<section class="report-analytics" aria-label="Indicadores y distribuciones de reportes">
<p class="analytics-context"><i class="bi bi-shield-check" aria-hidden="true"></i><span>Reportes de usuarios autorizados. La validación automática comprueba integridad técnica y autorización; no es prueba independiente de que ocurrió un robo. Las distribuciones incluyen solo casos abiertos con validación automática, administrativa o validación histórica registrada.</span></p>
@php
    $indicators = [
        'total_vehicles' => ['label'=>'Vehículos únicos','icon'=>'fingerprint','tone'=>'vehicles','context'=>'Cuenta VIN distintos de los vehículos que cumplen los filtros geográficos actuales, tengan o no reporte.'],
        'active_reports' => ['label'=>'Reportes abiertos validados','icon'=>'shield-check','tone'=>'validated','context'=>'Cuenta vehículos únicos con validación completada y caso abierto. Incluye validación automática, administrativa o histórica registrada; no acredita por sí sola que ocurrió un robo.'],
        'resolved_reports' => ['label'=>'Casos resueltos','icon'=>'check2-circle','tone'=>'resolved','context'=>'Cuenta vehículos únicos cuyo caso está resuelto, incluidos los históricos sin validación acreditada. No se suman a los reportes abiertos.'],
        'pending_reports' => ['label'=>'Reportes abiertos pendientes','icon'=>'hourglass-split','tone'=>'pending','context'=>'Cuenta vehículos únicos con caso abierto y validación pendiente. No se incluyen como validados en las distribuciones.'],
    ];
@endphp
<div class="kpi-grid">
@foreach($indicators as $key=>$indicator)
<article class="kpi-card kpi-card--{{ $indicator['tone'] }}" aria-labelledby="kpi-{{ $key }}">
    <div class="kpi-topline"><h2 id="kpi-{{ $key }}">{{ $indicator['label'] }}</h2><span class="kpi-icon" aria-hidden="true"><i class="bi bi-{{ $indicator['icon'] }}"></i></span></div>
    <p class="kpi-value">{{ $stats[$key] }}</p>
    <span class="kpi-scope"><i class="bi bi-funnel" aria-hidden="true"></i> Según filtros actuales</span>
    <details class="kpi-details"><summary>¿Qué cuenta este indicador?<i class="bi bi-chevron-down" aria-hidden="true"></i></summary><p>{{ $indicator['context'] }}</p></details>
</article>
@endforeach
</div>
<div class="distribution-grid">
@foreach(['topDepartments'=>['title'=>'Por departamento','icon'=>'geo-alt'], 'topMunicipalities'=>['title'=>'Por municipio','icon'=>'buildings'], 'zoneDistribution'=>['title'=>'Por zona','icon'=>'pin-map'], 'topBrands'=>['title'=>'Por marca','icon'=>'car-front'], 'monthlyTrend'=>['title'=>'Tendencia de reportes validados (incluye resueltos)','icon'=>'calendar3']] as $variable=>$distribution)
<section class="distribution-panel" aria-labelledby="distribution-{{ $variable }}">
    <header><span class="distribution-icon" aria-hidden="true"><i class="bi bi-{{ $distribution['icon'] }}"></i></span><div><h2 id="distribution-{{ $variable }}">{{ $distribution['title'] }}</h2><p>{{ $variable === 'monthlyTrend' ? 'Vehículos únicos por mes · últimos seis meses' : 'Vehículos únicos · casos abiertos validados' }}</p></div></header>
    <div class="distribution-body">
    @forelse($$variable as $row)
        @php($label = $row->municipality ?? $row->department ?? $row->zone ?? $row->brand ?? $row->month)
        <div class="distribution-row"><div class="distribution-row-label"><span>{{ $label }} @if(isset($row->municipality))<small> · {{ $row->department }}</small>@endif</span><strong>{{ $row->total }}</strong></div><meter min="0" max="{{ max(1, $$variable->max('total')) }}" value="{{ $row->total }}" aria-label="{{ $distribution['title'] }}: {{ $label }}">{{ $row->total }}</meter></div>
    @empty
        <div class="distribution-empty"><i class="bi bi-bar-chart" aria-hidden="true"></i><p>Sin datos disponibles</p><small>{{ $variable === 'monthlyTrend' ? 'No hay reportes validados abiertos o resueltos en este período con los filtros actuales.' : 'No hay casos abiertos validados con datos para esta categoría y los filtros actuales.' }}</small></div>
    @endforelse
    </div>
</section>
@endforeach
</div>
</section>
