<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InfoVehicular GT - Plataforma de Consulta y Reporte Vehicular</title>
    <meta name="description" content="Consulta si un vehículo tiene reporte de robo en Guatemala. Más de 1,000 registros actualizados. Verifica por VIN o placa.">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #0a58ca;
            --dark-bg: #0f172a;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* Navbar */
        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(15, 23, 42, 0.9) !important;
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            margin: 0 0.5rem;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .btn-nav {
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1e293b 100%);
            min-height: 90vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><g fill="%239C92AC" fill-opacity="0.05"><path d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/></g></g></svg>');
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(13, 110, 253, 0.2);
            color: #60a5fa;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(13, 110, 253, 0.3);
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #60a5fa;
            display: block;
        }
        
        .stat-label {
            color: rgba(255,255,255,0.7);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        /* Features */
        .features-section {
            padding: 6rem 0;
            background: #f8fafc;
        }
        
        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon i {
            font-size: 2rem;
            color: white;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 6rem 0;
            text-align: center;
            color: white;
        }
        
        /* Footer */
        footer {
            background: var(--dark-bg);
            color: rgba(255,255,255,0.6);
            padding: 3rem 0 2rem;
        }
        
        .footer-brand {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            .hero-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-car-front-fill me-2"></i>InfoVehicular GT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#caracteristicas">Características</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#como-funciona">Cómo Funciona</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#estadisticas">Estadísticas</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-nav">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Acceder
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a href="{{ route('register') }}" class="btn btn-light btn-nav text-primary">
                            <i class="bi bi-person-plus me-2"></i>Registrarse
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="hero-badge">
                        <i class="bi bi-shield-check me-2"></i>Plataforma Certificada
                    </div>
                    <h1 class="hero-title">
                        Protege tu inversión<br>
                        <span style="color: #60a5fa;">verifica antes de comprar</span>
                    </h1>
                    <p class="hero-subtitle">
                        Consulta si un vehículo tiene reporte de robo en Guatemala. 
                        Accede a más de <strong>1,000 registros actualizados</strong> 
                        en tiempo real. Verifica por VIN o placa de forma rápida y segura.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-search me-2"></i>Consultar Ahora
                        </a>
                        <a href="#como-funciona" class="btn btn-outline-light btn-lg px-5">
                            <i class="bi bi-play-circle me-2"></i>Ver Demo
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="row mt-5 g-3">
                        <div class="col-4">
                            <div class="stat-card">
                                <span class="stat-number">1,247</span>
                                <div class="stat-label">Vehículos Reportados</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <span class="stat-number">5,832</span>
                                <div class="stat-label">Consultas Realizadas</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <span class="stat-number">22</span>
                                <div class="stat-label">Departamentos</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="text-center">
                        <i class="bi bi-car-front" style="font-size: 20rem; color: rgba(96, 165, 250, 0.2);"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="caracteristicas" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">¿Por qué elegir InfoVehicular GT?</h2>
                <p class="lead text-muted">La plataforma más completa de consulta vehicular en Guatemala</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-fingerprint"></i>
                        </div>
                        <h4>Búsqueda por VIN</h4>
                        <p class="text-muted mb-0">El método más seguro. El VIN es único e inmutable, grabado en el chasis del vehículo.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h4>Mapa de Siniestros</h4>
                        <p class="text-muted mb-0">Visualiza geográficamente dónde ocurrieron los reportes de robo.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-lightning-fill"></i>
                        </div>
                        <h4>Resultados Instantáneos</h4>
                        <p class="text-muted mb-0">Obtén la información en segundos. Sin esperas, sin complicaciones.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4>Datos Confiables</h4>
                        <p class="text-muted mb-0">Información actualizada y verificada de fuentes oficiales.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h4>Historial de Consultas</h4>
                        <p class="text-muted mb-0">Guardamos tu historial para que siempre tengas respaldo.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <h4>100% Seguro</h4>
                        <p class="text-muted mb-0">Tus datos están protegidos con encriptación de nivel bancario.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section id="como-funciona" style="padding: 6rem 0; background: white;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">¿Cómo funciona?</h2>
                <p class="lead text-muted">Tres simples pasos para proteger tu inversión</p>
            </div>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; color: white; font-weight: bold;">1</div>
                    <h4>Regístrate</h4>
                    <p class="text-muted">Crea tu cuenta gratuita en menos de 2 minutos. Puedes usar tu correo o Google.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; color: white; font-weight: bold;">2</div>
                    <h4>Ingresa los Datos</h4>
                    <p class="text-muted">Proporciona el VIN o la placa del vehículo que deseas consultar.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; color: white; font-weight: bold;">3</div>
                    <h4>Recibe el Reporte</h4>
                    <p class="text-muted">Obtén inmediatamente si el vehículo tiene reporte de robo y su ubicación.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="display-4 fw-bold mb-4">¿Listo para consultar?</h2>
            <p class="lead mb-5" style="max-width: 600px; margin: 0 auto 2rem;">
                Únete a miles de usuarios que ya protegen sus inversiones con InfoVehicular GT
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg px-5 text-primary">
                    <i class="bi bi-person-plus me-2"></i>Crear Cuenta Gratis
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-5">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="footer-brand mb-3">
                        <i class="bi bi-car-front-fill me-2"></i>InfoVehicular GT
                    </div>
                    <p>Plataforma líder en consulta y reporte de vehículos robados en Guatemala. Protegiendo tu patrimonio desde 2024.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="#caracteristicas" class="text-decoration-none" style="color: rgba(255,255,255,0.6);">Características</a></li>
                        <li><a href="#como-funciona" class="text-decoration-none" style="color: rgba(255,255,255,0.6);">Cómo Funciona</a></li>
                        <li><a href="{{ route('login') }}" class="text-decoration-none" style="color: rgba(255,255,255,0.6);">Acceder</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">Contacto</h5>
                    <p><i class="bi bi-envelope me-2"></i>info@infovehiculargt.com</p>
                    <p><i class="bi bi-geo-alt me-2"></i>Ciudad de Guatemala</p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center pt-3">
                <p class="mb-0">&copy; 2024 InfoVehicular GT. Proyecto de Seminario de TI. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>