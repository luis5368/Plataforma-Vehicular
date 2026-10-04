@extends('layouts.app')

@section('title', 'Consultar Vehículo')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-search"></i> Consultar Vehículo por Placa
                    </h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">
                        Ingresa el número de placa del vehículo que deseas consultar. 
                        El sistema verificará si existe algún reporte de robo asociado.
                    </p>

                    <form action="{{ route('consultar.buscar') }}" method="GET">
                        <div class="mb-3">
                            <label for="plate" class="form-label fw-bold">
                                Número de Placa <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg @error('plate') is-invalid @enderror" 
                                id="plate" 
                                name="plate" 
                                value="{{ old('plate') }}"
                                placeholder="Ej: P123ABC"
                                required
                                autofocus
                            >
                            @error('plate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Ingresa la placa completa sin espacios</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-search"></i> Consultar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle"></i> 
                <strong>Nota:</strong> Todas las consultas quedan registradas en tu historial 
                para fines de trazabilidad.
            </div>
        </div>
    </div>
</div>
@endsection