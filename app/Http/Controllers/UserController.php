<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

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

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    // Formulario para editar usuario
    public function edit(User $usuario) // Nota: Laravel usa inyección de modelo
    {
        $roles = Role::all();
        return view('users.edit', compact('usuario', 'roles'));
    }

    // Actualizar usuario
    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $oldValues = $usuario->toArray();

        $usuario->name = $validated['name'];
        $usuario->email = $validated['email'];
        $usuario->role_id = $validated['role_id'];

        if (!empty($validated['password'])) {
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

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    // Eliminar usuario
    public function destroy(User $usuario)
    {
        // Prevenir que el admin se elimine a sí mismo
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete_user',
            'table_name' => 'users',
            'record_id' => $usuario->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado exitosamente.');
    }
}