<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:create-admin', function () {
    $name = $this->ask('Nombre');
    $email = $this->ask('Correo');
    $password = $this->secret('Contraseña (mínimo 12 caracteres, letras y números)');
    $confirmation = $this->secret('Confirmar contraseña');
    $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], [
        'name' => 'required|string|max:255', 'email' => 'required|email|unique:users',
        'password' => ['required', 'confirmed', 'different:email', Password::min(12)->letters()->numbers()],
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

return 1;
    }
    DB::transaction(function () use ($name, $email, $password) {
        $role = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administración']);
        $user = new User(['name' => $name, 'email' => $email, 'password' => $password, 'role_id' => $role->id, 'is_active' => true]);
        $user->email_verified_at = now();
        $user->save();
        AuditLog::create(['user_id' => $user->id, 'action' => 'create_admin_cli', 'table_name' => 'users', 'record_id' => $user->id, 'ip_address' => '127.0.0.1']);
    });
    $this->info('Administrador creado.');

    return 0;
})->purpose('Crear el primer administrador con contraseña privada e interactiva');
