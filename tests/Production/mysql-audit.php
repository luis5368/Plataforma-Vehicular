<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AccountLifecycle;
use App\Services\ActiveAdministratorGuard;
use App\Services\RegionalMap;
use App\Services\ReportAnalytics;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function verify(bool $condition, string $label): void
{
    if (! $condition) {
        throw new RuntimeException($label);
    }
    echo 'PASS '.$label.PHP_EOL;
}

function race(string $operation, array $targets): array
{
    $directory = storage_path('framework/production-audit');
    $batch = bin2hex(random_bytes(8));
    $processes = [];
    foreach ($targets as $index => $target) {
        $process = proc_open([PHP_BINARY, __DIR__.'/mysql-worker.php', $operation, (string) $target, $batch, (string) ($index + 1)],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    $deadline = microtime(true) + 20;
    while (! is_file($directory.'/'.$batch.'-1.ready') || ! is_file($directory.'/'.$batch.'-2.ready')) {
        if (microtime(true) > $deadline) {
            foreach ($processes as [$process]) {
                proc_terminate($process);
            }
            throw new RuntimeException('Worker start timeout.');
        }
        usleep(10000);
    }
    touch($directory.'/'.$batch.'.go');
    $results = [];
    foreach ($processes as [$process, $pipes]) {
        $results[] = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || $error !== '') {
            throw new RuntimeException('Worker failed: '.$error);
        }
    }
    foreach (glob($directory.'/'.$batch.'*') as $file) {
        unlink($file);
    }
    sort($results);

    return $results;
}

try {
    $directory = dirname(__DIR__, 2).'/storage/framework/production-audit';
    if (! is_file($directory.'/mysql.pid')) {
        throw new RuntimeException('Missing isolated MySQL instance.');
    }
    $pdo = new PDO('mysql:host=127.0.0.1;port=33079', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    verify(str_starts_with($pdo->query('SELECT VERSION()')->fetchColumn(), '8.0.'), 'MySQL 8.0 (not SQLite/MariaDB)');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS pv_audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $app = require __DIR__.'/bootstrap.php';
    verify(Artisan::call('migrate:fresh', ['--force' => true]) === 0, 'all migrations on isolated pv_audit');
    $adminRole = Role::create(['name' => 'admin', 'description' => 'Audit']);
    $consultaRole = Role::create(['name' => 'consulta', 'description' => 'Audit']);
    $registryRole = Role::create(['name' => 'registro', 'description' => 'Audit']);
    $owner = User::factory()->create(['role_id' => $registryRole->id]);
    $admins = User::factory()->count(2)->create(['role_id' => $adminRole->id]);
    foreach (['disable', 'demote'] as $operation) {
        foreach ($admins as $admin) {
            $admin->update(['role_id' => $adminRole->id, 'is_active' => true]);
        }
        verify(race($operation, $admins->modelKeys()) === ['blocked', 'ok'], 'concurrent last-admin '.$operation.' serialized');
        verify(User::where('role_id', $adminRole->id)->where('is_active', true)->count() === 1, 'one active administrator survives '.$operation);
    }
    foreach (['vin', 'plate'] as $operation) {
        verify(race($operation, [0, 0]) === ['duplicate', 'ok'], 'concurrent '.$operation.' uniqueness enforced by MySQL');
        DB::table('vehicles')->delete();
    }
    $vehicle = Vehicle::create(['registered_by' => $owner->id, 'vin' => '1HGCM82633A880001', 'plate' => 'P888ZZZ',
        'brand' => 'Audit', 'model' => 'Synthetic', 'department' => 'Audit', 'municipality' => 'Audit', 'theft_report_address' => 'Audit']);
    verify(race('report', [$vehicle->id, $vehicle->id]) === ['duplicate', 'ok'], 'concurrent single-report-per-vehicle constraint');
    $vehicle->theftReport()->update(['reported_by' => $owner->id, 'validation_status' => 'validado', 'case_status' => 'abierto',
        'validation_method' => 'automatica', 'validated_by' => $owner->id, 'validated_at' => now(), 'evidence_reference' => 'AUDIT']);
    verify(race('edit', [$vehicle->id, $vehicle->id]) === ['ok', 'ok'], 'concurrent report updates through VehicleController');
    $edits = AuditLog::where('action', 'update_report')->orderBy('id')->get();
    verify($edits->count() === 2 && $edits[1]->old_values['description'] === $edits[0]->new_values['description'], 'serialized report audit chain has no lost history');
    DB::table('sessions')->insert(['id' => 'audit-device-1', 'user_id' => $owner->id, 'payload' => '', 'last_activity' => time()]);
    DB::table('sessions')->insert(['id' => 'audit-device-2', 'user_id' => $owner->id, 'payload' => '', 'last_activity' => time()]);
    DB::transaction(function () use ($owner) {
        $locked = app(ActiveAdministratorGuard::class)->lock($owner, active: false);
        app(AccountLifecycle::class)->setActive($locked, false, $owner->id, '127.0.0.1');
    });
    verify(DB::table('sessions')->where('user_id', $owner->id)->count() === 0 && $vehicle->fresh() !== null, 'database sessions revoked; vehicle retained');

    // Deterministic synthetic distribution, never imported from the real database.
    $vehicles = $reports = [];
    for ($i = 1; $i <= 10000; $i++) {
        $vehicles[] = ['id' => 1000 + $i, 'registered_by' => $owner->id, 'vin' => '1HGCM82633B'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
            'plate' => sprintf('P%03dAA%s', $i % 1000, chr(65 + intdiv($i, 1000))), 'brand' => 'Audit', 'model' => 'Synthetic', 'department' => $i % 2 ? 'North' : 'South',
            'municipality' => 'Town'.($i % 10), 'zone' => 'Zone'.($i % 4), 'theft_report_address' => 'Synthetic audit location',
            'theft_latitude' => $i % 25 === 0 ? null : 14 + ($i % 100) * 0.025,
            'theft_longitude' => $i % 25 === 0 ? null : -92 + intdiv($i, 100) * 0.025, 'created_at' => now(), 'updated_at' => now()];
        $reports[] = ['vehicle_id' => 1000 + $i, 'reported_by' => $owner->id, 'report_date' => now()->subDays($i % 180),
            'description' => 'Synthetic', 'status' => 'validado', 'validation_status' => $i % 7 ? 'validado' : 'pendiente',
            'case_status' => $i % 11 ? 'abierto' : 'resuelto', 'validation_method' => 'automatica'];
    }
    foreach (array_chunk($vehicles, 500) as $chunk) {
        DB::table('vehicles')->insert($chunk);
    }
    foreach (array_chunk($reports, 500) as $chunk) {
        DB::table('theft_reports')->insert($chunk);
    }
    $analytics = app(ReportAnalytics::class);
    $map = app(RegionalMap::class);
    $area = ['south' => 13, 'north' => 18, 'west' => -93, 'east' => -88, 'zoom' => 14];
    $metrics = [];
    foreach (['unfiltered' => [], 'filtered' => ['department' => 'North', 'municipality' => 'Town1', 'zone' => 'Zone1']] as $name => $filters) {
        $times = [];
        DB::enableQueryLog();
        for ($sample = 0; $sample < 6; $sample++) {
            $start = hrtime(true);
            $page = $analytics->activeVehicles($filters)->with('theftReport')->latest()->orderByDesc('id')->paginate(25);
            $dashboard = $analytics->dashboard($filters);
            $map->initialBounds($analytics, $filters);
            $points = $map->points($analytics, $filters, $area);
            $clusters = $map->points($analytics, $filters, array_replace($area, ['zoom' => 8]));
            $fineClusters = $map->points($analytics, $filters, array_replace($area, ['zoom' => 13]));
            if ($sample > 0) {
                $times[] = round((hrtime(true) - $start) / 1e6, 2);
            }
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        DB::flushQueryLog();
        verify($page->count() === 25 && $dashboard['stats']['active_reports'] === $page->total(), $name.' full filtered KPI; 25-row page');
        verify($points['meta']['feature_count'] === 300 && $points['meta']['truncated'], $name.' map cap and omission metadata');
        verify($clusters['meta']['represented_count'] === $points['meta']['total_matching'] && ! $clusters['meta']['truncated'], $name.' low-zoom coverage');
        verify($fineClusters['meta']['feature_count'] <= 300, $name.' high-density cluster cap');
        $plans = [];
        foreach ($queries as $query) {
            if (str_starts_with(strtolower($query['query']), 'select')) {
                $fingerprint = hash('sha256', $query['query']);
                if (! isset($plans[$fingerprint])) {
                    $plans[$fingerprint] = ['sql' => $query['query'], 'plan' => DB::select('EXPLAIN '.$query['query'], $query['bindings'])];
                }
            }
        }
        sort($times);
        $metrics[$name] = ['warm_samples_ms' => $times, 'median_ms' => $times[2], 'max_ms' => max($times),
            'page_total' => $page->total(), 'points' => $points['meta'], 'clusters' => $clusters['meta'],
            'fine_clusters' => $fineClusters['meta'], 'point_json_bytes' => strlen(json_encode($points)), 'plans' => array_values($plans)];
    }
    $indexes = ['vehicles' => DB::select('SHOW INDEX FROM vehicles'), 'theft_reports' => DB::select('SHOW INDEX FROM theft_reports'),
        'sessions' => DB::select('SHOW INDEX FROM sessions')];
    file_put_contents($directory.'/mysql-results.json', json_encode(['mysql' => DB::selectOne('SELECT VERSION() AS version, @@transaction_isolation AS isolation_level'),
        'synthetic_vehicles' => 10000, 'metrics' => $metrics, 'indexes' => $indexes], JSON_PRETTY_PRINT));
    echo 'RESULTS storage/framework/production-audit/mysql-results.json (synthetic data only)'.PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL '.get_class($exception).': '.($exception instanceof RuntimeException && ! $exception instanceof PDOException ? $exception->getMessage() : 'details withheld').PHP_EOL);
    exit(1);
}
