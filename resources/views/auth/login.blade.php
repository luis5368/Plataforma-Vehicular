@extends('layouts.auth')
@section('title','Iniciar sesión')
@section('auth-content')
<div class="auth-heading"><span class="eyebrow">BIENVENIDO DE NUEVO</span><h1>Inicia sesión</h1><p>Tu plataforma de registro y consulta vehicular.</p></div>
<form method="POST" action="{{ route('login') }}">
@csrf
<div class="mb-4"><label for="email" class="form-label">Correo electrónico</label><div class="input-group"><span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span><input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="tu@correo.com"></div></div>
<div class="mb-3"><label for="password" class="form-label">Contraseña</label><div class="input-group"><span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password" placeholder="Tu contraseña"><button class="btn password-toggle" type="button" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Mostrar contraseña"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
<div class="auth-options"><label class="form-check"><input class="form-check-input" type="checkbox" name="remember" id="remember_me"> Recordarme</label>@if(Route::has('password.request'))<a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>@endif</div>
<button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Ingresar al sistema</button>
</form>
@if(Route::has('auth.google'))<div class="auth-divider"><span>O continúa con</span></div><a href="{{ route('auth.google') }}" class="btn btn-google w-100"><i class="bi bi-google" aria-hidden="true"></i> Continuar con Google</a>@endif
<p class="auth-switch">¿Aún no tienes una cuenta? <a href="{{ route('register.choice') }}">Crear cuenta</a></p>
@endsection
