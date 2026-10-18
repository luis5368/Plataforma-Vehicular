<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear Cuenta - InfoVehicular GT</title>
    
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card shadow-lg border-0" style="width: 100%; max-width: 500px;">
        <div class="card-body p-4 p-md-5">
            
            <div class="text-center mb-4">
                <i class="bi bi-car-front-fill text-primary" style="font-size: 3rem;"></i>
                <h2 class="mt-3 fw-bold text-primary">InfoVehicular GT</h2>
                <p class="text-muted">Crea tu cuenta para comenzar</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST">
                @csrf

                <!-- Nombre -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nombre Completo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required autofocus>
                    </div>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Correo -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Tipo de Cuenta (Preseleccionado desde la URL) -->
                <div class="mb-3">
                    <label for="role" class="form-label fw-semibold">Tipo de Cuenta</label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                        <option value="consulta" {{ (request('role') ?? 'consulta') === 'consulta' ? 'selected' : '' }}>
                            👤 Usuario Consultor (Solo verificar vehículos)
                        </option>
                        <option value="registro" {{ request('role') === 'registro' ? 'selected' : '' }}>
                            🏢 Agencia / PNC (Puede reportar vehículos robados)
                        </option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Los usuarios "Registro" pueden agregar vehículos al sistema.</small>
                </div>

                <!-- Contraseña -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Mínimo 8 caracteres</small>
                </div>

                <!-- Confirmar Contraseña -->
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold">Confirmar Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>

                <!-- Botón -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-plus me-2"></i> Crear Cuenta
                    </button>
                </div>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted mb-0">
                    ¿Ya tienes cuenta? 
                    <a href="{{ route('login') }}" class="text-primary fw-bold text-decoration-none">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>

            <hr class="my-4">

            <div class="text-center">
                <p class="text-muted mb-2">O regístrate con:</p>
                <a href="{{ route('auth.google') }}" class="btn btn-outline-danger w-100">
                    <i class="bi bi-google me-2"></i>Continuar con Google
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>