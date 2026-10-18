<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Mostrar el formulario de login
    public function showLogin()
    {
        return view('auth.login');
    }

    // Procesar el login con email y contraseña
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Registrar en la bitácora
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'login',
                'table_name' => 'users',
                'record_id' => Auth::id(),
                'ip_address' => $request->ip(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    // Mostrar el formulario de registro tradicional
    public function showRegister(Request $request)
    {
        // Recibe 'consulta' o 'registro' desde la URL, por defecto 'consulta'
        $selectedRole = $request->query('role', 'consulta');
        return view('auth.register', compact('selectedRole'));
    }

    // Procesar el registro tradicional
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:consulta,registro', // Solo permite estos dos roles
        ]);

        // Buscar el ID del rol seleccionado
        $role = \App\Models\Role::where('name', $validated['role'])->first();

        // Crear el usuario
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role_id' => $role ? $role->id : 3, // 3 es el ID por defecto de 'consulta'
        ]);

        // Iniciar sesión automáticamente
        \Illuminate\Support\Facades\Auth::login($user);

        // Bitácora
        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => 'register',
            'table_name' => 'users',
            'record_id' => $user->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('dashboard')->with('success', '¡Cuenta creada exitosamente! Bienvenido a InfoVehicular GT.');
    }

    // Cerrar sesión
    public function logout(Request $request)
    {
        // Registrar en la bitácora antes de salir
        if (auth()->check()) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'logout',
                'table_name' => 'users',
                'record_id' => Auth::id(),
                'ip_address' => $request->ip(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Redirigir al usuario a Google para autenticación
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Manejar el callback de Google después de la autenticación
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Buscar usuario por email o google_id
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Si no existe, lo creamos con rol de 'consulta' por defecto
                $consultaRole = Role::where('name', 'consulta')->first();
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt('usuario_google'),
                    'role_id' => $consultaRole->id,
                ]);
            } elseif (!$user->google_id) {
                // Si existe pero no tiene google_id, lo vinculamos
                $user->update(['google_id' => $googleUser->getId()]);
            }

            Auth::login($user);

            // Bitácora de login con Google
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login_google',
                'table_name' => 'users',
                'record_id' => $user->id,
                'ip_address' => request()->ip(),
            ]);

            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors('Error al iniciar sesión con Google.');
        }
    }
}