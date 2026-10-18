<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'InfoVehicular GT')</title>

    @include('layouts.assets')
</head>
<body class="app-page">
<a class="skip-link" href="#main-content">Saltar al contenido</a>

    <!-- Barra de Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}" aria-label="InfoVehicular GT: ir al panel">
                <x-brand :compact="true" />
            </a>
            <div class="navbar-account-tools">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Ver información de mi cuenta">
                    <i class="bi bi-person-circle" aria-hidden="true"></i><span class="account-toggle-label">Cuenta</span>
                </button>
                <form action="{{ route('logout') }}" method="POST" class="navbar-logout">
                    @csrf
                    <button type="submit" class="btn btn-outline-light logout-button">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="bi bi-person-circle"></i>
                            {{ auth()->user()->name }}
                            <span class="badge bg-light text-primary">{{ ucfirst(auth()->user()->role?->name ?? 'Sin rol') }}</span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar / Menú Lateral -->
            <nav class="col-md-2 sidebar py-3" aria-label="Navegación principal">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>

                        @if(auth()->user()->role?->name === 'admin' || auth()->user()->role?->name === 'registro')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vehiculos.*') ? 'active' : '' }}" href="{{ route('vehiculos.index') }}">
                                <i class="bi bi-car-front"></i> Vehículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vehiculos.create') ? 'active' : '' }}" href="{{ route('vehiculos.create') }}">
                                <i class="bi bi-plus-circle"></i> Registrar Vehículo
                            </a>
                        </li>
                        @endif

                        @if(in_array(auth()->user()->role?->name, ['admin','consulta']))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('consultar.*') ? 'active' : '' }}" href="{{ route('consultar.index') }}">
                                <i class="bi bi-search"></i> Consultar VIN
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('consultar.region') ? 'active' : '' }}" href="{{ route('consultar.region') }}">
                                 <i class="bi bi-map"></i> Análisis por Región
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('consultar.historial') ? 'active' : '' }}" href="{{ route('consultar.historial') }}">
                                <i class="bi bi-clock-history"></i> Mi Historial
                            </a>
                        </li>

                        @endif
                        @if(auth()->user()->role?->name === 'admin')
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="{{ route('admin.roles.index') }}"><i class="bi bi-shield-lock" aria-hidden="true"></i> Roles y permisos</a><h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 text-muted text-uppercase">
                                <span>Administración</span>
                            </h6>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.bitacora') ? 'active' : '' }}" href="{{ route('admin.bitacora') }}">
                                <i class="bi bi-journal-text"></i> Bitácora
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <main id="main-content" class="col-md-10 ms-sm-auto content-wrapper">
                @if($errors->any())
                    <div role="alert" class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('status') === 'email-verified')
                    <div role="status" class="alert alert-success"><x-session-status-message :status="session('status')" /></div>
                @endif
                @isset($header)<div class="mb-4">{{ $header }}</div>@endisset
                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->

    @stack('scripts')
</body>
</html>
