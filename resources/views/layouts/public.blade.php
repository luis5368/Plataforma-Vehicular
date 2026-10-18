<!doctype html>
<html lang="es" data-bs-theme="dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'InfoVehicular GT · Registro y consulta')</title>@include('layouts.assets')</head>
<body class="public-page">
<a class="skip-link" href="#main-content">Saltar al contenido</a>
<header class="public-header container"><a href="{{ route('welcome') }}" aria-label="InfoVehicular GT, inicio"><x-brand :compact="true" /></a><nav aria-label="Navegación pública"><a href="{{ route('welcome') }}#consulta-publica">Consultar vehículo</a><a href="{{ route('login') }}" class="btn btn-outline-primary">Ingresar <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></nav></header>
<main id="main-content" class="container py-4">
@if($errors->any())<div role="alert" class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('status'))<div role="status" class="alert alert-info"><x-session-status-message :status="session('status')" /></div>@endif
@yield('content')
</main>
<footer class="container public-footer"><x-brand :compact="true" /><p>Información orientativa. Los reportes y su validación de integridad no constituyen confirmación independiente de un robo. La ausencia de reportes no garantiza la situación legal del vehículo.</p><span>GUATEMALA · PLATAFORMA VEHICULAR</span></footer>
</body></html>
