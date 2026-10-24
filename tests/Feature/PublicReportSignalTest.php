<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicReportSignalTest extends TestCase
{
    use RefreshDatabase;

    public static function states(): array
    {
        return [
            'active' => ['validado', 'abierto', 'danger', 'reporte de robo vigente registrado'],
            'pending' => ['pendiente', 'abierto', 'warning', 'pendiente o no confirmado'],
            'rejected' => ['rechazado', 'abierto', 'warning', 'pendiente o no confirmado'],
            'resolved' => ['validado', 'resuelto', 'neutral', 'Reporte resuelto'],
            'withdrawn pending' => ['pendiente', 'retirado', 'neutral', 'Reporte retirado'],
            'without report' => [null, null, 'success', 'Esto no garantiza'],
        ];
    }

    #[DataProvider('states')]
    public function test_public_signal_uses_actual_case_and_validation_status_without_private_details(?string $validation, ?string $case, string $tone, string $message): void
    {
        $owner = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'registro'])->id]);
        $vehicle = Vehicle::create(['registered_by' => $owner->id, 'vin' => '1HGCM82633A000001', 'plate' => 'P101AAA', 'brand' => 'Toyota', 'model' => 'Hilux',
            'theft_report_address' => 'PRIVATE LOCATION', 'theft_latitude' => 14.6, 'theft_longitude' => -90.5]);
        if ($validation !== null) {
            $report = new TheftReport(['vehicle_id' => $vehicle->id, 'reported_by' => $owner->id, 'report_date' => now()->subDay(), 'description' => 'PRIVATE DESCRIPTION']);
            $report->forceFill(['validation_status' => $validation, 'case_status' => $case, 'status' => $validation, 'validation_method' => 'automatica'])->save();
        }
        foreach (['vin', 'plate'] as $field) {
            $this->post(route('public.search'), [$field => $vehicle->$field])->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertSee('data-report-signal="'.$tone.'"', false)->assertSee($message)
                ->assertSee('¿Deseas consultar información más detallada? Inicia sesión con una cuenta autorizada para el rol Consulta.')
                ->assertSee('href="'.route('login').'"', false)
                ->assertSee('Nueva consulta')->assertSee('Esta consulta muestra únicamente el estado registrado.')
                ->assertDontSee('PRIVATE LOCATION')->assertDontSee('PRIVATE DESCRIPTION')
                ->assertDontSee('data-reported-theft-map', false)->assertDontSee($owner->email);
        }
    }

    public function test_missing_vehicle_is_neutral_and_existing_registration_flow_stays_on_landing(): void
    {
        $this->post(route('public.search'), ['plate' => 'P999ZZZ'])->assertOk()
            ->assertSee('data-report-signal="neutral"', false)->assertSee('Sin coincidencia para el VIN o la placa.')
            ->assertSee('No se encontró un vehículo con este identificador.');
        $this->get('/')->assertOk()->assertSee('href="'.route('register.choice').'"', false);
        $this->get(route('login'))->assertOk();
    }
}
