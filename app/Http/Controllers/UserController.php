<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // Listar usuarios
    public function index()
    {
        $users = User::with('role')->orderBy('created_at', 'desc')->paginate(15);

        return view('users.index', compact('users'));
    }

    // Formulario para crear usuario
    public function create()
    {
        $roles = Role::all();

        return view('users.create', compact('roles'));
    }

    // Guardar nuevo usuario
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', 'different:email', Password::min(12)->letters()->numbers()],
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'],
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create_user',
                'table_name' => 'users',
                'record_id' => $user->id,
                'new_values' => ['name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id],
                'ip_address' => $request->ip(),
            ]);

            return $user;
        });
        event(new Registered($user));

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    // Formulario para editar usuario
    public function edit(User $usuario) // Nota: Laravel usa inyección de modelo
    {
        $roles = Role::all();

        return view('users.edit', compact('usuario', 'roles'));
    }

    public function show(User $usuario)
    {
        return redirect()->route('admin.usuarios.edit', $usuario);
    }

    // Actualizar usuario
    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'required|boolean',
            'password' => ['nullable', 'string', 'confirmed', 'different:email', Password::min(12)->letters()->numbers()],
        ]);

        DB::transaction(function () use ($validated, $request, $usuario) {
            $oldValues = $usuario->toArray();

            $usuario->is_active = $validated['is_active'];
            $usuario->name = $validated['name'];
            if ($usuario->email !== $validated['email']) {
                $usuario->email_verified_at = null;
            }
            $usuario->email = $validated['email'];
            $usuario->role_id = $validated['role_id'];

            if (! empty($validated['password'])) {
                $usuario->password = Hash::make($validated['password']);
            }

            $usuario->save();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update_user',
                'table_name' => 'users',
                'record_id' => $usuario->id,
                'old_values' => $oldValues,
                'new_values' => $usuario->toArray(),
                'ip_address' => $request->ip(),
            ]);

        });

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    // Eliminar usuario
    public function destroy(User $usuario)
    {
        // Prevenir que el admin se elimine a sí mismo
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        DB::transaction(function () use ($usuario) {
            $usuario->update(['is_active' => false]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'deactivate_user',
                'table_name' => 'users',
                'record_id' => $usuario->id,
                'ip_address' => request()->ip(),
            ]);

        });

        return redirect()->route('admin.usuarios.index')->with('success', 'Cuenta desactivada. Sus registros se conservan.');
    }
}
