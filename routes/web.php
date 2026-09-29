<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;

// ======================================================================
// 1. RUTAS PÚBLICAS (No requieren autenticación)
// ======================================================================

// Página de inicio (Landing page)
Route::get('/', function () {
    return view('auth.login');
})->name('home');

// Autenticación tradicional (Email y Contraseña)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Autenticación con Google (Socialite)
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);


// ======================================================================
// 2. RUTAS PROTEGIDAS (Requieren estar autenticado: middleware 'auth')
// ======================================================================
Route::middleware(['auth'])->group(function () {
    
    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard: Accesible para TODOS los roles autenticados
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------------------------------------------------
    // Módulo de Vehículos: Solo roles 'admin' y 'registro'
    // ------------------------------------------------------------------
    Route::middleware(['role:admin,registro'])->group(function () {
        Route::resource('vehiculos', VehicleController::class);
    });

    // ------------------------------------------------------------------
    // Módulo de Consultas: Accesible para 'admin', 'registro' y 'consulta'
    // (Preparado para el Sprint 5: Consulta por Placa y Alertas)
    // ------------------------------------------------------------------
    Route::middleware(['role:admin,registro,consulta'])->group(function () {
        Route::get('/consultar', function () {
            return view('consultations.search'); // Vista que crearemos a continuación
        })->name('consultar.index');

        Route::get('/historial', function () {
            return 'Aquí irá el historial de consultas del usuario (Próximo Sprint)';
        })->name('consultar.historial');
    });

    // ------------------------------------------------------------------
    // Módulo de Administración: Exclusivo para rol 'admin'
    // ------------------------------------------------------------------
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/usuarios', function () {
            return 'Aquí irá la gestión de usuarios y roles (Próximo Sprint)';
        })->name('admin.usuarios');

        Route::get('/admin/bitacora', function () {
            return 'Aquí irá la visualización de la bitácora de auditoría (Próximo Sprint)';
        })->name('admin.bitacora');
    });
});