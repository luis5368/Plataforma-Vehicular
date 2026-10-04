@extends('layouts.app')

@section('title', 'Lista de Vehículos')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-car-front"></i> Vehículos Registrados</h2>
        <a href="{{ route('vehiculos.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Registrar Nuevo
        </a>
    </div>

    @if($vehicles->count() > 0)
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Placa</th>
                            <th>VIN</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Año</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vehicles as $vehicle)
                        <tr>
                            <td><strong>{{ $vehicle->plate }}</strong></td>
                            <td>{{ $vehicle->vin }}</td>
                            <td>{{ $vehicle->brand }}</td>
                            <td>{{ $vehicle->model }}</td>
                            <td>{{ $vehicle->year ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $vehicle->status === 'activo' ? 'success' : ($vehicle->status === 'reportado' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($vehicle->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('vehiculos.show', $vehicle->id) }}" class="btn btn-sm btn-info" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('vehiculos.edit', $vehicle->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('vehiculos.destroy', $vehicle->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este vehículo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $vehicles->links() }}
            </div>
        </div>
    </div>
    @else
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
            <p class="mt-3 text-muted">No hay vehículos registrados aún.</p>
            <a href="{{ route('vehiculos.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Registrar primer vehículo
            </a>
        </div>
    </div>
    @endif
</div>
@endsection