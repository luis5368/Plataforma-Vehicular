@extends('layouts.app') <!-- Asumiendo que crearás un layout base, si no, usa la estructura HTML completa -->

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Bienvenido, {{ $user->name }} <span class="badge bg-primary">{{ ucfirst($user->role->name) }}</span></h2>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Vehículos Registrados</h5>
                    <p class="card-text display-4">{{ $stats['total_vehicles'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Reportes de Robo Activos</h5>
                    <p class="card-text display-4">{{ $stats['active_reports'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        @if(auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'registro')
            <a href="{{ route('vehiculos.create') }}" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle"></i> Registrar Nuevo Vehículo
            </a>
        @endif
        <a href="{{ route('consultar.index') }}" class="btn btn-info btn-lg text-white">
            <i class="bi bi-search"></i> Consultar Vehículo por Placa
        </a>
    </div>
</div>
@endsection