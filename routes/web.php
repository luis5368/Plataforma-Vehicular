<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\AuditLogController;

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
// Módulo de Consultas: Accesible para 'admin', 'registro' y 'consulta'
    Route::middleware(['role:admin,registro,consulta'])->group(function () {
        Route::get('/consultar', [ConsultationController::class, 'index'])->name('consultar.index');
        Route::get('/consultar/buscar', [ConsultationController::class, 'buscar'])->name('consultar.buscar');
        Route::get('/historial', [ConsultationController::class, 'historial'])->name('consultar.historial');
    });
    // ------------------------------------------------------------------
    // Módulo de Administración: Exclusivo para rol 'admin'
    // ------------------------------------------------------------------
// Módulo de Administración: Solo 'admin'
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('admin/usuarios', UserController::class)->names('admin.usuarios');
        
        // La bitácora la haremos en el siguiente paso
    Route::get('/admin/bitacora', [AuditLogController::class, 'index'])->name('admin.bitacora');
    });
});