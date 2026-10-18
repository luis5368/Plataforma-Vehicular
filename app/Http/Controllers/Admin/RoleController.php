<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles', ['roles' => Role::withCount('users')->get()]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate(['description' => 'required|string|max:255']);
        DB::transaction(function () use ($request, $role, $data) {
            $old = $role->toArray();
            $role->update($data);
            AuditLog::create(['user_id' => auth()->id(), 'action' => 'update_role', 'table_name' => 'roles',
                'record_id' => $role->id, 'old_values' => $old, 'new_values' => $role->toArray(), 'ip_address' => $request->ip()]);
        });

        return back()->with('success', 'Rol actualizado.');
    }
}
