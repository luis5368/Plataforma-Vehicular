<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\AuditLogController;

// ======================================================================
// 1. RUTAS PÚBLICAS
// ======================================================================

// Página de inicio: Redirige al login para mayor seguridad en este prototipo
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rutas de recuperación de contraseña (Agregadas por Breeze, ahora sí funcionarán)
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

Route::post('/forgot-password', function () {
    // Por ahora, solo redirigimos con un mensaje de éxito para el prototipo
    return back()->with('status', 'Se ha enviado un enlace de recuperación (Simulado para el prototipo).');
})->middleware('guest')->name('password.email');

// Autenticación con Google
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);


// ======================================================================
// 2. RUTAS PROTEGIDAS (Requieren autenticación)
// ======================================================================
Route::middleware(['auth'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard: Todos los logueados
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Vehículos: Solo 'admin' y 'registro'
    Route::middleware(['role:admin,registro'])->group(function () {
        Route::resource('vehiculos', VehicleController::class);
    });

    // Módulo de Consultas: Todos los roles autenticados
    Route::middleware(['role:admin,registro,consulta'])->group(function () {
        Route::get('/consultar', [ConsultationController::class, 'index'])->name('consultar.index');
        Route::get('/consultar/buscar', [ConsultationController::class, 'buscar'])->name('consultar.buscar');
        Route::get('/historial', [ConsultationController::class, 'historial'])->name('consultar.historial');
        Route::get('/consulta-region', [ConsultationController::class, 'region'])->name('consultar.region');
    });

    // Módulo de Administración: Solo 'admin'
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('admin/usuarios', UserController::class)->names('admin.usuarios');
        Route::get('/admin/bitacora', [AuditLogController::class, 'index'])->name('admin.bitacora');
    });
});