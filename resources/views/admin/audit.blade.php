@extends('layouts.app')

@section('title', 'Bitácora de Auditoría')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-journal-text"></i> Bitácora de Auditoría del Sistema</h2>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.bitacora') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Acción</label>
                    <select name="action" class="form-select">
                        <option value="">Todas las acciones</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $action)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usuario</label>
                    <select name="user_id" class="form-select">
                        <option value="">Todos los usuarios</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.bitacora') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div class="card shadow-sm">
        <div class="card-body">
            @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Tabla Afectada</th>
                            <th>ID Registro</th>
                            <th>Dirección IP</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                @if($log->user)
                                    <strong>{{ $log->user->name }}</strong><br>
                                    <small class="text-muted">{{ $log->user->email }}</small>
                                @else
                                    <span class="text-muted">Sistema / Eliminado</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badgeClass = 'bg-secondary';
                                    if(str_contains($log->action, 'create')) $badgeClass = 'bg-success';
                                    elseif(str_contains($log->action, 'update')) $badgeClass = 'bg-warning text-dark';
                                    elseif(str_contains($log->action, 'delete')) $badgeClass = 'bg-danger';
                                    elseif(str_contains($log->action, 'login')) $badgeClass = 'bg-info text-dark';
                                    elseif(str_contains($log->action, 'consult')) $badgeClass = 'bg-primary';
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>
                            <td><code>{{ $log->table_name }}</code></td>
                            <td>{{ $log->record_id ?? '-' }}</td>
                            <td><small>{{ $log->ip_address }}</small></td>
                            <td>
                                @if($log->new_values)
                                    <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#details{{ $log->id }}">
                                        Ver detalles
                                    </button>
                                    <div class="collapse mt-2" id="details{{ $log->id }}">
                                        <div class="card card-body bg-light small">
                                            @if($log->old_values)
                                                <strong>Antes:</strong> <pre class="mb-1">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @endif
                                            @if($log->new_values)
                                                <strong>Después:</strong> <pre class="mb-0">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">Sin detalles adicionales</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $logs->appends(request()->query())->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                <p class="mt-3 text-muted">No se encontraron registros en la bitácora con los filtros aplicados.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection