<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Support\VerificationMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Credenciales incorrectas.']);
        }
        if (! $request->user()->is_active || ! $request->user()->role) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['email' => 'Cuenta no disponible.']);
        }
        $request->session()->regenerate();
        $this->audit('login', $request->user());

        return redirect()->route('dashboard');
    }

    public function showRegister(Request $request)
    {
        $request->validate(['role' => 'sometimes|in:consulta,registro']);
        $selectedRole = $request->query('role', 'consulta');
        $request->session()->put('registration_role', $selectedRole);

        return view('auth.register', compact('selectedRole'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'string', 'max:1024', 'confirmed', 'different:email', Password::min(12)->letters()->numbers()],
            'role' => 'required|in:consulta,registro',
        ]);
        $choice = $request->session()->get('registration_role');
        if (! in_array($choice, ['consulta', 'registro'], true) || $choice !== $data['role']) {
            throw ValidationException::withMessages(['role' => 'Selecciona nuevamente el tipo de cuenta.']);
        }
        $role = Role::where('name', $choice)->first();
        if (! $role) {
            throw ValidationException::withMessages(['role' => 'Tipo de cuenta temporalmente no disponible.']);
        }
        $user = DB::transaction(function () use ($data, $role) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role_id' => $role->id, 'is_active' => true]);
            $this->audit('register', $user);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('registration_role');
        if (! VerificationMail::dispatch(fn () => event(new Registered($user)), $user->id)) {
            return redirect()->route('verification.notice')->withErrors(['verification' => __('verification.failed', [], 'es')]);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $this->audit('logout', $request->user());
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function redirectToGoogle(Request $request)
    {
        $choice = $request->session()->get('registration_role');
        $request->session()->put('oauth_registration_role', in_array($choice, ['consulta', 'registro'], true) ? $choice : null);

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            // Socialite validates OAuth state against the session.
            $google = Socialite::driver('google')->user();
            $choice = $request->session()->pull('oauth_registration_role');
            if (($google->user['email_verified'] ?? false) !== true || ! $google->getEmail() || ! $google->getId()) {
                throw new \RuntimeException('Identidad no verificada.');
            }
            $user = DB::transaction(function () use ($google, $choice) {
                $user = User::where('google_id', $google->getId())->orWhere('email', $google->getEmail())->lockForUpdate()->first();
                if ($user) {
                    if (! $user->is_active || ! $user->role || ($user->google_id && $user->google_id !== $google->getId()) || strcasecmp($user->email, $google->getEmail()) !== 0) {
                        throw new \RuntimeException('Cuenta no disponible.');
                    }
                } else {
                    if (! in_array($choice, ['consulta', 'registro'], true)) {
                        throw new \RuntimeException('Selecciona el tipo de cuenta.');
                    }
                    $role = Role::where('name', $choice)->firstOrFail();
                    $user = new User(['name' => $google->getName() ?: 'Usuario', 'email' => $google->getEmail(), 'password' => Str::random(64), 'role_id' => $role->id, 'is_active' => true]);
                }
                $user->google_id = $google->getId();
                $user->email_verified_at ??= now();
                $user->save();
                $this->audit('login_google', $user);

                return $user;
            });
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget('registration_role');

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'No se pudo verificar la cuenta con Google. Reintenta desde el tipo de cuenta elegido.']);
        }
    }

    private function audit(string $action, User $user): void
    {
        AuditLog::create(['user_id' => $user->id, 'action' => $action, 'table_name' => 'users', 'record_id' => $user->id, 'ip_address' => request()->ip()]);
    }
}
