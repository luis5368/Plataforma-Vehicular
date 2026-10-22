<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function vehicle(User $owner): Vehicle
    {
        $this->actingAs($owner)->post('/vehiculos', $this->payload())->assertSessionHasNoErrors();

        return Vehicle::firstOrFail();
    }

    private function payload(): array
    {
        return ['vin' => '1HGCM82633A000001', 'plate' => 'P101AAA', 'brand' => 'Toyota', 'model' => 'Hilux',
            'department' => 'Guatemala', 'municipality' => 'Mixco', 'theft_report_address' => 'Ubicación declarada',
            'has_theft_report' => '1', 'theft_report_date' => now()->subDay()->toDateString(),
            'theft_description' => 'Descripción detallada del reporte', 'evidence_reference' => 'REF-TEST'];
    }

    public function test_registry_deletes_only_its_own_open_report_and_audits_it(): void
    {
        $owner = $this->account('registro');
        $v = $this->vehicle($owner);
        $this->assertSame('automatica', $v->theftReport->validation_method);
        $this->delete('/vehiculos/'.$v->id)->assertRedirect();
        $this->assertDatabaseMissing('vehicles', ['id' => $v->id]);
        $this->assertDatabaseCount('theft_reports', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'delete', 'record_id' => $v->id, 'user_id' => $owner->id]);
    }

    public static function closedCases(): array
    {
        return [['resuelto'], ['retirado']];
    }

    #[DataProvider('closedCases')]
    public function test_registry_cannot_delete_closed_cases_but_admin_can(string $state): void
    {
        $owner = $this->account('registro');
        $v = $this->vehicle($owner);
        $v->theftReport->forceFill(['case_status' => $state, 'status' => $state])->save();
        $this->delete('/vehiculos/'.$v->id)->assertForbidden();
        $this->assertDatabaseHas('theft_reports', ['vehicle_id' => $v->id, 'case_status' => $state]);
        $this->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertFalse($owner->fresh()->is_active);
        $this->assertDatabaseHas('theft_reports', ['vehicle_id' => $v->id, 'case_status' => $state]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->actingAs($this->account('admin'))->delete('/vehiculos/'.$v->id)->assertRedirect();
        $this->assertDatabaseMissing('vehicles', ['id' => $v->id]);
    }

    public function test_registry_cannot_access_or_mutate_other_owners_records(): void
    {
        $owner = $this->account('registro');
        $v = $this->vehicle($owner);
        $other = $this->account('registro');
        $this->actingAs($other)->get('/vehiculos')->assertOk()->assertDontSee($v->vin);
        $this->get('/vehiculos/'.$v->id)->assertForbidden();
        $this->get('/vehiculos/'.$v->id.'/edit')->assertForbidden();
        $this->put('/vehiculos/'.$v->id, $this->payload())->assertForbidden();
        $this->delete('/vehiculos/'.$v->id)->assertForbidden();
        $this->get('/consultar')->assertForbidden();
        $this->assertDatabaseHas('vehicles', ['id' => $v->id, 'registered_by' => $owner->id]);
    }

    public function test_registry_cannot_delete_another_authors_report_even_on_its_own_vehicle(): void
    {
        $owner = $this->account('registro');
        $v = $this->vehicle($owner);
        $v->theftReport->update(['reported_by' => $this->account('admin')->id]);
        $this->actingAs($owner)->delete('/vehiculos/'.$v->id)->assertForbidden();
        $this->assertDatabaseHas('vehicles', ['id' => $v->id]);
    }

    public function test_consulta_keeps_detailed_read_access_without_write_permissions(): void
    {
        $v = $this->vehicle($this->account('registro'));
        $this->actingAs($this->account('consulta'))->get('/consultar/buscar?vin='.$v->vin)->assertOk()
            ->assertSee($v->vin)->assertSee('Hilux')->assertSee('Descripción detallada del reporte');
        $this->get('/consulta-region')->assertOk();
        $this->get('/historial')->assertOk();
        $this->get('/vehiculos/create')->assertForbidden();
        $this->post('/vehiculos', $this->payload())->assertForbidden();
        $this->get('/vehiculos/'.$v->id.'/edit')->assertForbidden();
        $this->put('/vehiculos/'.$v->id, $this->payload() + ['review_action' => 'resolver', 'review_reason' => 'No autorizado'])->assertForbidden();
        $this->delete('/vehiculos/'.$v->id)->assertForbidden();
        $this->assertDatabaseHas('theft_reports', ['vehicle_id' => $v->id, 'case_status' => 'abierto']);
    }

    public function test_admin_routes_return_403_for_both_non_admin_roles(): void
    {
        $target = $this->account('consulta');
        $role = Role::firstOrCreate(['name' => 'admin']);
        foreach (['consulta', 'registro'] as $name) {
            $this->actingAs($this->account($name));
            foreach (['/admin/usuarios', '/admin/usuarios/create', '/admin/usuarios/'.$target->id,
                '/admin/usuarios/'.$target->id.'/edit', '/admin/roles', '/admin/bitacora'] as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post('/admin/usuarios', [])->assertForbidden();
            $this->put('/admin/usuarios/'.$target->id, [])->assertForbidden();
            $this->delete('/admin/usuarios/'.$target->id)->assertForbidden();
            $this->patch('/admin/roles/'.$role->id, [])->assertForbidden();
        }
    }

    public static function administratorChanges(): array
    {
        $cases = [];
        foreach (['deactivate_edit', 'demote_edit', 'administrative_delete', 'profile_delete'] as $action) {
            $cases[$action.'_last'] = [$action, false];
            $cases[$action.'_with_other'] = [$action, true];
        }

        return $cases;
    }

    #[DataProvider('administratorChanges')]
    public function test_every_user_and_profile_removal_path_preserves_an_active_admin(string $action, bool $hasOther): void
    {
        $admin = $this->account('admin');
        $other = $this->account('admin');
        $other->update(['is_active' => $hasOther]);
        $consultaRole = Role::firstOrCreate(['name' => 'consulta']);
        $this->actingAs($admin);
        if ($action === 'profile_delete') {
            $response = $this->deleteJson('/profile', ['password' => 'password']);
        } elseif ($action === 'administrative_delete') {
            $response = $this->deleteJson('/admin/usuarios/'.$admin->id);
        } else {
            $response = $this->putJson('/admin/usuarios/'.$admin->id, [
                'name' => $admin->name, 'email' => $admin->email,
                'role_id' => $action === 'demote_edit' ? $consultaRole->id : $admin->role_id,
                'is_active' => $action === 'deactivate_edit' ? 0 : 1,
            ]);
        }
        if (! $hasOther) {
            $response->assertUnprocessable()->assertJsonValidationErrors('account');
            $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true, 'role_id' => $admin->role_id]);
            $this->assertAuthenticated();
            $this->assertDatabaseMissing('audit_logs', ['action' => 'deactivate_user', 'record_id' => $admin->id]);
            $this->assertDatabaseMissing('audit_logs', ['action' => 'delete_account', 'record_id' => $admin->id]);
        } else {
            $response->assertRedirect();
            if ($action === 'profile_delete') {
                $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => false]);
                $this->assertGuest();
            } elseif ($action === 'demote_edit') {
                $this->assertDatabaseHas('users', ['id' => $admin->id, 'role_id' => $consultaRole->id]);
            } else {
                $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => false]);
            }
            $this->assertDatabaseHas('users', ['id' => $other->id, 'is_active' => true, 'role_id' => $other->role_id]);
        }
        $this->assertSame(1, User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->where('is_active', true)->count());
    }

    public function test_profile_explains_last_admin_rejection_without_logging_out(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->from('/profile')->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/profile')->assertSessionHasErrorsIn('userDeletion', 'account');
        $this->assertAuthenticatedAs($admin);
        $this->get('/profile')->assertOk()->assertSee('último administrador activo');
    }
}
