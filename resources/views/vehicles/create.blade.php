   @extends('layouts.app')

   @section('content')
   <div class="container py-4">
       <h2 class="mb-4">Registrar Nuevo Vehículo</h2>
       
       <form action="{{ route('vehiculos.store') }}" method="POST">
           @csrf
           
           <div class="row">
               <div class="col-md-6 mb-3">
                   <label for="vin" class="form-label">VIN (Número de Identificación Vehicular) *</label>
                   <input type="text" class="form-control @error('vin') is-invalid @enderror" 
                          id="vin" name="vin" maxlength="17" value="{{ old('vin') }}" required>
                   <small class="text-muted">17 caracteres alfanuméricos (sin I, O, Q)</small>
                   @error('vin') <div class="invalid-feedback">{{ $message }}</div> @enderror
               </div>
               
               <div class="col-md-6 mb-3">
                   <label for="plate" class="form-label">Placa *</label>
                   <input type="text" class="form-control @error('plate') is-invalid @enderror" 
                          id="plate" name="plate" maxlength="20" value="{{ old('plate') }}" required>
                   @error('plate') <div class="invalid-feedback">{{ $message }}</div> @enderror
               </div>
           </div>
           
           <div class="row">
               <div class="col-md-6 mb-3">
                   <label for="brand" class="form-label">Marca *</label>
                   <input type="text" class="form-control @error('brand') is-invalid @enderror" 
                          id="brand" name="brand" value="{{ old('brand') }}" required>
                   @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
               </div>
               
               <div class="col-md-6 mb-3">
                   <label for="model" class="form-label">Modelo *</label>
                   <input type="text" class="form-control @error('model') is-invalid @enderror" 
                          id="model" name="model" value="{{ old('model') }}" required>
                   @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
               </div>
           </div>

           <div class="row">
               <div class="col-md-6 mb-3">
                   <label for="year" class="form-label">Año</label>
                   <input type="number" class="form-control" id="year" name="year" min="1900" max="{{ date('Y') }}">
               </div>
               <div class="col-md-6 mb-3">
                   <label for="color" class="form-label">Color</label>
                   <input type="text" class="form-control" id="color" name="color">
               </div>
           </div>
           
           <div class="mb-3">
               <label for="theft_report_address" class="form-label">Dirección del Reporte de Robo</label>
               <input type="text" class="form-control" id="theft_report_address" name="theft_report_address" 
                      placeholder="Ej: Zona 10, Ciudad de Guatemala (Dejar en blanco si no aplica)">
           </div>
           
           <div class="mb-3">
               <label for="observations" class="form-label">Observaciones Adicionales</label>
               <textarea class="form-control" id="observations" name="observations" rows="4" 
                         placeholder="Detalles adicionales sobre el estado o historial del vehículo..."></textarea>
           </div>
           
           <button type="submit" class="btn btn-primary">
               <i class="bi bi-save"></i> Guardar Vehículo
           </button>
           <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancelar</a>
       </form>
   </div>
   @endsection