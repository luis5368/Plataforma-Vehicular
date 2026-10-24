<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;

class ActiveSession
{
    public const KEY = 'account_session_token';

    public function start(User $user, Session $session): void
    {
        $token = bin2hex(random_bytes(32));
        DB::transaction(function () use ($user, $token) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->is_active && $locked->role, 403);
            abort_unless(hash_equals($locked->getAuthPassword(), $user->getAuthPassword()), 403);
            $locked->forceFill(['active_session_hash' => hash('sha256', $token), 'remember_token' => null])->save();
        });
        $session->put(self::KEY, $token);
    }

    public function matches(User $user, Session $session): bool
    {
        $token = $session->get(self::KEY);
        $hash = User::whereKey($user->id)->value('active_session_hash');

        return is_string($token) && is_string($hash) && hash_equals($hash, hash('sha256', $token));
    }

    public function forget(User $user, Session $session): void
    {
        $token = $session->get(self::KEY);
        if (is_string($token)) {
            // An old logout cannot erase the new login, even if they race.
            User::whereKey($user->id)->where('active_session_hash', hash('sha256', $token))
                ->update(['active_session_hash' => null, 'remember_token' => null]);
        }
    }
}
