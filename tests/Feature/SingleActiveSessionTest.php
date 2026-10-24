<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AccountLifecycle;
use App\Services\ActiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class SingleActiveSessionTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'consulta'): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
    }

    private function browser(): void
    {
        app('session')->driver()->invalidate();
        Auth::forgetGuards();
    }

    private function login(User $user): string
    {
        $this->browser();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');

        return session()->get(ActiveSession::KEY);
    }

    public function test_password_login_replaces_old_session_and_new_session_survives_old_logout(): void
    {
        $user = $this->account('admin');
        $old = $this->login($user);
        $new = $this->login($user);
        $this->assertFalse(hash_equals($old, $new));
        foreach (['/dashboard', '/admin/usuarios', '/consulta-region'] as $url) {
            $this->be($user->fresh())->withSession([ActiveSession::KEY => $old])->get($url)
                ->assertRedirect('/login')->assertSessionHas('session-replaced');
        }
        $this->be($user->fresh())->withSession([ActiveSession::KEY => $old])->post('/logout')->assertRedirect('/login');
        $this->assertTrue(hash_equals(hash('sha256', $new), $user->fresh()->active_session_hash));
        $this->be($user->fresh())->withSession([ActiveSession::KEY => $new])->get('/dashboard')->assertOk();
        $this->get('/dashboard')->assertOk(); // Another tab uses the same cookie/store.
        $this->get('/admin/usuarios')->assertOk();
        $this->post('/logout')->assertRedirect('/');
        $this->assertNull($user->fresh()->active_session_hash);
        $this->get('/login')->assertOk();
    }

    public function test_google_callback_also_replaces_session_without_contacting_provider(): void
    {
        $user = $this->account();
        $old = $this->login($user);
        $this->browser();
        $google = (new \Laravel\Socialite\Two\User)->setRaw(['email_verified' => true])->map(['id' => 'test-provider-id', 'email' => $user->email]);
        Socialite::shouldReceive('driver->user')->once()->andReturn($google);
        $this->get('/auth/google/callback')->assertRedirect('/dashboard');
        $new = session()->get(ActiveSession::KEY);
        $this->assertTrue(is_string($new) && ! hash_equals($old, $new));
        $this->be($user->fresh())->withSession([ActiveSession::KEY => $old])->get('/consulta-region')->assertRedirect('/login');
        $this->be($user->fresh())->withSession([ActiveSession::KEY => $new])->get('/consulta-region')->assertOk();
    }

    public function test_distinct_accounts_are_independent_on_different_session_stores(): void
    {
        $first = $this->account();
        $second = $this->account('registro');
        $one = new Store('one', new ArraySessionHandler(120));
        $two = new Store('two', new ArraySessionHandler(120));
        $service = app(ActiveSession::class);
        $service->start($first, $one);
        $service->start($second, $two);
        $this->assertTrue($service->matches($first, $one));
        $this->assertTrue($service->matches($second, $two));
        $service->start($first, new Store('other', new ArraySessionHandler(120)));
        $this->assertFalse($service->matches($first, $one));
        $this->assertTrue($service->matches($second, $two));
        $this->assertArrayNotHasKey('active_session_hash', $first->fresh()->toArray());
    }

    public function test_legacy_or_recalled_session_cannot_claim_a_new_binding(): void
    {
        $user = $this->account();
        $new = $this->login($user);
        $this->be($user->fresh())->withSession([ActiveSession::KEY => null])->get('/dashboard')->assertRedirect('/login');
        $this->assertTrue(hash_equals(hash('sha256', $new), $user->fresh()->active_session_hash));
    }

    public function test_deactivation_and_reactivation_revoke_bindings_on_non_database_driver(): void
    {
        $user = $this->account();
        $token = $this->login($user);
        DB::transaction(fn () => app(AccountLifecycle::class)->setActive($user->fresh(), false, $user->id, '127.0.0.1'));
        $this->assertNull($user->fresh()->active_session_hash);
        DB::transaction(fn () => app(AccountLifecycle::class)->setActive($user->fresh(), true, $user->id, '127.0.0.1'));
        $this->be($user->fresh())->withSession([ActiveSession::KEY => $token])->get('/consulta-region')->assertRedirect('/login');
    }

    public function test_changing_password_keeps_current_browser_and_revokes_previous_binding(): void
    {
        $user = $this->account();
        $old = $this->login($user);
        $this->put('/password', ['current_password' => 'password', 'password' => 'ChangedSecure123!', 'password_confirmation' => 'ChangedSecure123!'])->assertSessionHasNoErrors();
        $this->assertFalse(hash_equals(hash('sha256', $old), $user->fresh()->active_session_hash));
        $this->get('/dashboard')->assertOk();
    }

    public function test_password_reset_invalidates_the_current_binding(): void
    {
        $user = $this->account();
        $old = $this->login($user);
        $reset = Password::broker()->createToken($user);
        $this->browser();
        $this->post('/reset-password', ['token' => $reset, 'email' => $user->email,
            'password' => 'ChangedSecure123!', 'password_confirmation' => 'ChangedSecure123!'])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->active_session_hash);
        $this->be($user->fresh())->withSession([ActiveSession::KEY => $old])->get('/dashboard')->assertRedirect('/login');
    }
}
