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
                    <h5 class="mb-0">Vehículos con reportes abiertos validados ({{ $vehicles->total() }})</h5>
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
                <div class="card-footer"><p class="small text-secondary">{{ $vehicles->firstItem() ?? 0 }}–{{ $vehicles->lastItem() ?? 0 }} de {{ $vehicles->total() }} resultados. Los indicadores incluyen todo el conjunto filtrado.</p>{{ $vehicles->links() }}</div>
            </div>
        </div>

        <!-- 3. Mapa de Calor / Pines -->
        <div class="col-md-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Mapa de reportes abiertos validados</h5>
                </div>
                <div class="card-body p-0">
                    <x-leaflet-map id="region-map" label="Mapa de reportes abiertos validados" height="500px"
                        :data-endpoint="route('consultar.region.map')" :data-filters="json_encode($filters)"
                        :data-initial-bounds="json_encode($mapBounds)" />
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
