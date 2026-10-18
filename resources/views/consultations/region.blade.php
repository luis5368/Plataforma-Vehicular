@extends('layouts.app')

@section('title', 'Análisis por Región')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="bi bi-map"></i> Análisis y Consulta por Región</h2>

    @include('reports.filters')
    @include('reports.analytics')
    <div class="row">
        <!-- 2. Tabla de Resultados -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Vehículos con reportes abiertos validados ({{ $vehicles->count() }})</h5>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    @if($vehicles->count() > 0)
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>VIN</th>
                                <th>Placa</th>
                                <th>Ubicación</th>
                                <th>Fecha y fuente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicles as $v)
                            <tr>
                                <td><code class="small">{{ $v->vin }}</code></td>
                                <td><strong>{{ $v->plate }}</strong></td>
                                <td><small>{{ $v->municipality }}, {{ $v->zone }}</small></td>
                                <td><small>{{ \Carbon\Carbon::parse($v->theftReport->report_date)->format('d/m/y') }}</small><x-report-context :report="$v->theftReport" /></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="p-4 text-center text-muted">
                        Sin datos disponibles
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 3. Mapa de Calor / Pines -->
        <div class="col-md-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Mapa de reportes abiertos validados</h5>
                </div>
                <div class="card-body p-0">
                    @if($mapData->isEmpty())<p class="p-3 text-secondary">Sin datos disponibles para el mapa: no hay reportes con coordenadas en estos filtros.</p>@endif
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
    const escapeHtml = value => {const span=document.createElement('span');span.textContent=value ?? '';return span.innerHTML;};

    if (locations.length > 0) {
        var bounds = [];
        locations.forEach(function(loc) {
            if(loc.lat && loc.lng) {
                var marker = L.marker([loc.lat, loc.lng]).addTo(map);
                marker.bindPopup(`
                    <b>VIN:</b> ${escapeHtml(loc.vin)}<br><b>Placa:</b> ${escapeHtml(loc.plate )}<br>
                    <b>Vehículo:</b> ${escapeHtml(loc.brand )}<br>
                    <b>Dirección:</b> ${escapeHtml(loc.address )}<br>
                    <b>Detalles:</b> ${escapeHtml(loc.desc)}
                `);
                bounds.push([loc.lat, loc.lng]);
            }
        });
        // Ajustar el mapa para que se vean todos los pines
        if (bounds.length) map.fitBounds(bounds, { padding: [50, 50] });
    }
});
</script>
@endsection