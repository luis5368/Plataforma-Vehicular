<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tu cuenta') · InfoVehicular GT</title>
    @include('layouts.assets')
</head>
<body class="auth-page">
<a class="skip-link" href="#main-content">Saltar al contenido</a>
<header class="auth-header"><a href="{{ route('welcome') }}" aria-label="InfoVehicular GT, inicio"><x-brand :compact="true" /></a><a class="back-link" href="{{ route('welcome') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al inicio</a></header>
<main id="main-content" class="auth-composition">
    <aside class="auth-benefits" aria-label="Herramientas de la plataforma">
        <span class="eyebrow">INFORMACIÓN PARA TU CAMINO</span>
        <h2>Conoce el vehículo.<br><span class="text-accent">Consulta con contexto.</span></h2>
        <ul class="benefit-list">
            <li><i class="bi bi-fingerprint" aria-hidden="true"></i><span><strong>El VIN primero</strong>Identifica el vehículo por su número de identificación.</span></li>
            <li><i class="bi bi-shield-check" aria-hidden="true"></i><span><strong>Estados transparentes</strong>Distingue la fuente, validación y estado de cada reporte.</span></li>
            <li><i class="bi bi-person-lock" aria-hidden="true"></i><span><strong>Tu espacio de trabajo</strong>Accede a las herramientas autorizadas de tu cuenta.</span></li>
        </ul>
    </aside>
    <section class="auth-card">
        <div class="auth-brand"><x-brand /></div>
        @if($errors->any())<div role="alert" class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @if(session('status'))<div role="status" class="alert alert-info"><x-session-status-message :status="session('status')" /></div>@endif
        @yield('auth-content')
    </section>
    <aside class="auth-art" aria-label="Identidad visual vehicular"><x-automotive-scene /></aside>
</main>
<footer class="auth-footer">Información con contexto. La validación de integridad no constituye una confirmación independiente de un robo.</footer>
</body>
</html>
