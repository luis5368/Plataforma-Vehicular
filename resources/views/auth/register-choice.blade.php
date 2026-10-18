<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - InfoVehicular GT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        
        .choice-container {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 3rem;
            max-width: 800px;
            width: 100%;
        }
        
        .choice-card {
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
        }
        
        .choice-card:hover {
            border-color: #0d6efd;
            transform: translateY(-5px);
            background: rgba(13, 110, 253, 0.1);
        }
        
        .choice-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .choice-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .choice-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .choice-description {
            color: rgba(255,255,255,0.7);
            line-height: 1.6;
        }
        
        .btn-choice {
            margin-top: 1.5rem;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .back-link {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .back-link:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="choice-container">
        <a href="{{ route('welcome') }}" class="back-link">
            <i class="bi bi-arrow-left me-2"></i> Volver al inicio
        </a>
        
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-white mb-3">
                <i class="bi bi-car-front-fill text-primary me-2"></i>InfoVehicular GT
            </h1>
            <p class="lead text-white-50">¿Qué deseas realizar?</p>
            <p class="text-white-50">Selecciona una opción para continuar con tu registro</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="choice-card" onclick="window.location.href='{{ route('register', ['type' => 'report']) }}'">
                    <div class="choice-icon">
                        <i class="bi bi-car-front"></i>
                    </div>
                    <h3 class="choice-title">Reportar Vehículo</h3>
                    <p class="choice-description">
                        Registra la información de un vehículo con reporte de robo para almacenarla y permitir futuras consultas.
                    </p>
                    <button class="btn btn-primary btn-choice">
                        <i class="bi bi-plus-circle me-2"></i>Reportar Vehículo
                    </button>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="choice-card" onclick="window.location.href='{{ route('register', ['type' => 'consult']) }}'">
                    <div class="choice-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3 class="choice-title">Verificar Vehículo</h3>
                    <p class="choice-description">
                        Consulta si un vehículo posee antecedentes o reportes mediante la placa o el número VIN.
                    </p>
                    <button class="btn btn-outline-light btn-choice">
                        <i class="bi bi-search me-2"></i>Verificar Información
                    </button>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <p class="text-white-50 mb-0">
                ¿Ya tienes cuenta? 
                <a href="{{ route('login') }}" class="text-primary text-decoration-none fw-bold">Inicia sesión aquí</a>
            </p>
        </div>
    </div>
</body>
</html>