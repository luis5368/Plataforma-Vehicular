<?php

use App\Http\Controllers\VehicleController;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActiveAdministratorGuard;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

try {
    $app = require __DIR__.'/bootstrap.php';
    [$script, $operation, $target, $batch, $worker] = $argv;
    $directory = storage_path('framework/production-audit');
    touch($directory.'/'.$batch.'-'.$worker.'.ready');
    $deadline = microtime(true) + 20;
    while (! is_file($directory.'/'.$batch.'.go')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Barrier timed out.');
        }
        usleep(10000);
    }
    if (in_array($operation, ['disable', 'demote'], true)) {
        DB::transaction(function () use ($operation, $target) {
            $role = $operation === 'demote' ? Role::where('name', 'consulta')->value('id') : null;
            $user = app(ActiveAdministratorGuard::class)->lock(User::findOrFail($target), roleId: $role, active: $operation === 'disable' ? false : null);
            usleep(200000);
            $user->update($operation === 'disable' ? ['is_active' => false] : ['role_id' => $role]);
        });
    } elseif ($operation === 'edit') {
        $vehicle = Vehicle::with('theftReport')->findOrFail($target);
        auth()->setUser(User::findOrFail($vehicle->registered_by));
        $request = Request::create('/vehiculos/'.$target, 'PUT', [
            'vin' => $vehicle->vin, 'plate' => $vehicle->plate, 'brand' => $vehicle->brand, 'model' => $vehicle->model,
            'department' => $vehicle->department, 'municipality' => $vehicle->municipality,
            'theft_report_address' => $vehicle->theft_report_address, 'has_theft_report' => '1',
            'theft_report_date' => now()->subDay()->toDateString(), 'theft_description' => 'Audit update '.$worker,
            'evidence_reference' => 'AUDIT',
        ]);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        app(VehicleController::class)->update($request, $vehicle);
    } else {
        DB::transaction(function () use ($operation, $target, $worker) {
            if ($operation === 'report') {
                DB::table('theft_reports')->insert(['vehicle_id' => $target, 'reported_by' => 1,
                    'report_date' => now(), 'description' => 'Audit', 'status' => 'validado']);
            } else {
                DB::table('vehicles')->insert(['registered_by' => 1,
                    'vin' => $operation === 'vin' ? '1HGCM82633A990001' : '1HGCM82633A99000'.$worker,
                    'plate' => $operation === 'plate' ? 'P999ZZZ' : 'P99'.$worker.'ZZZ',
                    'brand' => 'Audit', 'model' => 'Synthetic', 'department' => 'Audit', 'municipality' => 'Audit']);
            }
        });
    }
    echo 'ok';
} catch (ValidationException $exception) {
    echo 'blocked';
} catch (QueryException $exception) {
    if (($exception->errorInfo[1] ?? null) === 1062) {
        echo 'duplicate';
    } else {
        fwrite(STDERR, get_class($exception));
        exit(1);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception));
    exit(1);
}
