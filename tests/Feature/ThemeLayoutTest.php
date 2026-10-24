<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function assertTheme(string $url): void
    {
        $response = $this->get($url)->assertOk()
            ->assertSee('<html lang="es" data-bs-theme="dark">', false)
            ->assertDontSee('data-theme-toggle', false)
            ->assertDontSee('infovehiculargt.theme', false)
            ->assertDontSee('prefers-color-scheme', false);
    }

    public function test_public_and_authentication_layouts_use_original_fixed_dark_theme(): void
    {
        foreach (['/', '/login', '/register-choice', '/register?role=consulta', '/forgot-password'] as $url) {
            $this->assertTheme($url);
        }
    }

    public function test_all_roles_use_original_fixed_dark_theme_in_their_screens(): void
    {
        foreach (['admin', 'consulta', 'registro'] as $role) {
            $user = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
            $this->actingAs($user);
            $this->assertTheme('/dashboard');
            $this->assertTheme('/profile');
            if ($role !== 'registro') {
                $this->assertTheme('/consulta-region');
                $this->assertTheme('/consultar');
                $this->assertTheme('/historial');
            }
            if ($role !== 'consulta') {
                $this->assertTheme('/vehiculos/create');
                $this->assertTheme('/vehiculos');
            }
            if ($role === 'admin') {
                foreach (['/admin/usuarios', '/admin/roles', '/admin/bitacora'] as $url) {
                    $this->assertTheme($url);
                }
            }
        }
    }
}
