@extends('layouts.app')

@section('title', 'Historial de Consultas')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">
        <i class="bi bi-clock-history"></i> Mi Historial de Consultas
    </h2>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($consultations->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>VIN</th>
                            <th>Placa</th>
                            <th>Vehículo</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($consultations as $c)
                        <tr>
                            <td>{{ $c->consultation_date->format('d/m/Y H:i') }}</td>
                            <td><code class="small">{{ $c->vehicle->vin ?? 'N/A' }}</code></td>
                            <td><strong>{{ $c->vehicle->plate ?? 'N/A' }}</strong></td>
                            <td>
                                @if($c->vehicle)
                                    {{ $c->vehicle->brand }} {{ $c->vehicle->model }}
                                @else
                                    <span class="text-muted">No encontrado</span>
                                @endif
                            </td>
                            <td>
                                @if($c->result === 'con_reporte_activo')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Con Reporte
                                    </span>
                                @elseif($c->result === 'sin_reporte')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Sin Reportes
                                    </span>
                                @else
                                    <span class="badge bg-secondary">No Encontrado</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $consultations->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <p class="mt-3 text-muted">Aún no has realizado consultas.</p>
                <a href="{{ route('consultar.index') }}" class="btn btn-primary">
                    <i class="bi bi-search"></i> Realizar primera consulta
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection