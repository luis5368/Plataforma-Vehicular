<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RegionalMap;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegionalPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'consulta'): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function report(User $owner, int $number, array $vehicle = [], array $report = []): Vehicle
    {
        $v = Vehicle::create(array_replace(['vin' => '1HGCM82633A'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
            'plate' => 'P'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'AAA', 'brand' => 'Toyota', 'model' => 'Hilux',
            'department' => 'Guatemala', 'municipality' => 'Mixco', 'zone' => 'Zona 1', 'registered_by' => $owner->id,
            'theft_latitude' => 14.6, 'theft_longitude' => -90.6, 'theft_report_address' => 'Dirección detallada de prueba', 'status' => 'reportado'], $vehicle));
        TheftReport::forceCreate(array_replace(['vehicle_id' => $v->id, 'reported_by' => $owner->id, 'report_date' => now()->subDay()->toDateString(),
            'description' => 'Descripción detallada', 'status' => 'validado', 'validation_status' => 'validado', 'case_status' => 'abierto',
            'validation_method' => 'automatica', 'validated_at' => now(), 'validated_by' => $owner->id, 'evidence_reference' => 'Referencia privada'], $report));

        return $v;
    }

    private function url(array $values = []): string
    {
        return '/consulta-region/puntos?'.http_build_query(array_replace(['south' => 14, 'north' => 15, 'west' => -91, 'east' => -90, 'zoom' => 14], $values));
    }

    public function test_regional_routes_keep_authentication_verification_activity_and_role_guards(): void
    {
        foreach (['/consulta-region', $this->url()] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->actingAs($this->account('registro'));
        foreach (['/consulta-region', $this->url()] as $url) {
            $this->get($url)->assertForbidden();
        }
        foreach (['consulta', 'admin'] as $role) {
            $user = $this->account($role);
            $this->actingAs($user)->get('/consulta-region')->assertOk();
            $this->getJson($this->url())->assertOk();
            $user->update(['is_active' => false]);
            $this->actingAs($user)->getJson($this->url())->assertForbidden();
            $user->forceFill(['is_active' => true, 'email_verified_at' => null])->save();
            $this->actingAs($user)->get('/consulta-region')->assertRedirect('/verify-email');
            $this->getJson($this->url())->assertForbidden();
        }
    }

    public function test_pagination_keeps_filters_and_kpis_cover_every_matching_record(): void
    {
        $owner = $this->account('registro');
        for ($i = 1; $i <= 27; $i++) {
            $this->report($owner, $i);
        }
        $this->report($owner, 28, ['department' => 'Petén']);
        $this->actingAs($this->account());
        $filters = ['department' => 'Guatemala', 'municipality' => 'Mixco', 'zone' => 'Zona 1', 'extra' => 'keep'];
        $response = $this->get('/consulta-region?'.http_build_query($filters));
        $response->assertOk()->assertViewHas('vehicles', fn ($v) => $v->count() === 25 && $v->total() === 27)
            ->assertViewHas('stats', fn ($s) => $s['active_reports'] === 27 && $s['total_vehicles'] === 27)
            ->assertViewHas('topDepartments', fn ($rows) => $rows->sum('total') === 27)
            ->assertViewHas('monthlyTrend', fn ($rows) => (int) $rows->sum('total') === 27)
            ->assertViewMissing('mapData')->assertDontSee('var locations');
        $next = $response->viewData('vehicles')->nextPageUrl();
        parse_str(parse_url($next, PHP_URL_QUERY), $parameters);
        foreach ($filters as $name => $value) {
            $this->assertSame($value, $parameters[$name]);
        }
        $this->get($next)->assertOk()->assertViewHas('vehicles', fn ($v) => $v->count() === 2)
            ->assertViewHas('stats', fn ($s) => $s['active_reports'] === 27);
    }

    public function test_map_returns_only_active_matching_points_in_the_viewport_with_details(): void
    {
        $owner = $this->account('registro');
        $v = $this->report($owner, 1);
        $this->report($owner, 2, ['theft_latitude' => 16]);
        $this->report($owner, 3, ['department' => 'Petén']);
        $this->report($owner, 4, [], ['case_status' => 'resuelto']);
        $this->report($owner, 5, [], ['validation_status' => 'pendiente']);
        $this->report($owner, 6, ['zone' => 'Zona 2']);
        $this->report($owner, 7, ['municipality' => 'Guatemala']);
        $this->actingAs($this->account())->getJson($this->url(['department' => 'Guatemala', 'municipality' => 'Mixco', 'zone' => 'Zona 1']))
            ->assertOk()->assertJsonCount(1, 'features')->assertJsonPath('features.0.vin', $v->vin)
            ->assertJsonPath('features.0.address', 'Dirección detallada de prueba')
            ->assertJsonPath('features.0.type', 'point')->assertJsonPath('meta.total_matching', 1)
            ->assertJsonPath('meta.truncated', false)->assertDontSee('Referencia privada')->assertDontSee($owner->email)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public static function invalidAreas(): array
    {
        return [[['south' => -91]], [['north' => 91]], [['west' => -181]], [['east' => 181]],
            [['south' => 16, 'north' => 15]], [['west' => -90, 'east' => -90]], [['zoom' => 19]], [['zoom' => 1.5]], [['south' => 'bad']]];
    }

    #[DataProvider('invalidAreas')]
    public function test_invalid_geography_and_zoom_are_rejected(array $values): void
    {
        $this->actingAs($this->account())->getJson($this->url($values))->assertUnprocessable();
    }

    public function test_missing_coordinates_do_not_break_table_or_map(): void
    {
        $v = $this->report($this->account('registro'), 1, ['theft_latitude' => null, 'theft_longitude' => null]);
        $this->actingAs($this->account())->get('/consulta-region')->assertOk()->assertSee($v->vin)
            ->assertViewHas('stats', fn ($s) => $s['active_reports'] === 1);
        $this->getJson($this->url())->assertOk()->assertJsonCount(0, 'features')->assertJsonPath('meta.total_matching', 0)->assertJsonPath('meta.truncated', false);
    }

    public function test_point_limit_and_sql_clustering_report_the_real_number_of_matches(): void
    {
        $owner = $this->account('registro');
        for ($i = 1; $i <= RegionalMap::LIMIT + 1; $i++) {
            $this->report($owner, $i);
        }
        $this->actingAs($this->account())->getJson($this->url())->assertOk()->assertJsonCount(RegionalMap::LIMIT, 'features')
            ->assertJsonPath('meta.truncated', true)->assertJsonPath('meta.total_matching', RegionalMap::LIMIT + 1)
            ->assertJsonPath('meta.represented_count', RegionalMap::LIMIT);
        $this->getJson($this->url(['zoom' => 8]))->assertOk()->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.type', 'cluster')->assertJsonPath('features.0.count', RegionalMap::LIMIT + 1)
            ->assertJsonPath('meta.represented_count', RegionalMap::LIMIT + 1)->assertJsonPath('meta.truncated', false);
        foreach (Vehicle::orderBy('id')->get() as $index => $vehicle) {
            $vehicle->update(['theft_latitude' => 14 + ($index % 20) * 0.04, 'theft_longitude' => -90.99 + intdiv($index, 20) * 0.04]);
        }
        $this->getJson($this->url(['zoom' => 13]))->assertOk()->assertJsonCount(RegionalMap::LIMIT, 'features')
            ->assertJsonPath('meta.truncated', true)->assertJsonPath('meta.total_matching', RegionalMap::LIMIT + 1);
    }

    public function test_antimeridian_viewport_is_supported(): void
    {
        $owner = $this->account('registro');
        $this->report($owner, 1, ['theft_longitude' => 179]);
        $this->report($owner, 2, ['theft_longitude' => -179]);
        $this->report($owner, 3, ['theft_longitude' => 0]);
        $this->actingAs($this->account())->getJson($this->url(['west' => 170, 'east' => -170]))->assertOk()->assertJsonCount(2, 'features');
    }

    public function test_map_and_page_rate_limits_are_independent_and_per_user(): void
    {
        $this->actingAs($this->account());
        for ($i = 0; $i < 120; $i++) {
            $this->getJson($this->url())->assertOk();
        }
        $this->getJson($this->url())->assertStatus(429)->assertHeader('Retry-After');
        for ($i = 0; $i < 30; $i++) {
            $this->get('/consulta-region')->assertOk();
        }
        $this->get('/consulta-region')->assertStatus(429);
        $this->actingAs($this->account())->getJson($this->url())->assertOk();
        $this->get('/consulta-region')->assertOk();
    }

    public function test_map_database_failure_is_not_an_empty_success(): void
    {
        $this->mock(RegionalMap::class, fn ($mock) => $mock->shouldReceive('points')->andThrow(new QueryException('sqlite','private sql',[],new \Exception('secret'))));
        $this->actingAs($this->account())->getJson($this->url())->assertStatus(503)->assertJsonPath('message','La consulta del mapa falló. Intenta nuevamente más tarde.')
            ->assertJsonMissingPath('features')->assertDontSee('secret');
    }
}
