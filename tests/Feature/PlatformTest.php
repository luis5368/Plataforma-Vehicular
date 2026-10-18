<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function payload(): array
    {
        return ['vin' => '1HGCM82633A000001', 'plate' => 'P100AAA', 'brand' => 'Toyota', 'model' => 'Hilux',
            'department' => 'Guatemala', 'municipality' => 'Guatemala', 'has_theft_report' => '1',
            'theft_report_date' => '2026-01-01', 'theft_description' => 'Private description', 'theft_report_status' => 'validado'];
    }

    public function test_email_signup_requires_verified_identity_for_both_roles(): void
    {
        Notification::fake();
        foreach (['consulta', 'registro'] as $role) {
            Role::firstOrCreate(['name' => $role]);
            $this->get('/register?role='.$role)->assertOk();
            $this->post('/register', ['name' => 'Persona', 'email' => $role.'@example.com', 'role' => $role,
                'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123'])->assertRedirect('/dashboard');
            $user = User::where('email', $role.'@example.com')->firstOrFail();
            $this->assertSame($role, $user->role->name);
            $this->assertNull($user->email_verified_at);
            Notification::assertSentTo($user, VerifyEmail::class);
            $this->get('/dashboard')->assertRedirect('/verify-email');
            $this->get(URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1($user->email)]))->assertRedirect();
            $this->get('/dashboard')->assertOk();
            $this->post('/logout');
        }
    }

    public function test_public_admin_signup_and_missing_roles_are_rejected(): void
    {
        $this->get('/register?role=admin')->assertSessionHasErrors('role');
        $this->get('/register?role=registro');
        $data = ['name' => 'Test', 'email' => 'test@example.com', 'role' => 'admin', 'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123'];
        $this->post('/register', $data)->assertSessionHasErrors('role');
        $data['role'] = 'registro';
        $this->post('/register', $data)->assertSessionHasErrors('role');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_permissions_ownership_and_disabled_accounts(): void
    {
        $owner = $this->account('registro');
        $other = $this->account('registro');
        $this->actingAs($owner)->post('/vehiculos', $this->payload())->assertRedirect();
        $vehicle = Vehicle::firstOrFail();
        $this->assertSame('pendiente_validacion', $vehicle->theftReport->status);
        $this->actingAs($other)->get('/vehiculos/'.$vehicle->id.'/edit')->assertForbidden();
        $this->put('/vehiculos/'.$vehicle->id, $this->payload())->assertForbidden();
        $this->delete('/vehiculos/'.$vehicle->id)->assertForbidden();
        $this->get('/consultar')->assertForbidden();
        $this->actingAs($this->account('consulta'))->post('/vehiculos', $this->payload())->assertForbidden();
        $owner->update(['is_active' => false]);
        $this->actingAs($owner)->get('/vehiculos')->assertForbidden();
    }

    public function test_live_public_results_privacy_normalization_and_statistics(): void
    {
        $owner = $this->account('registro');
        $this->actingAs($owner)->post('/vehiculos', $this->payload())->assertRedirect();
        $this->post('/logout');
        $vehicle = Vehicle::firstOrFail();
        foreach (['pendiente_validacion' => 'Pendiente de validación: no confirmado', 'validado' => 'Reporte validado', 'resuelto' => 'Reporte resuelto', 'rechazado' => 'Reporte rechazado'] as $state => $label) {
            $vehicle->theftReport->update(['status' => $state]);
            $this->post('/consulta-publica', ['vin' => ' 1hgcm82633a000001 '])->assertOk()
                ->assertSee($label)->assertDontSee('Private description')->assertDontSee($owner->email)->assertDontSee($owner->name);
        }
        $this->post('/consulta-publica', ['plate' => ' p-100 aaa '])->assertOk()->assertSee('P100AAA');
        $this->post('/consulta-publica', ['vin' => '1HGCM82633A999999'])->assertOk()->assertSee('No se encontró');
        $this->post('/consulta-publica', ['vin' => 'bad'])->assertSessionHasErrors('vin');
        $this->get('/')->assertViewHas('stats', fn ($stats) => $stats['Vehículos registrados'] === 1 && $stats['Reportes resueltos'] === 0);
        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_missed_searches_are_saved_and_history_is_personal(): void
    {
        $user = $this->account('consulta');
        $this->actingAs($user)->get('/consultar/buscar?vin=1HGCM82633A999999')->assertOk();
        $this->assertDatabaseHas('consultations', ['user_id' => $user->id, 'vehicle_id' => null, 'result' => 'no_encontrado']);
        $this->get('/historial')->assertOk()->assertSee('1HGCM82633A999999');
        $this->actingAs($this->account('consulta'))->get('/historial')->assertDontSee('1HGCM82633A999999');
    }

    public function test_duplicates_lifecycle_and_audited_deletion(): void
    {
        $owner = $this->account('registro');
        $this->actingAs($owner)->post('/vehiculos', $this->payload())->assertRedirect();
        $this->post('/vehiculos', $this->payload())->assertSessionHasErrors(['vin', 'plate']);
        $v = Vehicle::firstOrFail();
        $admin = $this->account('admin');
        foreach (['validado' => 'reportado', 'rechazado' => 'activo', 'resuelto' => 'activo', 'pendiente_validacion' => 'reportado'] as $state => $expected) {
            $this->actingAs($admin)->put('/vehiculos/'.$v->id, array_replace($this->payload(), ['theft_report_status' => $state]))->assertRedirect();
            $this->assertSame($expected, $v->fresh()->status);
            $this->assertSame($state, $v->fresh()->theftReport->status);
        }
        $this->actingAs($owner)->delete('/vehiculos/'.$v->id)->assertRedirect();
        $this->assertDatabaseCount('theft_reports', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'delete', 'record_id' => $v->id]);
    }

    public function test_public_search_rate_limit_and_honeypot(): void
    {
        $this->post('/consulta-publica', ['vin' => '1HGCM82633A999999', 'website' => 'bot'])->assertSessionHasErrors('website');
        for ($i = 0; $i < 9; $i++) {
            $this->post('/consulta-publica', ['vin' => '1HGCM82633A999999'])->assertOk();
        }
        $this->post('/consulta-publica', ['vin' => '1HGCM82633A999999'])->assertStatus(429);
    }

    public function test_google_consulta_registration(): void
    {
        $this->googleRegistration('consulta');
    }

    public function test_google_registro_registration(): void
    {
        $this->googleRegistration('registro');
    }

    private function googleRegistration(string $role): void
    {
        Role::firstOrCreate(['name' => $role]);
        $google = (new \Laravel\Socialite\Two\User)->setRaw(['email_verified' => true])->map(['id' => 'google-'.$role, 'name' => 'Google User', 'email' => $role.'@google.example']);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver = \Mockery::mock());
        $driver->shouldReceive('user')->andReturn($google);
        $this->withSession(['oauth_registration_role' => $role])->get('/auth/google/callback')->assertRedirect('/dashboard');
        $user = User::where('email', $google->email)->firstOrFail();
        $this->assertSame($role, $user->role->name);
        $this->assertNotNull($user->email_verified_at);
        $this->post('/logout');
    }

    public function test_google_unverified_email_cannot_create_account(): void
    {
        $google = (new \Laravel\Socialite\Two\User)->setRaw(['email_verified' => false])->map(['id' => 'google', 'email' => 'bad@example.com']);
        Socialite::shouldReceive('driver->user')->andReturn($google);
        $this->withSession(['oauth_registration_role' => 'registro'])->get('/auth/google/callback')->assertSessionHasErrors();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_does_not_accept_role_escalation_and_blocks_disabled_accounts(): void
    {
        $user = $this->account('consulta');
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'role' => 'admin'])->assertRedirect('/dashboard');
        $this->get('/admin/usuarios')->assertForbidden();
        $this->assertSame('consulta', $user->fresh()->role->name);
        $this->post('/logout');
        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_linking_preserves_existing_role(): void
    {
        $user = $this->account('consulta');
        $google = (new \Laravel\Socialite\Two\User)->setRaw(['email_verified' => true])->map(['id' => 'linked', 'email' => $user->email]);
        Socialite::shouldReceive('driver->user')->andReturn($google);
        $this->withSession(['oauth_registration_role' => 'registro'])->get('/auth/google/callback')->assertRedirect('/dashboard');
        $this->assertSame('consulta', $user->fresh()->role->name);
        $this->assertSame('linked', $user->fresh()->google_id);
    }

    public function test_editing_validated_report_requires_new_review_and_withdrawal_preserves_audit(): void
    {
        $owner = $this->account('registro');
        $this->actingAs($owner)->post('/vehiculos', $this->payload());
        $v = Vehicle::firstOrFail();
        $v->theftReport->update(['status' => 'validado']);
        $this->put('/vehiculos/'.$v->id, array_replace($this->payload(), ['theft_description' => 'Changed details']))->assertRedirect();
        $this->assertSame('pendiente_validacion', $v->fresh()->theftReport->status);
        $this->get('/vehiculos/'.$v->id.'/edit')->assertOk()->assertSee('name="department"', false)->assertSee('Changed details');
        $data = $this->payload();
        unset($data['has_theft_report']);
        $this->put('/vehiculos/'.$v->id, $data)->assertRedirect();
        $this->assertNull($v->fresh()->theftReport);
        $this->assertSame('activo', $v->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'withdraw_report']);
    }

    public function test_admin_views_role_assignment_and_deactivation(): void
    {
        Notification::fake();
        $admin = $this->account('admin');
        $user = $this->account('registro');
        $this->actingAs($admin);
        foreach (['/admin/usuarios', '/admin/usuarios/create', '/admin/usuarios/'.$user->id.'/edit', '/admin/roles', '/admin/bitacora', '/vehiculos', '/vehiculos/create', '/consulta-region'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->put('/admin/usuarios/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id, 'is_active' => 0])->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'update_user', 'record_id' => $user->id]);
    }

    public function test_suspicious_write_pattern_is_limited_and_audited(): void
    {
        $user = $this->account('registro');
        $this->actingAs($user);
        for ($i = 0; $i < 30; $i++) {
            $this->post('/vehiculos', [])->assertSessionHasErrors('vin');
        }
        $this->post('/vehiculos', [])->assertStatus(429);
        $this->assertDatabaseHas('audit_logs', ['action' => 'suspicious_write_rate', 'user_id' => $user->id]);
    }

    public function test_upgrade_normalizes_existing_records_and_downgrades_unreviewed_legacy_reports(): void
    {
        $owner = $this->account('registro');
        $migration = require database_path('migrations/2026_10_18_000001_secure_accounts_and_reports.php');
        $migration->down();
        $v = Vehicle::create(array_merge(collect($this->payload())->only(['vin', 'plate', 'brand', 'model', 'department', 'municipality'])->all(), [
            'vin' => '1hgcm82633a000001', 'plate' => 'P-100 AAA', 'registered_by' => $owner->id, 'status' => 'activo',
        ]));
        TheftReport::create(['vehicle_id' => $v->id, 'reported_by' => $owner->id, 'status' => 'activo', 'report_date' => '2026-01-01', 'description' => 'Legacy unreviewed']);
        $migration->up();
        $this->assertSame('1HGCM82633A000001', $v->fresh()->vin);
        $this->assertSame('P100AAA', $v->fresh()->plate);
        $this->assertSame('reportado', $v->fresh()->status);
        $this->assertSame('pendiente_validacion', $v->fresh()->theftReport->status);
    }

    public function test_legacy_known_credentials_are_retired_without_affecting_real_passwords(): void
    {
        $demo = $this->account('admin');
        $demo->update(['email' => 'admin@sistema.gt', 'password' => 'password123']);
        $google = $this->account('registro');
        $google->update(['google_id' => 'legacy-google', 'password' => 'usuario_google']);
        $real = $this->account('admin');
        $hash = $real->password;
        (require database_path('migrations/2026_10_18_000002_retire_legacy_demo_credentials.php'))->up();
        $this->assertFalse($demo->fresh()->is_active);
        $this->assertTrue($google->fresh()->is_active);
        $this->assertFalse(Hash::check('usuario_google', $google->fresh()->password));
        $this->assertSame($hash, $real->fresh()->password);
        $this->assertDatabaseHas('audit_logs', ['action' => 'retire_demo_credentials', 'record_id' => $demo->id]);
    }

    public function test_vehicle_and_report_are_rolled_back_when_audit_fails(): void
    {
        $owner = $this->account('registro');
        AuditLog::creating(function () {
            throw new \RuntimeException('Audit unavailable');
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($owner)->post('/vehiculos', $this->payload());
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit unavailable', $e->getMessage());
            $this->assertDatabaseCount('vehicles', 0);
            $this->assertDatabaseCount('theft_reports', 0);
        } finally {
            AuditLog::flushEventListeners();
        }
    }
}
