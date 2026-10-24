<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Role;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'registro'): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function payload(): array
    {
        return ['vin' => '1HGCM82633A000001', 'plate' => 'P101AAA', 'brand' => 'Toyota', 'model' => 'Hilux',
            'department' => 'Guatemala', 'municipality' => 'Mixco', 'theft_report_address' => 'Lugar declarado',
            'has_theft_report' => '1', 'theft_report_date' => now()->subDay()->toDateString(),
            'theft_description' => 'Descripción declarada', 'evidence_reference' => 'REF-TEST'];
    }

    public function test_profile_deactivation_preserves_attribution_history_and_revokes_access(): void
    {
        $user = $this->account();
        $user->forceFill(['remember_token' => 'previous-token'])->save();
        $this->actingAs($user)->post('/vehiculos', $this->payload())->assertSessionHasNoErrors();
        $vehicle = Vehicle::firstOrFail();
        $report = $vehicle->theftReport;
        $consultation = Consultation::create(['user_id' => $user->id, 'vehicle_id' => $vehicle->id, 'consultation_date' => now(), 'result' => 'con_reporte_activo']);
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $sessionBinding = session()->get(ActiveSession::KEY);
        config(['session.driver' => 'database']);
        $this->withSession([ActiveSession::KEY => $sessionBinding]);
        $history = AuditLog::pluck('id')->all();
        $oldSession = session()->getId();
        $oldCsrf = session()->token();
        $this->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertGuest();
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNotSame($oldCsrf, session()->token());
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertSame($user->id, $consultation->fresh()->user_id);
        $this->assertNotSame('previous-token', $user->fresh()->remember_token);
        $this->assertSame($user->id, $vehicle->fresh()->registered_by);
        $this->assertSame($user->id, $report->fresh()->reported_by);
        $this->assertSame($user->id, $report->fresh()->validated_by);
        $this->assertSame(count($history), AuditLog::whereIn('id', $history)->count());
        $audit = AuditLog::where('action', 'deactivate_account')->firstOrFail();
        $this->assertSame(['is_active' => true], $audit->old_values);
        $this->assertSame(['is_active' => false], $audit->new_values);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user->fresh());
        foreach (['/profile', '/dashboard', '/verify-email', '/vehiculos'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/email/verification-notification')->assertForbidden();
        $this->patch('/profile', ['is_active' => true])->assertForbidden();
    }

    public function test_only_admin_reactivates_with_minimal_audit_and_database_session_revocation(): void
    {
        $user = $this->account();
        $user->forceFill(['is_active' => false, 'remember_token' => 'old-token'])->save();
        $admin = $this->account('admin');
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        config(['session.driver' => 'database']);
        $payload = ['name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id, 'is_active' => 1];
        foreach (['consulta', 'registro'] as $role) {
            $this->actingAs($this->account($role))->put('/admin/usuarios/'.$user->id, $payload)->assertForbidden();
        }
        $this->actingAs($admin)->put('/admin/usuarios/'.$user->id, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($user->fresh()->is_active);
        $this->assertNull($user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $audit = AuditLog::where('action', 'reactivate_user')->firstOrFail();
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame($user->id, $audit->record_id);
        $this->assertSame(['is_active' => true], $audit->new_values);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_report_metadata_cannot_be_forged_on_create_or_update(): void
    {
        $user = $this->account();
        $other = $this->account('admin');
        $forged = ['registered_by' => $other->id, 'reported_by' => $other->id, 'updated_by' => $other->id,
            'validated_by' => $other->id, 'validated_at' => '2000-01-01', 'validation_method' => 'administrativa',
            'validation_status' => 'rechazado', 'case_status' => 'resuelto'];
        $this->actingAs($user)->post('/vehiculos', $this->payload() + $forged)->assertSessionHasNoErrors();
        $report = TheftReport::firstOrFail();
        $this->travel(2)->minutes();
        $this->put('/vehiculos/'.$report->vehicle_id, array_replace($this->payload(), ['theft_description' => 'Descripción corregida']) + $forged)->assertSessionHasNoErrors();
        $report->refresh();
        $this->assertSame($user->id, $report->reported_by);
        $this->assertSame($user->id, $report->updated_by);
        $this->assertSame($user->id, $report->validated_by);
        $this->assertSame($user->id, $report->vehicle->registered_by);
        $this->assertSame('automatica', $report->validation_method);
        $this->assertSame('validado', $report->validation_status);
        $this->assertSame('abierto', $report->case_status);
        $this->assertTrue($report->validated_at->equalTo(now()->startOfSecond()));
        $this->assertDatabaseHas('audit_logs', ['action' => 'update_report', 'user_id' => $user->id]);
    }
}
