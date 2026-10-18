<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrador del sistema']);
        Role::firstOrCreate(['name' => 'registro'], ['description' => 'Usuario que registra y actualiza vehículos']);
        Role::firstOrCreate(['name' => 'consulta'], ['description' => 'Usuario que solo consulta información']);
    }
}
