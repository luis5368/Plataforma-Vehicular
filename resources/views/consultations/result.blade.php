@extends('layouts.app')

@section('title', 'Resultado de Consulta')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <!-- 1. ALERTA DE ROBO (Se muestra solo si hay reporte activo) -->
            @if($theftReport)
            <div class="alert alert-danger border-2 shadow-sm mb-4">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill text-white" style="font-size: 2.5rem;"></i>
                    <div class="ms-3">
                        <h4 class="alert-heading mb-1 fw-bold">⚠️ VEHÍCULO CON REPORTE DE ROBO ACTIVO</h4>
                        <p class="mb-1"><strong>Fecha del reporte:</strong> {{ \Carbon\Carbon::parse($theftReport->report_date)->format('d/m/Y') }}</p>
                        <p class="mb-1"><strong>Descripción:</strong> {{ $theftReport->description }}</p>
                        <hr class="border-white">
                        <p class="mb-0 fw-semibold">
                            <strong>Recomendación:</strong> No se recomienda continuar con la transacción. 
                            Verifique la información inmediatamente con las autoridades competentes (PNC, Ministerio Público).
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- 2. RESULTADO DE LA BÚSQUEDA -->
            @if($vehicle)
            <div class="card shadow-sm mb-4">
                <div class="card-header {{ $theftReport ? 'bg-danger text-white' : 'bg-success text-white' }}">
                    <h4 class="mb-0">
                        @if($theftReport)
                            <i class="bi bi-x-circle-fill"></i> Vehículo con Reporte de Robo
                        @else
                            <i class="bi bi-check-circle-fill"></i> Vehículo sin Reportes de Robo
                        @endif
                    </h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-3 text-primary">Información del Vehículo</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th width="40%" class="text-muted">Placa:</th>
                                    <td><strong class="fs-5">{{ $vehicle->plate }}</strong></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">VIN:</th>
                                    <td>{{ $vehicle->vin }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Marca:</th>
                                    <td>{{ $vehicle->brand }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Modelo:</th>
                                    <td>{{ $vehicle->model }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th width="40%" class="text-muted">Año:</th>
                                    <td>{{ $vehicle->year ?? 'No registrado' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Color:</th>
                                    <td>{{ $vehicle->color ?? 'No registrado' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Departamento:</th>
                                    <td>{{ $vehicle->department ?? 'No registrado' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Municipio / Zona:</th>
                                    <td>{{ $vehicle->municipality ?? 'No registrado' }} @if($vehicle->zone) - {{ $vehicle->zone }} @endif</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Estado:</th>
                                    <td>
                                        <span class="badge bg-{{ $vehicle->status === 'activo' ? 'success' : ($vehicle->status === 'reportado' ? 'danger' : 'secondary') }}">
                                            {{ ucfirst($vehicle->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($vehicle->observations)
                    <div class="mt-3">
                        <h6 class="text-muted">Observaciones:</h6>
                        <div class="alert alert-secondary mb-0">
                            {{ $vehicle->observations }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 3. MAPA DE UBICACIÓN DEL ROBO (Si aplica) -->
            @if($theftReport && $vehicle->theft_latitude && $vehicle->theft_longitude)
            <div class="card shadow-sm mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Ubicación del Robo en el Mapa</h5>
                </div>
                <div class="card-body p-0">
                    <div id="theft-map" style="height: 400px; width: 100%;"></div>
                </div>
                <div class="card-footer bg-light">
                    <p class="mb-1"><strong>Dirección registrada:</strong> {{ $vehicle->theft_report_address }}</p>
                    <p class="mb-2"><strong>Coordenadas:</strong> {{ $vehicle->theft_latitude }}, {{ $vehicle->theft_longitude }}</p>
                    <a href="https://www.google.com/maps?q={{ $vehicle->theft_latitude }},{{ $vehicle->theft_longitude }}" 
                       target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-map"></i> Abrir en Google Maps
                    </a>
                </div>
            </div>
            @endif

            @else
            <!-- 4. VEHÍCULO NO ENCONTRADO -->
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-body text-center py-5">
                    <i class="bi bi-question-circle text-warning" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Vehículo no encontrado</h4>
                    <p class="text-muted">
                        No existe ningún registro en el sistema con el 
                        <strong>{{ $searchMethod === 'VIN' ? 'VIN' : 'número de placa' }}</strong> 
                        <strong class="text-dark">{{ $searchMethod === 'VIN' ? $vin : $plate }}</strong>.
                    </p>
                    <p class="small text-muted">
                        Esto no garantiza que el vehículo no tenga reportes en otras fuentes o que la placa no haya sido clonada. 
                        Se recomienda verificar con autoridades competentes.
                    </p>
                </div>
            </div>
            @endif

            <!-- 5. BOTONES DE ACCIÓN -->
            <div class="d-flex justify-content-between mb-4">
                <a href="{{ route('consultar.index') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Nueva Consulta
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-house"></i> Ir al Dashboard
                </a>
            </div>

            <!-- 6. DISCLAIMER LEGAL -->
            <div class="alert alert-warning border-0 shadow-sm">
                <i class="bi bi-shield-exclamation"></i>
                <strong>Aviso importante:</strong> Esta plataforma es una herramienta de apoyo para la toma de decisiones. 
                La información mostrada no constituye una certificación legal. Verifique siempre con las autoridades 
                competentes (PNC, Ministerio Público) antes de realizar cualquier transacción.
            </div>
        </div>
    </div>
</div>

<!-- Scripts de Leaflet (Solo se cargan si hay mapa que mostrar) -->
@if($theftReport && $vehicle && $vehicle->theft_latitude && $vehicle->theft_longitude)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var lat = {{ $vehicle->theft_latitude }};
    var lng = {{ $vehicle->theft_longitude }};
    var address = "{{ $vehicle->theft_report_address ?? 'Ubicación del reporte' }}";
    
    var map = L.map('theft-map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    L.marker([lat, lng]).addTo(map)
        .bindPopup('<b>Ubicación del Robo</b><br>' + address)
        .openPopup();
});
</script>
@endif

@endsection