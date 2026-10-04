@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-person-gear"></i> Editar Usuario: {{ $usuario->name }}</h2>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.usuarios.update', $usuario->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $usuario->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $usuario->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="role_id" class="form-control @error('role_id') is-invalid @enderror" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $usuario->role_id) == $role->id ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                    @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nueva Contraseña (Dejar vacío para no cambiar)</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Actualizar</button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection