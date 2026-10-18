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

// Landing Page principal (Estilo SaaS / Profesional)
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Página de elección de registro (Reportar vs Verificar)
Route::get('/register-choice', function () {
    return view('auth.register-choice');
})->name('register.choice');

// Formulario de registro tradicional (GET)
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// Procesamiento del registro tradicional (POST)
Route::post('/register', [AuthController::class, 'register']);

// Rutas de Autenticación Tradicional
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas de recuperación de contraseña
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

Route::post('/forgot-password', function () {
    return back()->with('status', 'Se ha enviado un enlace de recuperación (Simulado para el prototipo).');
})->middleware('guest')->name('password.email');

// Autenticación con Google OAuth
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);


// ======================================================================
// 2. RUTAS PROTEGIDAS (Requieren autenticación)
// ======================================================================
Route::middleware(['auth'])->group(function () {
    
    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard: Todos los roles logueados
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Vehículos: Solo 'admin' y 'registro'
    Route::middleware(['role:admin,registro'])->group(function () {
        Route::resource('vehiculos', VehicleController::class);
    });

    // Módulo de Consultas: Todos los roles autenticados ('admin', 'registro', 'consulta')
    Route::middleware(['role:admin,registro,consulta'])->group(function () {
        Route::get('/consultar', [ConsultationController::class, 'index'])->name('consultar.index');
        Route::get('/consultar/buscar', [ConsultationController::class, 'buscar'])->name('consultar.buscar');
        Route::get('/consulta-region', [ConsultationController::class, 'region'])->name('consultar.region');
        Route::get('/historial', [ConsultationController::class, 'historial'])->name('consultar.historial');
    });

    // Módulo de Administración: Solo 'admin'
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('admin/usuarios', UserController::class)->names('admin.usuarios');
        Route::get('/admin/bitacora', [AuditLogController::class, 'index'])->name('admin.bitacora');
    });
});