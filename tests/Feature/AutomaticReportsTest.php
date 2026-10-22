<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportAnalytics;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutomaticReportsTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'registro'): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function data(int $n = 1): array
    {
        return ['vin' => '1HGCM82633A'.str_pad((string) $n, 6, '0', STR_PAD_LEFT), 'plate' => 'P'.(100 + $n).'AAA',
            'brand' => 'Toyota', 'model' => 'Hilux', 'department' => 'Guatemala', 'municipality' => 'Mixco', 'zone' => 'Zona 1',
            'theft_report_address' => 'Ubicación declarada de prueba', 'theft_latitude' => 14.6, 'theft_longitude' => -90.6,
            'has_theft_report' => '1', 'theft_report_date' => now()->subDay()->toDateString(), 'theft_description' => 'Descripción del incidente declarada por la fuente',
            'evidence_reference' => 'REFERENCIA-TEST-'.$n];
    }

    private function createReport(): TheftReport
    {
        $this->actingAs($this->account())->post('/vehiculos', $this->data())->assertSessionHasNoErrors();

        return TheftReport::firstOrFail();
    }

    public function test_authorized_registry_publishes_automatically_with_server_metadata(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post('/vehiculos', $this->data() + ['theft_report_status' => 'resuelto', 'validation_status' => 'rechazado', 'case_status' => 'resuelto', 'validated_by' => 999])->assertSessionHasNoErrors();
        $r = TheftReport::firstOrFail();
        $this->assertSame('validado', $r->validation_status);
        $this->assertSame('abierto', $r->case_status);
        $this->assertSame('automatica', $r->validation_method);
        $this->assertSame($user->id, $r->validated_by);
        $this->assertNotNull($r->validated_at);
        $this->assertSame('reportado', $r->vehicle->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create_report', 'record_id' => $r->id]);
        $this->post('/logout');
        $this->post('/consulta-publica', ['vin' => $r->vehicle->vin])->assertOk()->assertSee('validación automática de integridad')->assertSee('no acredita de manera independiente')->assertDontSee($user->email)->assertDontSee($r->evidence_reference);
        $this->actingAs($this->account('consulta'))->get('/consultar/buscar?vin='.$r->vehicle->vin)->assertOk()->assertSee('validación automática de integridad');
    }

    public function test_unauthorized_inactive_and_unverified_accounts_cannot_write(): void
    {
        $consulta = $this->account('consulta');
        $this->actingAs($consulta)->post('/vehiculos', $this->data())->assertForbidden();
        $user = $this->account();
        $user->update(['is_active' => false]);
        $this->actingAs($user)->post('/vehiculos', $this->data())->assertForbidden();
        $user->forceFill(['is_active' => true, 'email_verified_at' => null])->save();
        $this->actingAs($user)->post('/vehiculos', $this->data())->assertRedirect('/verify-email');
        $this->assertDatabaseCount('theft_reports', 0);
    }

    public function test_incomplete_invalid_and_duplicate_reports_do_not_publish(): void
    {
        $this->actingAs($this->account());
        foreach (['vin' => 'INVALID', 'department' => '', 'theft_report_date' => now()->addDay()->toDateString(), 'evidence_reference' => '', 'theft_report_address' => ''] as $field => $value) {
            $response = $this->post('/vehiculos', array_replace($this->data(), [$field => $value, 'theft_report_status' => 'validado', 'validation_status' => 'validado']));
            $response->assertSessionHasErrors();
            $this->assertDatabaseCount('theft_reports', 0);
            $this->assertDatabaseCount('vehicles', 0);
        }
        $this->post('/vehiculos', $this->data())->assertSessionHasNoErrors();
        $this->post('/vehiculos', $this->data())->assertSessionHasErrors(['vin', 'plate']);
        $this->assertDatabaseCount('theft_reports', 1);
        $r = TheftReport::firstOrFail();
        $r->fill(['status' => 'resuelto', 'validation_status' => 'rechazado', 'case_status' => 'resuelto', 'validation_method' => 'administrativa', 'validated_by' => 999]);
        $this->assertSame('validado', $r->status);
        $this->assertSame('abierto', $r->case_status);
        $this->assertSame('automatica', $r->validation_method);
    }

    public function test_admin_decisions_require_reason_and_preserve_validation_when_resolving(): void
    {
        $r = $this->createReport();
        $stamp = $r->validated_at->toDateTimeString();
        $this->actingAs($this->account('admin'));
        $this->put('/vehiculos/'.$r->vehicle_id, $this->data() + ['review_action' => 'resolver'])->assertSessionHasErrors('review_reason');
        $this->put('/vehiculos/'.$r->vehicle_id, $this->data() + ['review_action' => 'resolver', 'review_reason' => 'Caso recuperado según fuente'])->assertSessionHasNoErrors();
        $r->refresh();
        $this->assertSame('resuelto', $r->case_status);
        $this->assertSame('validado', $r->validation_status);
        $this->assertSame('automatica', $r->validation_method);
        $this->assertSame($stamp, $r->validated_at->toDateTimeString());
        $this->assertSame('activo', $r->vehicle->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report_resolver']);
        $this->put('/vehiculos/'.$r->vehicle_id, $this->data() + ['review_action' => 'invalidar', 'review_reason' => 'Datos inconsistentes'])->assertSessionHasNoErrors();
        $this->assertSame('rechazado', $r->fresh()->validation_status);
        $this->assertSame('resuelto', $r->fresh()->case_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report_invalidar']);
        $this->put('/vehiculos/'.$r->vehicle_id, array_replace($this->data(), ['theft_description' => 'Descripción corregida', 'review_action' => 'validar', 'review_reason' => 'Referencia revisada']))->assertSessionHasNoErrors();
        $this->assertSame('administrativa', $r->fresh()->validation_method);
        $this->assertSame('resuelto', $r->fresh()->case_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report_validar']);
        $this->get('/dashboard')->assertViewHas('stats', fn ($s) => $s['active_reports'] === 0 && $s['resolved_reports'] === 1);
        $this->get('/consulta-region')->assertViewHas('vehicles', fn ($v) => $v->isEmpty())->assertViewMissing('mapData');
        $this->post('/logout');
        $this->post('/consulta-publica', ['vin' => $r->vehicle->vin])->assertSee('Reporte resuelto')->assertSee('revisión administrativa');
    }

    public function test_registry_cannot_reverse_admin_invalidation_or_modify_resolved_cases(): void
    {
        $r = $this->createReport();
        $owner = $r->reportedBy;
        $this->actingAs($this->account('admin'))->put('/vehiculos/'.$r->vehicle_id, $this->data() + ['review_action' => 'invalidar', 'review_reason' => 'Revisar inconsistencia'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->put('/vehiculos/'.$r->vehicle_id, array_replace($this->data(), ['theft_description' => 'Intento de alteración', 'review_action' => 'validar', 'review_reason' => 'Cliente intenta validar']))->assertSessionHasErrors();
        $this->assertSame('rechazado', $r->fresh()->validation_status);
        $this->actingAs($this->account('consulta'))->put('/vehiculos/'.$r->vehicle_id, $this->data())->assertForbidden();
    }

    public function test_dashboard_region_map_and_filters_share_the_same_unique_vehicle_set(): void
    {
        $r = $this->createReport();
        $this->post('/vehiculos', array_replace($this->data(2), ['department' => 'Petén', 'municipality' => 'Flores', 'zone' => 'Centro']))->assertSessionHasNoErrors();
        $this->post('/vehiculos', $this->data(3))->assertSessionHasNoErrors();
        TheftReport::where('vehicle_id', Vehicle::where('vin', $this->data(3)['vin'])->value('id'))->update(['validation_status' => 'pendiente', 'status' => 'pendiente_validacion']);
        $this->actingAs($this->account('admin'));
        $url = '?department=Guatemala&municipality=Mixco&zone=Zona%201';
        $this->get('/dashboard'.$url)->assertOk()->assertViewHas('stats', fn ($s) => $s['active_reports'] === 1 && $s['pending_reports'] === 1 && $s['total_vehicles'] === 2)
            ->assertViewHas('topDepartments', fn ($rows) => $rows->sum('total') === 1)->assertViewHas('topBrands', fn ($rows) => $rows->sum('total') === 1);
        $this->get('/consulta-region'.$url)->assertOk()->assertViewHas('vehicles', fn ($v) => $v->count() === 1 && $v->first()->vin === $r->vehicle->vin)
            ->assertViewMissing('mapData');
        $this->getJson('/consulta-region/puntos'.$url.'&south=14&north=15&west=-91&east=-90&zoom=14')
            ->assertOk()->assertJsonCount(1, 'features')->assertJsonPath('features.0.vin', $r->vehicle->vin);
        $this->get('/dashboard?department=SinCoincidencias')->assertOk()->assertSee('Sin datos disponibles');
    }

    public function test_query_failure_is_not_presented_as_empty_data(): void
    {
        $this->actingAs($this->account('admin'));
        $this->mock(ReportAnalytics::class, function ($mock) {
            $mock->shouldReceive('filters')->andReturn([]);
            $mock->shouldReceive('dashboard')->andThrow(new QueryException('sqlite', 'select secret', [], new \Exception('private connection info')));
        });
        $this->get('/dashboard')->assertStatus(503)->assertSee('La consulta de reportes falló')->assertDontSee('private connection info')->assertDontSee('Sin datos disponibles');
    }

    public function test_migration_preserves_pending_and_resolved_legacy_records_without_auto_validating(): void
    {
        $r = $this->createReport();
        $this->post('/vehiculos', $this->data(2))->assertSessionHasNoErrors();
        $pending = TheftReport::latest('id')->firstOrFail();
        $migration = require database_path('migrations/2026_10_18_000003_separate_report_validation_and_case.php');
        $migration->down();
        DB::table('theft_reports')->where('id', $r->id)->update(['status' => 'resuelto']);
        DB::table('theft_reports')->where('id', $pending->id)->update(['status' => 'pendiente_validacion']);
        $migration->up();
        $this->assertSame('resuelto', $r->fresh()->case_status);
        $this->assertSame('pendiente', $r->fresh()->validation_status);
        $this->assertNull($r->fresh()->validated_at);
        $this->assertNull($r->fresh()->validation_method);
        $this->assertSame('resuelto', $r->fresh()->status);
        $this->assertSame('pendiente', $pending->fresh()->validation_status);
        $this->assertSame('abierto', $pending->fresh()->case_status);
        $this->assertNull($pending->fresh()->validation_method);
    }

    public function test_read_only_diagnostic_never_changes_report_states(): void
    {
        $r = $this->createReport();
        $before = DB::table('theft_reports')->get()->toJson();
        $this->artisan('reports:diagnose', ['--details' => true])->assertSuccessful();
        $this->assertSame($before, DB::table('theft_reports')->get()->toJson());
    }

    public function test_editing_a_historical_pending_report_does_not_reclassify_it_automatically(): void
    {
        $r = $this->createReport();
        $r->forceFill(['status' => 'pendiente_validacion', 'validation_status' => 'pendiente', 'validation_method' => null, 'validated_at' => null, 'validated_by' => null])->save();
        $this->put('/vehiculos/'.$r->vehicle_id, array_replace($this->data(), ['theft_description' => 'Descripción histórica corregida']))->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $r->fresh()->validation_status);
        $this->assertNull($r->fresh()->validated_at);
        $this->actingAs($this->account('admin'))->put('/vehiculos/'.$r->vehicle_id, array_replace($this->data(), [
            'theft_description' => 'Descripción histórica corregida', 'review_action' => 'validar', 'review_reason' => 'Referencia y antecedentes revisados',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('validado', $r->fresh()->validation_status);
        $this->assertSame('administrativa', $r->fresh()->validation_method);
    }
}
