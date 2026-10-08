<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - InfoVehicular GT</title>
    
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card shadow-sm border-0" style="width: 100%; max-width: 450px;">
        <div class="card-body p-4 p-md-5">
            
            <!-- Encabezado -->
            <div class="text-center mb-4">
                <i class="bi bi-car-front-fill text-primary" style="font-size: 3.5rem;"></i>
                <h4 class="mt-3 fw-bold">InfoVehicular GT</h4>
                <p class="text-muted small">Plataforma de Registro y Consulta Vehicular</p>
            </div>

            <!-- Mensaje de Estado de Sesión (De Breeze: ej. "Enlace de recuperación enviado") -->
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Errores Generales de Validación -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Formulario de Login -->
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Correo Electrónico -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Contraseña -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required autocomplete="current-password">
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Recordarme y Olvidé mi contraseña -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                        <label class="form-check-label small text-muted" for="remember_me">
                            Recordarme
                        </label>
                    </div>
                    
                    @if (Route::has('password.request'))
                        <a class="small text-primary text-decoration-none fw-semibold" href="{{ route('password.request') }}">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                <!-- Botón de Ingreso -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Ingresar al Sistema
                    </button>
                </div>
            </form>

            <!-- Separador -->
            <hr class="my-4">

            <!-- Botón de Google -->
            <div class="text-center">
                <p class="small text-muted mb-2">O inicia sesión de forma rápida con:</p>
                <a href="{{ route('auth.google') }}" class="btn btn-outline-danger w-100">
                    <i class="bi bi-google"></i> Continuar con Google
                </a>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>