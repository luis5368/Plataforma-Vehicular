@extends('layouts.app')

@section('title', 'Consultar Vehículo')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-search"></i> Consultar Vehículo
                    </h4>
                </div>
                <div class="card-body p-4">
                    
                    <!-- Caja de información contextual (Muy importante para tu defensa académica) -->
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle-fill"></i> 
                        <strong>Método de búsqueda recomendado:</strong> 
                        El <strong>Número VIN</strong> es el identificador más confiable ya que es único e inmutable (grabado en el chasis). 
                        La placa puede ser removida o alterada en caso de robo, por lo que se recomienda como búsqueda alternativa.
                    </div>

                    <form action="{{ route('consultar.buscar') }}" method="GET">
                        
                        <!-- Campo 1: VIN (Principal) -->
                        <div class="mb-4">
                            <label for="vin" class="form-label fw-bold text-primary">
                                <i class="bi bi-fingerprint"></i> Número VIN (Identificación Vehicular) 
                                <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg @error('vin') is-invalid @enderror" 
                                id="vin" 
                                name="vin" 
                                maxlength="17"
                                value="{{ old('vin') }}"
                                placeholder="Ej: 1HGCM82633A004352"
                                autofocus
                                style="text-transform: uppercase;"
                            >
                            @error('vin')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">17 caracteres alfanuméricos. Es el método de búsqueda más seguro y preciso.</small>
                        </div>

                        <div class="text-center my-3 text-muted">
                            <small class="fst-italic">— O busca por placa si no cuentas con el VIN —</small>
                        </div>

                        <!-- Campo 2: Placa (Alternativo) -->
                        <div class="mb-4">
                            <label for="plate" class="form-label fw-bold">
                                <i class="bi bi-tag"></i> Número de Placa
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg @error('plate') is-invalid @enderror" 
                                id="plate" 
                                name="plate" 
                                maxlength="20"
                                value="{{ old('plate') }}"
                                placeholder="Ej: P123ABC"
                                style="text-transform: uppercase;"
                            >
                            @error('plate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Opcional. Úsalo solo si no tienes acceso al número VIN.</small>
                        </div>

                        <!-- Botón de Acción -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-search"></i> Consultar Vehículo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Nota de Trazabilidad -->
            <div class="alert alert-warning mt-3">
                <i class="bi bi-shield-check"></i> 
                <strong>Nota de Trazabilidad:</strong> Todas las consultas quedan registradas en tu historial 
                y en la bitácora de auditoría del sistema para fines de control y seguridad.
            </div>
        </div>
    </div>
</div>
@endsection