@extends('layouts.app')
@section('content')
<h1 class="h2">Roles y permisos</h1>
<p class="text-secondary">Los permisos están definidos en el servidor. Asigna el rol de cada cuenta desde Usuarios.</p>
<div class="row g-4">
@foreach($roles as $role)
<section class="col-lg-4"><div class="card p-4 h-100">
<h2 class="h4">{{ $role->name }}</h2>
<p>{{ $role->users_count }} cuentas asignadas</p>
<p>{{ ['admin'=>'Administración completa, revisión de reportes y bitácora.', 'consulta'=>'Consultas, análisis por región e historial propio.', 'registro'=>'Creación de vehículos y reportes; edición y eliminación de registros propios.'][$role->name] ?? 'Sin módulos autorizados.' }}</p>
<form action="{{ route('admin.roles.update', $role) }}" method="POST">
@csrf @method('PATCH')
<label class="form-label" for="description-{{ $role->id }}">Descripción</label>
<input class="form-control mb-3" id="description-{{ $role->id }}" name="description" value="{{ $role->description }}" maxlength="255" required>
<button class="btn btn-outline-primary">Guardar descripción</button>
</form></div></section>
@endforeach
</div>
@endsection
