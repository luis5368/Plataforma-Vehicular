<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\StagingVehicleImport;
use App\Support\StagingImportException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StagingVehicleImportTest extends TestCase
{
    use RefreshDatabase;

    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'staging';
        config(['app.env' => 'staging', 'staging_import.enabled' => true, 'staging_import.database' => ':memory:', 'staging_import.testing_sqlite' => true]);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['id' => 7, 'role_id' => Role::firstOrCreate(['name' => 'admin'])->id]);
    }

    private function row(int $number = 1): array
    {
        return array_replace(array_fill_keys(StagingVehicleImport::HEADERS, ''), [
            'source_row_number' => (string) $number, 'source_id' => (string) $number,
            'synthetic_data' => '1', 'vin' => '1HGCM82633A'.sprintf('%06d', $number),
            'plate' => 'P'.sprintf('%03d', $number % 1000).chr(65 + intdiv($number, 1000)).'ST',
            'brand' => 'Marca sintética', 'model' => 'Modelo de prueba', 'year' => '2024',
            'department' => 'Guatemala', 'municipality' => 'Mixco', 'theft_report_address' => 'Lugar sintético',
            'vehicle_status' => 'reportado', 'has_theft_report' => '1', 'theft_report_date' => '2024-01-01',
            'theft_description' => "Escenario sintético, no real.\nTexto con comillas: \"prueba\".",
            'evidence_reference' => 'STAGING-SINTETICO-'.sprintf('%06d', $number),
            'report_status' => 'activo', 'validation_status' => 'pendiente', 'case_status' => 'abierto',
            'registered_by' => '1', 'reported_by' => '1', 'updated_by' => '1',
        ]);
    }

    private function csv(array $rows): string
    {
        $file = tempnam(sys_get_temp_dir(), 'pv-import-test-');
        $this->files[] = $file;
        $stream = fopen($file, 'wb');
        fputcsv($stream, StagingVehicleImport::HEADERS, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($stream, array_values($row), ',', '"', '');
        }
        fclose($stream);

        return $file;
    }

    private function runImport(string $file, bool $commit = false, int $actor = 7): int
    {
        return Artisan::call('vehicles:import-staging', ['csv' => $file, '--admin-id' => (string) $actor, '--commit' => $commit]);
    }

    private function assertNoImport(): void
    {
        foreach (['vehicles', 'theft_reports', 'staging_import_batches', 'audit_logs'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_dry_run_validates_complete_file_without_changing_any_table(): void
    {
        $this->admin();
        $this->assertSame(0, $this->runImport($this->csv([$this->row()])));
        $this->assertNoImport();
    }

    public function test_commit_preserves_pending_states_synthetic_identity_actor_and_idempotency(): void
    {
        $admin = $this->admin();
        $file = $this->csv([$this->row(), $this->row(2)]);
        $this->assertSame(0, $this->runImport($file, true));
        $this->assertDatabaseCount('vehicles', 2);
        $this->assertDatabaseCount('theft_reports', 2);
        $this->assertDatabaseCount('staging_import_batches', 1);
        $this->assertDatabaseCount('audit_logs', 1);
        foreach (Vehicle::with('theftReport')->get() as $vehicle) {
            $this->assertTrue($vehicle->synthetic_data);
            $this->assertSame($admin->id, $vehicle->registered_by);
            $this->assertSame('reportado', $vehicle->status);
            $report = $vehicle->theftReport;
            $this->assertSame($admin->id, $report->reported_by);
            $this->assertSame($admin->id, $report->updated_by);
            $this->assertSame('activo', $report->status);
            $this->assertSame('abierto', $report->case_status);
            $this->assertSame('pendiente', $report->validation_status);
            $this->assertNull($report->validation_method);
            $this->assertNull($report->validated_at);
            $this->assertNull($report->validated_by);
        }
        $audit = AuditLog::firstOrFail();
        $this->assertSame(hash_file('sha256', $file), $audit->new_values['file_hash']);
        $this->assertSame('committed', $audit->new_values['result']);
        $this->assertSame(1, $this->runImport($file, true));
        $this->assertSame(1, $this->runImport($file));
        $this->assertDatabaseCount('vehicles', 2);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public static function gates(): array
    {
        return [['production', true], ['local', true], ['testing', true], ['staging', false], ['staging', 'true']];
    }

    #[DataProvider('gates')]
    public function test_environment_and_flag_block_before_connecting(string $environment, mixed $enabled): void
    {
        $this->app['env'] = $environment;
        config(['staging_import.enabled' => $enabled]);
        DB::shouldReceive('table')->never();
        $this->assertSame(1, $this->runImport('not-present.csv', true));
    }

    public static function actors(): array
    {
        return [['missing'], ['inactive'], ['unverified'], ['consulta'], ['registro']];
    }

    #[DataProvider('actors')]
    public function test_unavailable_or_unauthorized_actor_never_imports(string $state): void
    {
        if ($state !== 'missing') {
            $user = $this->admin();
            $user->forceFill(match ($state) {
                'inactive' => ['is_active' => false], 'unverified' => ['email_verified_at' => null],
                default => ['role_id' => Role::firstOrCreate(['name' => $state])->id],
            })->save();
        }
        $this->assertSame(1, $this->runImport($this->csv([$this->row()]), true));
        $this->assertNoImport();
    }

    public static function invalidRows(): array
    {
        return [
            [['vin' => 'INVALID']], [['plate' => 'BAD']], [['brand' => '']], [['model' => str_repeat('a', 101)]],
            [['year' => '1899']], [['theft_report_date' => '2024-02-30']], [['theft_report_date' => '2999-01-01']],
            [['theft_latitude' => '91', 'theft_longitude' => '-90']], [['theft_latitude' => '14']],
            [['theft_latitude' => '14.123456789', 'theft_longitude' => '-90']],
            [['synthetic_data' => '0']], [['evidence_reference' => 'REAL-REFERENCE']],
            [['validation_status' => 'validado']], [['report_status' => 'validado']], [['case_status' => 'resuelto']],
            [['vehicle_status' => 'activo']], [['validated_by' => '1']], [['validation_method' => 'automatica']],
        ];
    }

    #[DataProvider('invalidRows')]
    public function test_invalid_last_row_prevents_all_writes(array $changes): void
    {
        $this->admin();
        $this->assertSame(1, $this->runImport($this->csv([$this->row(), array_replace($this->row(2), $changes)]), true));
        $this->assertNoImport();
    }

    public static function malformed(): array
    {
        return [["vin,plate\na,b\n"], ["\xFF"], ['"unclosed'], ['a"b,c'], ['"a"junk,b'], ["a,b\0c"]];
    }

    #[DataProvider('malformed')]
    public function test_malformed_encoding_and_headers_are_rejected(string $bytes): void
    {
        $this->admin();
        $file = $this->csv([]);
        file_put_contents($file, $bytes);
        $this->assertSame(1, $this->runImport($file, true));
        $this->assertNoImport();
    }

    public function test_limits_internal_duplicates_and_empty_file_reject_everything(): void
    {
        $this->admin();
        foreach (['vin', 'plate', 'evidence_reference'] as $field) {
            $first = $this->row();
            $second = $this->row(2);
            $second[$field] = $first[$field];
            $this->assertSame(1, $this->runImport($this->csv([$first, $second]), true));
        }
        $this->assertSame(1, $this->runImport($this->csv([]), true));
        config(['staging_import.max_rows' => 1]);
        $this->assertSame(1, $this->runImport($this->csv([$this->row(), $this->row(2)]), true));
        config(['staging_import.max_bytes' => 10]);
        $this->assertSame(1, $this->runImport($this->csv([$this->row()]), true));
        $this->assertNoImport();
    }

    public function test_existing_vin_or_plate_and_database_mismatch_are_rejected(): void
    {
        $this->admin();
        $row = $this->row();
        $existing = Vehicle::create(['vin' => $row['vin'], 'plate' => 'P999ZZZ', 'brand' => 'Test', 'model' => 'Test', 'registered_by' => 7]);
        $this->assertFalse($existing->fresh()->synthetic_data);
        $this->assertSame(1, $this->runImport($this->csv([$row]), true));
        $existing->update(['vin' => $this->row(3)['vin'], 'plate' => $row['plate']]);
        $this->assertSame(1, $this->runImport($this->csv([$row]), true));
        config(['staging_import.database' => 'different']);
        $this->assertSame(1, $this->runImport($this->csv([$row]), true));
        $this->assertDatabaseCount('vehicles', 1);
        $this->assertDatabaseCount('theft_reports', 0);
    }

    public function test_a_failure_in_the_second_chunk_rolls_back_first_chunk_batch_and_audit(): void
    {
        $this->admin();
        $calls = 0;
        DB::listen(function ($query) use (&$calls) {
            if (str_starts_with($query->sql, 'insert into "theft_reports"') && ++$calls === 2) {
                throw new \RuntimeException('SECRET must never reach output');
            }
        });
        $file = $this->csv(array_map(fn ($i) => $this->row($i), range(1, 501)));
        $this->assertSame(1, $this->runImport($file, true));
        $this->assertStringNotContainsString('SECRET', Artisan::output());
        $this->assertNoImport();
    }

    public function test_audit_failure_rolls_back_everything_and_actor_is_rechecked_at_commit(): void
    {
        $admin = $this->admin();
        $file = $this->csv([$this->row()]);
        $import = app(StagingVehicleImport::class);
        $input = $import->read($file);
        $admin->update(['is_active' => false]);
        try {
            $import->commit($input, $admin->id);
            $this->fail('An actor revoked after dry-run must not import.');
        } catch (StagingImportException) {
            $this->assertNoImport();
        }
        $admin->update(['is_active' => true]);
        AuditLog::creating(fn () => throw new \RuntimeException('private audit details'));
        try {
            $this->assertSame(1, $this->runImport($file, true));
            $this->assertStringNotContainsString('private audit details', Artisan::output());
            $this->assertNoImport();
        } finally {
            AuditLog::flushEventListeners();
        }
    }

    public function test_explicit_actor_is_required_and_connection_url_is_rejected(): void
    {
        $this->admin();
        $file = $this->csv([$this->row()]);
        $this->assertSame(1, Artisan::call('vehicles:import-staging', ['csv' => $file, '--commit' => true]));
        $this->assertSame(1, $this->runImport($file, true, 0));
        config(['database.connections.sqlite.url' => 'not-allowed']);
        $this->assertSame(1, $this->runImport($file, true));
        config(['database.connections.sqlite.url' => null]);
        $this->assertNoImport();
    }

    public function test_synthetic_migration_preserves_existing_rows_and_is_reversible(): void
    {
        $this->admin();
        $migration = require database_path('migrations/2026_10_29_000001_add_synthetic_data_to_vehicles.php');
        $migration->down();
        $id = DB::table('vehicles')->insertGetId(['vin' => $this->row()['vin'], 'plate' => $this->row()['plate'], 'brand' => 'Fixture', 'model' => 'Fixture', 'registered_by' => 7]);
        $migration->up();
        $this->assertFalse(Vehicle::findOrFail($id)->synthetic_data);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('vehicles', 'synthetic_data'));
        $migration->up();
    }

    public function test_normal_registration_still_validates_automatically_without_forged_synthetic_flags(): void
    {
        $this->app['env'] = 'testing';
        $owner = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'registro'])->id]);
        $this->actingAs($owner)->post('/vehiculos', [
            'vin' => $this->row()['vin'], 'plate' => $this->row()['plate'], 'brand' => 'Prueba', 'model' => 'Prueba',
            'department' => 'Guatemala', 'municipality' => 'Mixco', 'theft_report_address' => 'Lugar de prueba',
            'has_theft_report' => '1', 'theft_report_date' => '2024-01-01', 'theft_description' => 'Reporte normal de prueba',
            'evidence_reference' => 'DOC-TEST', 'synthetic_data' => '1', 'staging_import_batch_id' => 123,
        ])->assertSessionHasNoErrors()->assertRedirect('/vehiculos');
        $vehicle = Vehicle::firstOrFail();
        $this->assertFalse($vehicle->synthetic_data);
        $this->assertNull($vehicle->staging_import_batch_id);
        $this->assertSame('validado', $vehicle->theftReport->validation_status);
        $this->assertSame('automatica', $vehicle->theftReport->validation_method);
        $this->assertSame($owner->id, $vehicle->theftReport->validated_by);
    }

    public function test_full_7693_row_synthetic_batch_and_staging_labels(): void
    {
        $this->admin();
        $rows = array_map(fn ($i) => $this->row($i), range(1, 7693));
        $this->assertSame(0, $this->runImport($this->csv($rows), true));
        $this->assertDatabaseCount('vehicles', 7693);
        $this->assertDatabaseCount('theft_reports', 7693);
        // Changing APP_ENV for the command also enables CSRF in the test HTTP kernel.
        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/consulta-publica', ['vin' => $rows[0]['vin']])->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('DATOS SINTÉTICOS')->assertSee('STAGING')->assertSee('pendiente o no confirmado');
        $this->assertDatabaseMissing('theft_reports', ['validation_status' => 'validado']);
    }
}
