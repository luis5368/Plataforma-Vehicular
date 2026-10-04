@extends('layouts.app')

@section('title', 'Editar Vehículo')

@section('content')
<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-pencil"></i> Editar Vehículo</h2>
    
    <!-- ========================================== -->
<!-- SECCIÓN DEL MAPA INTERACTIVO (Leaflet.js) -->
<!-- ========================================== -->
<div class="mb-4 p-3 bg-light border rounded">
    <h5 class="mb-3"><i class="bi bi-geo-alt-fill text-danger"></i> Ubicación del Reporte de Robo</h5>
    
    <div class="mb-3">
        <label for="theft_report_address" class="form-label">Dirección o Referencia (Opcional)</label>
        <input type="text" class="form-control" id="theft_report_address" name="theft_report_address" 
               value="{{ old('theft_report_address') }}" placeholder="Ej: Frente al Centro Comercial Oakland, Zona 10">
    </div>

    <p class="small text-muted mb-2">Haz clic en el mapa para marcar la ubicación exacta, o usa el buscador.</p>
    
    <!-- Contenedor del Mapa -->
    <div id="map" style="height: 350px; width: 100%; border-radius: 8px; border: 2px solid #dee2e6;"></div>
    
    <!-- Campos ocultos para enviar las coordenadas a Laravel -->
    <input type="hidden" id="theft_latitude" name="theft_latitude" value="{{ old('theft_latitude') }}">
    <input type="hidden" id="theft_longitude" name="theft_longitude" value="{{ old('theft_longitude') }}">
    
    <div class="mt-2">
        <small class="text-primary fw-bold">
            Coordenadas seleccionadas: 
            <span id="coords-display">No seleccionadas</span>
        </small>
    </div>
</div>

<!-- Estilos y Scripts de Leaflet (OpenStreetMap) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Plugin de búsqueda de direcciones (Geocoder) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
    // 1. Inicializar el mapa centrado en la Ciudad de Guatemala
    var map = L.map('map').setView([14.6349, -90.5069], 12);

    // 2. Cargar las "baldosas" (tiles) de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker;

    // 3. Función para actualizar el marcador y los campos ocultos
    function updateMarker(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], {draggable: true}).addTo(map);
            
            // Permitir que el usuario arrastre el marcador para ajustar
            marker.on('dragend', function(event) {
                var position = marker.getLatLng();
                document.getElementById('theft_latitude').value = position.lat;
                document.getElementById('theft_longitude').value = position.lng;
                document.getElementById('coords-display').innerText = position.lat.toFixed(6) + ', ' + position.lng.toFixed(6);
            });
        }
        
        // Actualizar campos ocultos y texto en pantalla
        document.getElementById('theft_latitude').value = lat;
        document.getElementById('theft_longitude').value = lng;
        document.getElementById('coords-display').innerText = lat.toFixed(6) + ', ' + lng.toFixed(6);
        
        // Centrar el mapa en la nueva ubicación
        map.setView([lat, lng], 15);
    }

    // 4. Evento: Al hacer clic en el mapa
    map.on('click', function(e) {
        updateMarker(e.latlng.lat, e.latlng.lng);
    });

    // 5. Agregar el buscador de direcciones (Geocoder)
    L.Control.geocoder({
        defaultMarkGeocode: false
    })
    .on('markgeocode', function(e) {
        var bbox = e.geocode.bbox;
        var poly = L.polygon([
            bbox.getSouthEast(),
            bbox.getNorthEast(),
            bbox.getNorthWest(),
            bbox.getSouthWest()
        ]);
        map.fitBounds(poly.getBounds());
        updateMarker(e.geocode.center.lat, e.geocode.center.lng);
    })
    .addTo(map);

    // 6. Si estamos editando y ya hay coordenadas, mostrar el marcador
    var initialLat = document.getElementById('theft_latitude').value;
    var initialLng = document.getElementById('theft_longitude').value;
    if (initialLat && initialLng) {
        updateMarker(parseFloat(initialLat), parseFloat(initialLng));
    }
</script>

    <form action="{{ route('vehiculos.update', $vehiculo->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="vin" class="form-label">VIN *</label>
                <input type="text" class="form-control @error('vin') is-invalid @enderror" 
                       id="vin" name="vin" maxlength="17" value="{{ old('vin', $vehiculo->vin) }}" required>
                @error('vin') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="plate" class="form-label">Placa *</label>
                <input type="text" class="form-control @error('plate') is-invalid @enderror" 
                       id="plate" name="plate" maxlength="20" value="{{ old('plate', $vehiculo->plate) }}" required>
                @error('plate') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="brand" class="form-label">Marca *</label>
                <input type="text" class="form-control @error('brand') is-invalid @enderror" 
                       id="brand" name="brand" value="{{ old('brand', $vehiculo->brand) }}" required>
                @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="model" class="form-label">Modelo *</label>
                <input type="text" class="form-control @error('model') is-invalid @enderror" 
                       id="model" name="model" value="{{ old('model', $vehiculo->model) }}" required>
                @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="year" class="form-label">Año</label>
                <input type="number" class="form-control" id="year" name="year" 
                       min="1900" max="{{ date('Y') }}" value="{{ old('year', $vehiculo->year) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="color" class="form-label">Color</label>
                <input type="text" class="form-control" id="color" name="color" 
                       value="{{ old('color', $vehiculo->color) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="status" class="form-label">Estado *</label>
                <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                    <option value="activo" {{ old('status', $vehiculo->status) === 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="reportado" {{ old('status', $vehiculo->status) === 'reportado' ? 'selected' : '' }}>Reportado</option>
                    <option value="vendido" {{ old('status', $vehiculo->status) === 'vendido' ? 'selected' : '' }}>Vendido</option>
                    <option value="inactivo" {{ old('status', $vehiculo->status) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="theft_report_address" class="form-label">Dirección del Reporte de Robo</label>
            <input type="text" class="form-control" id="theft_report_address" name="theft_report_address" 
                   value="{{ old('theft_report_address', $vehiculo->theft_report_address) }}">
        </div>

        <div class="mb-3">
            <label for="observations" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observations" name="observations" rows="4">{{ old('observations', $vehiculo->observations) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Guardar Cambios
        </button>
        <a href="{{ route('vehiculos.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection