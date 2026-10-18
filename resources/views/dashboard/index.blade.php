@extends('layouts.app')
@section('content')
<h1 class="h2">Panel de Control <span class="badge bg-primary">{{ $user->role?->name }}</span></h1>
@include('reports.filters')
@include('reports.analytics')
<p class="mt-4 text-secondary">{{ $stats['total_users'] }} usuarios · {{ $stats['total_consultations'] }} consultas globales · {{ $stats['consultations_today'] }} consultas hoy</p>
<div class="d-flex gap-2 flex-wrap mt-4">@if($user->role?->name === 'admin')<a class="btn btn-primary" href="{{ route('vehiculos.create') }}">Registrar vehículo</a>@endif<a class="btn btn-outline-primary" href="{{ route('consultar.index') }}">Consultar por VIN</a><a class="btn btn-outline-primary" href="{{ route('consultar.region', $filters) }}">Análisis por región</a><a class="btn btn-outline-primary" href="{{ route('consultar.historial') }}">Mi historial</a></div>
@endsection
