<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $users = DB::table('users')->where('email', 'admin@sistema.gt')->orWhereNotNull('google_id')->get();
            foreach ($users as $user) {
                $demo = $user->email === 'admin@sistema.gt' && Hash::check('password123', $user->password);
                $legacyGoogle = $user->google_id && Hash::check('usuario_google', $user->password);
                if (! $demo && ! $legacyGoogle) {
                    continue;
                }
                DB::table('users')->where('id', $user->id)->update([
                    'password' => Hash::make(Str::random(64)),
                    'is_active' => $demo ? false : $user->is_active,
                    'remember_token' => null, 'updated_at' => now(),
                ]);
                DB::table('audit_logs')->insert([
                    'user_id' => null, 'action' => 'retire_demo_credentials', 'table_name' => 'users',
                    'record_id' => $user->id, 'new_values' => json_encode(['password_rotated' => true, 'account_disabled' => $demo]),
                    'ip_address' => '127.0.0.1', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Never restore a publicly known password or reactivate a retired demo account.
    }
};
