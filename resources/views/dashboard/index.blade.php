@extends('layouts.app')
@section('content')
<header class="dashboard-heading"><div><span class="eyebrow">CENTRO DE INFORMACIÓN VEHICULAR</span><h1>Panel de Control</h1><p>Explora los reportes y su contexto con los filtros geográficos.</p></div><span class="badge bg-primary"><i class="bi bi-person-badge" aria-hidden="true"></i> {{ $user->role?->name }}</span></header>
@include('reports.filters')
@include('reports.analytics')
<p class="mt-4 text-secondary">{{ $stats['total_users'] }} usuarios · {{ $stats['total_consultations'] }} consultas globales · {{ $stats['consultations_today'] }} consultas hoy</p>
<div class="d-flex gap-2 flex-wrap mt-4">@if($user->role?->name === 'admin')<a class="btn btn-primary" href="{{ route('vehiculos.create') }}">Registrar vehículo</a>@endif<a class="btn btn-outline-primary" href="{{ route('consultar.index') }}">Consultar por VIN</a><a class="btn btn-outline-primary" href="{{ route('consultar.region', $filters) }}">Análisis por región</a><a class="btn btn-outline-primary" href="{{ route('consultar.historial') }}">Mi historial</a></div>
@endsection
