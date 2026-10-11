@extends('layouts.app')

@section('title', 'Vehículos Registrados')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-car-front"></i> Vehículos Registrados</h2>
        @if(auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'registro')
            <a href="{{ route('vehiculos.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Registrar Nuevo
            </a>
        @endif
    </div>

    @if($vehicles->count() > 0)
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <!-- VIN PRIMERO Y PRINCIPAL -->
                            <th width="25%">
                                <i class="bi bi-fingerprint text-primary"></i> VIN
                            </th>
                            <th width="15%">Placa</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Año</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th width="15%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicles as $vehicle)
                        <tr>
                            <!-- VIN DESTACADO -->
                            <td>
                                <code class="text-primary fw-bold" style="font-size: 0.9rem;">
                                    {{ $vehicle->vin }}
                                </code>
                            </td>
                            <!-- Placa en segundo plano -->
                            <td>
                                <span class="text-muted">{{ $vehicle->plate }}</span>
                            </td>
                            <td>{{ $vehicle->brand }}</td>
                            <td>{{ $vehicle->model }}</td>
                            <td>{{ $vehicle->year ?? '-' }}</td>
                            <td>
                                <small class="text-muted">
                                    {{ $vehicle->municipality ?? 'N/A' }}
                                    @if($vehicle->zone)
                                        , {{ $vehicle->zone }}
                                    @endif
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $vehicle->status === 'activo' ? 'success' : ($vehicle->status === 'reportado' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($vehicle->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('vehiculos.show', $vehicle->id) }}" 
                                       class="btn btn-info" 
                                       title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'registro')
                                        <a href="{{ route('vehiculos.edit', $vehicle->id) }}" 
                                           class="btn btn-warning" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('vehiculos.destroy', $vehicle->id) }}" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('¿Estás seguro de eliminar este vehículo? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación forzada a Bootstrap 5 para evitar íconos gigantes -->
            <div class="mt-3">
                {{ $vehicles->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <p class="mt-3 text-muted">No hay vehículos registrados aún.</p>
            @if(auth()->user()->role->name === 'admin' || auth()->user()->role->name === 'registro')
                <a href="{{ route('vehiculos.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Registrar primer vehículo
                </a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection