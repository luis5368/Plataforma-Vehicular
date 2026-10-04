@extends('layouts.app')

@section('title', 'Detalle del Vehículo')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-car-front"></i> Detalle del Vehículo</h2>
        <div>
            <a href="{{ route('vehiculos.edit', $vehiculo->id) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('vehiculos.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    @if($vehiculo->theftReport && $vehiculo->theftReport->status === 'activo')
    <div class="alert alert-danger border-2 shadow-sm mb-4">
        <h4><i class="bi bi-exclamation-triangle"></i> VEHÍCULO CON REPORTE DE ROBO ACTIVO</h4>
        <p><strong>Fecha:</strong> {{ $vehiculo->theftReport->report_date->format('d/m/Y') }}</p>
        <p><strong>Descripción:</strong> {{ $vehiculo->theftReport->description }}</p>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr><th>Placa:</th><td><strong>{{ $vehiculo->plate }}</strong></td></tr>
                        <tr><th>VIN:</th><td>{{ $vehiculo->vin }}</td></tr>
                        <tr><th>Marca:</th><td>{{ $vehiculo->brand }}</td></tr>
                        <tr><th>Modelo:</th><td>{{ $vehiculo->model }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr><th>Año:</th><td>{{ $vehiculo->year ?? 'No registrado' }}</td></tr>
                        <tr><th>Color:</th><td>{{ $vehiculo->color ?? 'No registrado' }}</td></tr>
                        <tr><th>Estado:</th><td><span class="badge bg-success">{{ ucfirst($vehiculo->status) }}</span></td></tr>
                        <tr><th>Registrado por:</th><td>{{ $vehiculo->registeredBy->name ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>

            @if($vehiculo->theft_report_address)
            <div class="mt-3">
                <h6>Dirección del Reporte:</h6>
                <p>{{ $vehiculo->theft_report_address }}</p>
            </div>
            @endif

            @if($vehiculo->observations)
            <div class="mt-3">
                <h6>Observaciones:</h6>
                <div class="alert alert-secondary">{{ $vehiculo->observations }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection