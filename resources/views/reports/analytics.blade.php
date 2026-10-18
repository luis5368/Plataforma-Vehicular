<p class="text-secondary">Reportes de usuarios autorizados. La validación automática comprueba integridad técnica y autorización; no es prueba independiente de que ocurrió un robo. Las distribuciones incluyen solo casos abiertos con validación automática, administrativa o validación histórica registrada.</p>
<div class="row g-3 mb-4">
@foreach(['total_vehicles'=>'Vehículos únicos','active_reports'=>'Reportes abiertos validados','resolved_reports'=>'Casos resueltos','pending_reports'=>'Reportes abiertos pendientes'] as $key=>$label)
<div class="col-6 col-lg-3"><div class="card p-3 h-100"><span>{{ $label }}</span><strong class="fs-2">{{ $stats[$key] }}</strong></div></div>
@endforeach
</div>
<div class="row g-4">
@foreach(['topDepartments'=>'Por departamento','topMunicipalities'=>'Por municipio','zoneDistribution'=>'Por zona','topBrands'=>'Por marca','monthlyTrend'=>'Tendencia de reportes validados (incluye resueltos)'] as $variable=>$title)
<section class="col-md-6"><div class="card p-4 h-100"><h2 class="h5">{{ $title }}</h2>
@forelse($$variable as $row)
@php($label = $row->municipality ?? $row->department ?? $row->zone ?? $row->brand ?? $row->month)
<div class="mb-3"><div class="d-flex justify-content-between gap-3"><span>{{ $label }} @if(isset($row->municipality)) · {{ $row->department }} @endif</span><strong>{{ $row->total }}</strong></div><meter class="w-100" min="0" max="{{ max(1, $$variable->max('total')) }}" value="{{ $row->total }}" aria-label="{{ $title }}: {{ $label }}">{{ $row->total }}</meter></div>
@empty<p class="text-secondary">Sin datos disponibles</p>@endforelse
</div></section>
@endforeach
</div>
