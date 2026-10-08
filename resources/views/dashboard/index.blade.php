@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="bi bi-speedometer2"></i> Panel de Control
        <span class="badge bg-primary ms-2">{{ ucfirst($user->role->name) }}</span>
    </h2>

    <!-- Tarjetas de Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h6 class="card-title">Total Vehículos</h6>
                    <p class="card-text display-6">{{ $stats['total_vehicles'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <h6 class="card-title">Robos Activos</h6>
                    <p class="card-text display-6">{{ $stats['active_reports'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h6 class="card-title">Casos Resueltos</h6>
                    <p class="card-text display-6">{{ $stats['resolved_reports'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <h6 class="card-title">Consultas Totales</h6>
                    <p class="card-text display-6">{{ $stats['total_consultations'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h6 class="card-title">Consultas Hoy</h6>
                    <p class="card-text display-6">{{ $stats['consultations_today'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white bg-secondary h-100">
                <div class="card-body">
                    <h6 class="card-title">Usuarios</h6>
                    <p class="card-text display-6">{{ $stats['total_users'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Departamentos -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Top 5 Departamentos con más Robos</h5>
                </div>
                <div class="card-body">
                    @if($topDepartments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Departamento</th>
                                    <th class="text-end">Casos Activos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topDepartments as $index => $dept)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $dept->department }}</td>
                                    <td class="text-end"><span class="badge bg-danger">{{ $dept->total }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center">Sin datos disponibles</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Top Municipios -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Top 5 Municipios con más Robos</h5>
                </div>
                <div class="card-body">
                    @if($topMunicipalities->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Municipio</th>
                                    <th>Departamento</th>
                                    <th class="text-end">Casos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topMunicipalities as $index => $mun)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $mun->municipality }}</td>
                                    <td><small>{{ $mun->department }}</small></td>
                                    <td class="text-end"><span class="badge bg-danger">{{ $mun->total }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center">Sin datos disponibles</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Distribución por Zonas (Guatemala) -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-map"></i> Robos por Zona (Departamento de Guatemala)</h5>
                </div>
                <div class="card-body">
                    @if($zoneDistribution->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Zona</th>
                                    <th class="text-end">Casos Activos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($zoneDistribution as $zone)
                                <tr>
                                    <td>{{ $zone->zone }}</td>
                                    <td class="text-end"><span class="badge bg-warning text-dark">{{ $zone->total }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center">Sin datos disponibles</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Marcas más robadas -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-car-front"></i> Top 5 Marcas más Robadas</h5>
                </div>
                <div class="card-body">
                    @if($topBrands->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Marca</th>
                                    <th class="text-end">Casos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topBrands as $index => $brand)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $brand->brand }}</td>
                                    <td class="text-end"><span class="badge bg-info text-dark">{{ $brand->total }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center">Sin datos disponibles</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Acciones Rápidas</h5>
                    <div class="d-flex flex-wrap gap-2">
                        @if(auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'registro')
                            <a href="{{ route('vehiculos.create') }}" class="btn btn-success btn-lg">
                                <i class="bi bi-plus-circle"></i> Registrar Vehículo
                            </a>
                        @endif
                        <a href="{{ route('consultar.index') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-search"></i> Consultar por Placa/VIN
                        </a>
                        <a href="{{ route('consultar.historial') }}" class="btn btn-info btn-lg text-white">
                            <i class="bi bi-clock-history"></i> Mi Historial
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection