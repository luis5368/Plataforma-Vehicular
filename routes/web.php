<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// ======================================================================
// 1. RUTAS PÚBLICAS (No requieren autenticación)
// ======================================================================

// Landing Page principal (Estilo SaaS / Profesional)
Route::get('/', [PublicSearchController::class, 'index'])->name('welcome');
Route::post('/consulta-publica', [PublicSearchController::class, 'search'])->middleware('throttle:10,1')->name('public.search');

// Página de elección de registro (Reportar vs Verificar)
Route::middleware('guest')->group(function () {
    Route::get('/register-choice', function () {
        return view('auth.register-choice');
    })->name('register.choice');

    // Formulario de registro tradicional (GET)
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

    // Procesamiento del registro tradicional (POST)
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    // Rutas de Autenticación Tradicional
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Rutas de recuperación de contraseña
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    // Autenticación con Google OAuth
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

// ======================================================================
// 2. RUTAS PROTEGIDAS (Requieren autenticación)
// ======================================================================
Route::middleware(['auth', 'active', 'verified'])->group(function () {

    // Dashboard: Todos los roles logueados
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Vehículos: Solo 'admin' y 'registro'
    Route::middleware(['role:admin,registro'])->group(function () {
        Route::resource('vehiculos', VehicleController::class)->middleware('throttle:60,1')->middlewareFor(['store', 'update', 'destroy'], 'throttle:vehicle-writes');
    });

    // Consulta autenticada: separada de las capacidades de registro.
    Route::middleware(['role:admin,consulta'])->group(function () {
        Route::get('/consultar', [ConsultationController::class, 'index'])->name('consultar.index');
        Route::get('/consultar/buscar', [ConsultationController::class, 'buscar'])->name('consultar.buscar');
        Route::get('/consulta-region', [ConsultationController::class, 'region'])->middleware('throttle:regional-pages')->name('consultar.region');
        Route::get('/consulta-region/puntos', [ConsultationController::class, 'map'])->middleware('throttle:regional-map')->name('consultar.region.map');
        Route::get('/historial', [ConsultationController::class, 'historial'])->name('consultar.historial');
    });

    // Módulo de Administración: Solo 'admin'
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/roles', [RoleController::class, 'index'])->name('admin.roles.index');
        Route::patch('/admin/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
        Route::resource('admin/usuarios', UserController::class)->names('admin.usuarios');
        Route::get('/admin/bitacora', [AuditLogController::class, 'index'])->name('admin.bitacora');
    });
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/verify-email', EmailVerificationPromptController::class)->middleware('active')->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['active', 'signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware(['active', 'throttle:6,1'])->name('verification.send');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
