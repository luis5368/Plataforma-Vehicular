@extends('layouts.app')

@section('title', 'Resultado de Consulta')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- ALERTA DE ROBO (Se muestra solo si hay reporte activo) -->
            @if($theftReport)
            <div class="alert alert-danger border-2 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 2.5rem;"></i>
                    <div class="ms-3">
                        <h4 class="alert-heading mb-1">⚠️ VEHÍCULO CON REPORTE DE ROBO ACTIVO</h4>
                        <p class="mb-1"><strong>Fecha del reporte:</strong> {{ $theftReport->report_date->format('d/m/Y') }}</p>
                        <p class="mb-1"><strong>Descripción:</strong> {{ $theftReport->description }}</p>
                        <hr>
                        <p class="mb-0">
                            <strong>Recomendación:</strong> No se recomienda continuar con la transacción. 
                            Verifique la información con las autoridades competentes (PNC, Ministerio Público).
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- RESULTADO DE LA BÚSQUEDA -->
            @if($vehicle)
            <div class="card shadow-sm mb-4">
                <div class="card-header {{ $theftReport ? 'bg-danger text-white' : 'bg-success text-white' }}">
                    <h4 class="mb-0">
                        @if($theftReport)
                            <i class="bi bi-x-circle"></i> Vehículo con Reporte de Robo
                        @else
                            <i class="bi bi-check-circle"></i> Vehículo sin Reportes de Robo
                        @endif
                    </h4>
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-3">Información del Vehículo</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Placa:</th>
                                    <td><strong>{{ $vehicle->plate }}</strong></td>
                                </tr>
                                <tr>
                                    <th>VIN:</th>
                                    <td>{{ $vehicle->vin }}</td>
                                </tr>
                                <tr>
                                    <th>Marca:</th>
                                    <td>{{ $vehicle->brand }}</td>
                                </tr>
                                <tr>
                                    <th>Modelo:</th>
                                    <td>{{ $vehicle->model }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Año:</th>
                                    <td>{{ $vehicle->year ?? 'No registrado' }}</td>
                                </tr>
                                <tr>
                                    <th>Color:</th>
                                    <td>{{ $vehicle->color ?? 'No registrado' }}</td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        <span class="badge bg-{{ $vehicle->status === 'activo' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($vehicle->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Registrado por:</th>
                                    <td>{{ $vehicle->registeredBy->name ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($vehicle->observations)
                    <div class="mt-3">
                        <h6>Observaciones:</h6>
                        <div class="alert alert-secondary">
                            {{ $vehicle->observations }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @else
            <!-- Vehículo no encontrado -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center py-5">
                    <i class="bi bi-question-circle text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Vehículo no encontrado</h4>
                    <p class="text-muted">
                        No existe ningún registro en el sistema con la placa 
                        <strong>{{ $plate }}</strong>.
                    </p>
                    <p class="small text-muted">
                        Esto no garantiza que el vehículo no tenga reportes en otras fuentes. 
                        Se recomienda verificar con autoridades competentes.
                    </p>
                </div>
            </div>
            @endif

            <!-- Botones de acción -->
            <div class="d-flex justify-content-between">
                <a href="{{ route('consultar.index') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Nueva Consulta
                </a>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-house"></i> Ir al Dashboard
                </a>
            </div>

            <!-- Disclaimer legal -->
            <div class="alert alert-warning mt-4">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Aviso importante:</strong> Esta plataforma es una herramienta de apoyo 
                para la toma de decisiones. La información mostrada no constituye una certificación 
                legal. Verifique siempre con las autoridades competentes (PNC, Ministerio Público) 
                antes de realizar cualquier transacción.
            </div>
        </div>
    </div>
</div>
@endsection