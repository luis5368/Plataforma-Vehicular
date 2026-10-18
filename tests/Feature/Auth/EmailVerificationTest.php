<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $verified = false): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
            'email_verified_at' => $verified ? now() : null,
            'is_active' => true,
        ]);
    }

    public function test_unverified_admin_cannot_bypass_verification(): void
    {
        $user = $this->admin();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
        $this->get('/admin/usuarios')->assertRedirect(route('verification.notice'));
        $this->get('/verify-email')->assertOk()->assertSee('Verifica tu correo')->assertDontSee('/admin/usuarios');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verified_active_admin_can_log_in_and_use_administration_navigation(): void
    {
        $user = $this->admin();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
        $this->actingAs($user)->get($url)->assertRedirect('/dashboard?verified=1')->assertSessionHas('status', 'email-verified');
        $this->get('/dashboard')->assertSee('Tu correo ya está verificado');
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee(route('admin.usuarios.index'))
            ->assertSee(route('admin.roles.index'))->assertSee(route('admin.bitacora'));
        foreach (['/admin/usuarios', '/admin/roles', '/admin/bitacora'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_non_admin_accounts_do_not_see_or_access_admin_operations(): void
    {
        foreach (['consulta', 'registro'] as $role) {
            $user = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
            $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee(route('admin.usuarios.index'))
                ->assertDontSee(route('admin.roles.index'))->assertDontSee(route('admin.bitacora'));
            $this->get('/admin/usuarios')->assertForbidden();
            $this->get('/admin/roles')->assertForbidden();
            $this->get('/admin/bitacora')->assertForbidden();
        }
        $admin = $this->admin(true);
        $admin->update(['is_active' => false]);
        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
    }

    public function test_resend_uses_standard_notification_and_shows_spanish_success(): void
    {
        Notification::fake();
        config(['mail.default' => 'smtp']);
        $user = $this->admin();
        $this->actingAs($user)->from('/verify-email')->post(route('verification.send'))
            ->assertRedirect('/verify-email')->assertSessionHasNoErrors()->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class, fn ($notification) => $notification->locale === 'es');
        $this->get('/verify-email')->assertSee('El mensaje de verificación se entregó al servicio de correo')
            ->assertDontSee('verification-link-sent');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_log_and_array_modes_do_not_claim_inbox_delivery(): void
    {
        Notification::fake();
        $this->actingAs($this->admin());
        foreach (['log' => 'verification-link-logged', 'array' => 'verification-link-local'] as $mailer => $status) {
            config(['mail.default' => $mailer]);
            $this->from('/verify-email')->post(route('verification.send'))->assertSessionHas('status', $status);
            $this->get('/verify-email')->assertSee('No se envió a una bandeja')->assertDontSee($status);
        }
    }

    public function test_transport_failure_is_sanitized_and_does_not_report_success(): void
    {
        $user = $this->admin();
        config(['mail.default' => 'smtp', 'app.debug' => true]);
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('smtp://secret-user:private-password@private-host'));
        Log::shouldReceive('warning')->once()->with('No se pudo enviar la notificación de verificación.', [
            'user_id' => $user->id, 'exception_type' => \RuntimeException::class,
        ]);
        $this->actingAs($user)->withSession(['status' => 'verification-link-sent'])->from('/verify-email')->post(route('verification.send'))
            ->assertRedirect('/verify-email')->assertSessionHasErrors('verification')->assertSessionMissing('status');
        $this->get('/verify-email')->assertSee('No pudimos enviar el correo de verificación')
            ->assertDontSee('private-password')->assertDontSee('secret-user')->assertDontSee('private-host');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_is_rate_limited_and_protected_by_authentication(): void
    {
        $this->post(route('verification.send'))->assertRedirect('/login');
        Notification::fake();
        $user = $this->admin();
        $this->actingAs($user);
        for ($i = 0; $i < 6; $i++) {
            $this->from('/verify-email')->post(route('verification.send'))->assertRedirect('/verify-email');
        }
        $this->post(route('verification.send'))->assertStatus(429)->assertHeader('Retry-After');
        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    public function test_already_verified_account_does_not_receive_another_notification(): void
    {
        Notification::fake();
        $this->actingAs($this->admin(true))->post(route('verification.send'))->assertRedirect('/dashboard');
        Notification::assertNothingSent();
    }

    public function test_registration_mail_failure_keeps_the_account_unverified_and_allows_resend(): void
    {
        Role::firstOrCreate(['name' => 'consulta']);
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('private-smtp-detail'));
        Log::spy();
        $this->get('/register?role=consulta');
        $this->post('/register', [
            'name' => 'Persona de prueba', 'email' => 'verification-test@example.com', 'role' => 'consulta',
            'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123',
        ])->assertRedirect(route('verification.notice'))->assertSessionHasErrors('verification');
        $this->assertAuthenticated();
        $this->assertNull(User::where('email', 'verification-test@example.com')->firstOrFail()->email_verified_at);
        $this->get('/verify-email')->assertOk()->assertSee('No pudimos enviar el correo')
            ->assertSee('Reenviar enlace')->assertDontSee('private-smtp-detail');
    }

    public function test_expired_verification_link_cannot_verify_an_admin(): void
    {
        $user = $this->admin();
        $url = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_log_transport_really_generates_a_local_message_without_inbox_delivery(): void
    {
        $user = $this->admin();
        config(['mail.default' => 'log']);
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('debug')->once()->with(\Mockery::on(fn ($message) => str_contains($message, '/verify-email/'.$user->id.'/') &&
            str_contains($message, 'signature=') && str_contains($message, 'expires=')
        ));
        $this->app->instance(LoggerInterface::class, $logger);
        $this->actingAs($user)->from('/verify-email')->post(route('verification.send'))
            ->assertRedirect('/verify-email')->assertSessionHasNoErrors()->assertSessionHas('status', 'verification-link-logged');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_standard_verification_email_is_in_spanish_with_a_signed_expiring_link(): void
    {
        Notification::fake();
        config(['app.locale' => 'en']);
        $user = $this->admin();
        $this->actingAs($user)->from('/verify-email')->post(route('verification.send'));
        $notification = Notification::sent($user, VerifyEmail::class)->sole();
        $locale = app()->getLocale();
        try {
            app()->setLocale($notification->locale);
            $message = $notification->toMail($user);
            $rendered = (string) $message->render();
        } finally {
            app()->setLocale($locale);
        }
        $this->assertSame('Verifica tu correo electrónico', $message->subject);
        $this->assertSame('Verifica tu correo electrónico', $message->actionText);
        $this->assertStringContainsString('¡Hola!', $rendered);
        $this->assertStringContainsString('Saludos,', $rendered);
        $this->assertStringNotContainsString("If you're having trouble", $rendered);
        $this->assertStringContainsString('signature=', $message->actionUrl);
        $this->assertStringContainsString('expires=', $message->actionUrl);
        $this->actingAs($user)->get($message->actionUrl)->assertRedirect();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
