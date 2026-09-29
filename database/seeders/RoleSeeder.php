<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'admin', 'description' => 'Administrador del sistema']);
        Role::create(['name' => 'registro', 'description' => 'Usuario que registra y actualiza vehículos']);
        Role::create(['name' => 'consulta', 'description' => 'Usuario que solo consulta información']);
    }
}