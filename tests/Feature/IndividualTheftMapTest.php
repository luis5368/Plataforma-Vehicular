<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndividualTheftMapTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function vehicle(?float $lat, ?float $lng, bool $report = true): Vehicle
    {
        $owner = $this->account('registro');
        $vehicle = Vehicle::create(['registered_by' => $owner->id, 'vin' => '1HGCM82633A000001', 'plate' => 'P101AAA',
            'brand' => 'Toyota', 'model' => 'Hilux', 'department' => 'Guatemala', 'municipality' => 'Mixco',
            'theft_report_address' => 'Referencia declarada del robo', 'theft_latitude' => $lat, 'theft_longitude' => $lng]);
        if ($report) {
            $theft = new TheftReport(['vehicle_id' => $vehicle->id, 'reported_by' => $owner->id,
                'report_date' => now()->subDay(), 'description' => 'Declaración de prueba']);
            $theft->forceFill(['validation_status' => 'validado', 'case_status' => 'abierto', 'status' => 'validado', 'validation_method' => 'automatica'])->save();
        }

        return $vehicle;
    }

    public static function searchMethods(): array
    {
        return [['vin'], ['plate']];
    }

    #[DataProvider('searchMethods')]
    public function test_individual_search_maps_only_theft_coordinates_and_recorded_location(string $field): void
    {
        $v = $this->vehicle(14.6349, -90.5069);
        $response = $this->actingAs($this->account('consulta'))->get('/consultar/buscar?'.$field.'='.$v->$field);
        $response->assertOk()->assertSee('Ubicación reportada del robo')
            ->assertSee('data-latitude="14.6349"', false)->assertSee('data-longitude="-90.5069"', false)
            ->assertSee('Referencia declarada del robo')->assertSee('Mixco')->assertSee('Guatemala')
            ->assertSee('el mapa no verifica por sí mismo que el hecho ocurrió')
            ->assertDontSee('No se registró una ubicación con coordenadas');
        $this->assertSame(1, substr_count($response->getContent(), 'src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"'));
    }

    public static function missingCoordinates(): array
    {
        return [[null, null], [14.6, null], [null, -90.5], [91, -90.5]];
    }

    #[DataProvider('missingCoordinates')]
    public function test_missing_or_invalid_coordinates_never_guess_a_position(?float $lat, ?float $lng): void
    {
        $v = $this->vehicle($lat, $lng);
        $this->actingAs($this->account('consulta'))->get('/consultar/buscar?plate='.$v->plate)
            ->assertOk()->assertSee('No se registró una ubicación con coordenadas')
            ->assertSee('Referencia declarada del robo')->assertDontSee('data-reported-theft-map', false);
    }

    public function test_zero_coordinates_are_valid_and_recorded_text_is_escaped(): void
    {
        $v = $this->vehicle(0, 0);
        $v->update(['theft_report_address' => '<script>mapXss()</script>']);
        $this->actingAs($this->account('admin'))->get('/consultar/buscar?vin='.$v->vin)->assertOk()
            ->assertSee('data-latitude="0"', false)->assertSee('data-longitude="0"', false)
            ->assertDontSee('<script>mapXss()</script>', false)->assertSee('&lt;script&gt;mapXss()&lt;/script&gt;', false);
    }

    public function test_no_report_does_not_show_a_theft_map_and_permissions_stay_unchanged(): void
    {
        $v = $this->vehicle(14.6, -90.5, false);
        $this->actingAs($this->account('consulta'))->get('/consultar/buscar?vin='.$v->vin)->assertOk()
            ->assertDontSee('Ubicación reportada del robo')->assertDontSee('data-reported-theft-map', false);
        $this->actingAs($v->registeredBy)->get('/consultar/buscar?vin='.$v->vin)->assertForbidden();
        $this->post('/logout');
        $this->get('/consultar/buscar?vin='.$v->vin)->assertRedirect('/login');
    }

    public function test_public_result_does_not_gain_the_private_map(): void
    {
        $v = $this->vehicle(14.6, -90.5);
        $this->post('/consulta-publica', ['vin' => $v->vin])->assertOk()->assertDontSee('data-reported-theft-map', false);
    }

    public function test_current_form_already_captures_and_persists_theft_coordinates(): void
    {
        $this->actingAs($this->account('registro'))->get('/vehiculos/create')->assertOk()
            ->assertSee('name="theft_latitude"', false)->assertSee('name="theft_longitude"', false)->assertSee('id="map"', false);
        $this->post('/vehiculos', ['vin' => '1HGCM82633A000001', 'plate' => 'P101AAA', 'brand' => 'Toyota', 'model' => 'Hilux',
            'department' => 'Guatemala', 'municipality' => 'Mixco', 'has_theft_report' => '1',
            'theft_report_date' => now()->subDay()->toDateString(), 'theft_description' => 'Declaración',
            'evidence_reference' => 'TEST', 'theft_report_address' => 'Lugar del robo', 'theft_latitude' => 14.6349, 'theft_longitude' => -90.5069])
            ->assertSessionHasNoErrors()->assertRedirect('/vehiculos');
        $vehicle = Vehicle::firstOrFail();
        $this->assertEquals(14.6349, (float) $vehicle->theft_latitude);
        $this->assertEquals(-90.5069, (float) $vehicle->theft_longitude);
        $this->assertSame('automatica', $vehicle->theftReport->validation_method);
    }
}
