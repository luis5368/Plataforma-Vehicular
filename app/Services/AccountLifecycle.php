<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountLifecycle
{
    // Caller holds the user/administrator locks inside a transaction.
    public function setActive(User $user, bool $active, int $actorId, ?string $ip, ?string $action = null): void
    {
        if ($user->is_active === $active) {
            return;
        }

        $before = $user->is_active;
        $user->is_active = $active;
        $user->remember_token = null;
        $user->active_session_hash = null;
        $user->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->delete();
        }

        AuditLog::create([
            'user_id' => $actorId,
            'action' => $action ?? ($active ? 'reactivate_user' : 'deactivate_user'),
            'table_name' => 'users',
            'record_id' => $user->id,
            'old_values' => ['is_active' => $before],
            'new_values' => ['is_active' => $active],
            'ip_address' => $ip,
        ]);
    }
}
