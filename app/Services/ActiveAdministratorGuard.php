<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ActiveAdministratorGuard
{
    // Call inside the mutation transaction. Every account-removal path locks in this order.
    public function lock(User $target, ?int $roleId = null, ?bool $active = null, bool $deleting = false, string $errorBag = 'default'): User
    {
        $adminRole = Role::where('name', 'admin')->lockForUpdate()->first();
        $user = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
        $remainsAdmin = ! $deleting && ($active ?? $user->is_active) && ($roleId ?? $user->role_id) === $adminRole?->id;

        if ($adminRole && $user->is_active && $user->role_id === $adminRole->id && ! $remainsAdmin) {
            $others = User::where('role_id', $adminRole->id)->where('is_active', true)
                ->whereKeyNot($user->id)->orderBy('id')->lockForUpdate()->get(['id']);
            if ($others->isEmpty()) {
                throw ValidationException::withMessages([
                    'account' => 'No puedes desactivar, degradar ni eliminar al último administrador activo. Debe quedar otro administrador activo.',
                ])->errorBag($errorBag);
            }
        }

        return $user;
    }
}
