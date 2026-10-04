<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'InfoVehicular GT')</title>
    
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 12px 20px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
        }
        .content-wrapper {
            padding: 20px;
        }
    </style>
</head>
<body>

    <!-- Barra de Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-car-front-fill"></i> InfoVehicular GT
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="bi bi-person-circle"></i> 
                            {{ auth()->user()->name }} 
                            <span class="badge bg-light text-primary">{{ ucfirst(auth()->user()->role->name) }}</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm ms-2">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar / Menú Lateral -->
            <nav class="col-md-2 d-none d-md-block sidebar py-3">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>

                        @if(auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'registro')
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

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('consultar.*') ? 'active' : '' }}" href="{{ route('consultar.index') }}">
                                <i class="bi bi-search"></i> Consultar Placa
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('consultar.historial') ? 'active' : '' }}" href="{{ route('consultar.historial') }}">
                                <i class="bi bi-clock-history"></i> Mi Historial
                            </a>
                        </li>

                        @if(auth()->user()->role->name === 'admin')
                        <li class="nav-item mt-3">
                            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 text-muted text-uppercase">
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
            <main class="col-md-10 ms-sm-auto content-wrapper">
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

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>