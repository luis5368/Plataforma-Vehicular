@extends('layouts.app')

@section('title', 'Análisis por Región')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="bi bi-map"></i> Análisis y Consulta por Región</h2>

    <!-- 1. Formulario de Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('consultar.region') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Departamento</label>
                    <select name="department" class="form-select" id="filter-dept" onchange="updateMunFilter()">
                        <option value="">Todos</option>
                        <option value="Guatemala" {{ request('department') == 'Guatemala' ? 'selected' : '' }}>Guatemala</option>
                        <option value="Sacatepéquez" {{ request('department') == 'Sacatepéquez' ? 'selected' : '' }}>Sacatepéquez</option>
                        <option value="Escuintla" {{ request('department') == 'Escuintla' ? 'selected' : '' }}>Escuintla</option>
                        <option value="Quetzaltenango" {{ request('department') == 'Quetzaltenango' ? 'selected' : '' }}>Quetzaltenango</option>
                        <option value="Alta Verapaz" {{ request('department') == 'Alta Verapaz' ? 'selected' : '' }}>Alta Verapaz</option>
                        <option value="Izabal" {{ request('department') == 'Izabal' ? 'selected' : '' }}>Izabal</option>
                        <option value="Petén" {{ request('department') == 'Petén' ? 'selected' : '' }}>Petén</option>
                        <option value="Huehuetenango" {{ request('department') == 'Huehuetenango' ? 'selected' : '' }}>Huehuetenango</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Municipio</label>
                    <input type="text" name="municipality" class="form-control" value="{{ request('municipality') }}" placeholder="Ej: Mixco">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Zona / Área</label>
                    <input type="text" name="zone" class="form-control" value="{{ request('zone') }}" placeholder="Ej: Zona 10">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrar y Analizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- 2. Tabla de Resultados -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Vehículos Robados Encontrados ({{ $vehicles->count() }})</h5>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    @if($vehicles->count() > 0)
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Placa</th>
                                <th>Ubicación</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicles as $v)
                            <tr>
                                <td><strong>{{ $v->plate }}</strong><br><small class="text-muted">{{ $v->brand }}</small></td>
                                <td><small>{{ $v->municipality }}, {{ $v->zone }}</small></td>
                                <td><small>{{ \Carbon\Carbon::parse($v->theftReport->report_date)->format('d/m/y') }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="p-4 text-center text-muted">
                        No hay reportes activos con estos filtros.
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Mapa de Calor / Pines -->
        <div class="col-md-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Mapa de Incidencia Delictiva</h5>
                </div>
                <div class="card-body p-0">
                    <div id="region-map" style="height: 500px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet Scripts -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar mapa centrado en Guatemala
    var map = L.map('region-map').setView([14.6349, -90.5069], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Datos inyectados desde Laravel
    var locations = @json($mapData);

    if (locations.length > 0) {
        var bounds = [];
        locations.forEach(function(loc) {
            if(loc.lat && loc.lng) {
                var marker = L.marker([loc.lat, loc.lng]).addTo(map);
                marker.bindPopup(`
                    <b>Placa:</b> ${loc.plate}<br>
                    <b>Vehículo:</b> ${loc.brand}<br>
                    <b>Dirección:</b> ${loc.address}<br>
                    <b>Detalles:</b> ${loc.desc}
                `);
                bounds.push([loc.lat, loc.lng]);
            }
        });
        // Ajustar el mapa para que se vean todos los pines
        map.fitBounds(bounds, { padding: [50, 50] });
    }
});
</script>
@endsection