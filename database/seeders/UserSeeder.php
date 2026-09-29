<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar el rol de administrador
        $adminRole = Role::where('name', 'admin')->first();

        // Crear un usuario administrador por defecto
        User::create([
            'name' => 'Administrador UMG',
            'email' => 'admin@sistema.gt',
            'password' => Hash::make('password123'), // Contraseña: password123
            'role_id' => $adminRole->id,
        ]);
    }
}