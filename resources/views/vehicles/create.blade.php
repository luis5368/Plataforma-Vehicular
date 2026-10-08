@extends('layouts.app')

@section('title', 'Registrar Nuevo Vehículo')

@section('content')
<div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Registrar Nuevo Vehículo</h2>

    <form action="{{ route('vehiculos.store') }}" method="POST">
        @csrf
        
        <!-- 1. Información Básica del Vehículo -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Información General</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="vin" class="form-label fw-bold">VIN (Número de Identificación) *</label>
                        <input type="text" class="form-control @error('vin') is-invalid @enderror" 
                               id="vin" name="vin" maxlength="17" value="{{ old('vin') }}" required style="text-transform: uppercase;">
                        <small class="text-muted">17 caracteres alfanuméricos (sin I, O, Q)</small>
                        @error('vin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="plate" class="form-label fw-bold">Placa *</label>
                        <input type="text" class="form-control @error('plate') is-invalid @enderror" 
                               id="plate" name="plate" maxlength="20" value="{{ old('plate') }}" required style="text-transform: uppercase;">
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
                        <input type="number" class="form-control" id="year" name="year" min="1900" max="{{ date('Y') }}" value="{{ old('year') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="text" class="form-control" id="color" name="color" value="{{ old('color') }}">
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- UBICACIÓN GEOGRÁFICA (NUEVO) -->
                <!-- ========================================== -->
                <hr class="my-3">
                <h6 class="text-primary mb-3"><i class="bi bi-geo"></i> Ubicación Geográfica del Vehículo</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="department" class="form-label fw-bold">Departamento *</label>
                        <select class="form-select @error('department') is-invalid @enderror" id="department" name="department" required onchange="updateMunicipalities()">
                            <option value="">Seleccione...</option>
                            <option value="Guatemala" {{ old('department') == 'Guatemala' ? 'selected' : '' }}>Guatemala</option>
                            <option value="Sacatepéquez" {{ old('department') == 'Sacatepéquez' ? 'selected' : '' }}>Sacatepéquez</option>
                            <option value="Escuintla" {{ old('department') == 'Escuintla' ? 'selected' : '' }}>Escuintla</option>
                            <option value="Quetzaltenango" {{ old('department') == 'Quetzaltenango' ? 'selected' : '' }}>Quetzaltenango</option>
                            <option value="Alta Verapaz" {{ old('department') == 'Alta Verapaz' ? 'selected' : '' }}>Alta Verapaz</option>
                            <option value="Izabal" {{ old('department') == 'Izabal' ? 'selected' : '' }}>Izabal</option>
                            <option value="Petén" {{ old('department') == 'Petén' ? 'selected' : '' }}>Petén</option>
                            <option value="Huehuetenango" {{ old('department') == 'Huehuetenango' ? 'selected' : '' }}>Huehuetenango</option>
                            <option value="Jalapa" {{ old('department') == 'Jalapa' ? 'selected' : '' }}>Jalapa</option>
                            <option value="Jutiapa" {{ old('department') == 'Jutiapa' ? 'selected' : '' }}>Jutiapa</option>
                        </select>
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="municipality" class="form-label fw-bold">Municipio *</label>
                        <select class="form-select @error('municipality') is-invalid @enderror" id="municipality" name="municipality" required>
                            <option value="">Seleccione departamento primero...</option>
                        </select>
                        @error('municipality') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="zone" class="form-label">Zona / Área</label>
                        <input type="text" class="form-control @error('zone') is-invalid @enderror" id="zone" name="zone" value="{{ old('zone') }}" placeholder="Ej: Zona 10, Centro, Casco Urbano">
                        @error('zone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Interruptor de Reporte de Robo -->
        <div class="card shadow-sm mb-4 border-danger">
            <div class="card-body">
                <div class="form-check form-switch p-3 bg-light rounded border">
                    <input class="form-check-input" type="checkbox" id="has_theft_report" name="has_theft_report" value="1" {{ old('has_theft_report') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-danger fs-5" for="has_theft_report">
                        <i class="bi bi-exclamation-triangle-fill"></i> Activar Reporte de Robo
                    </label>
                    <small class="d-block text-muted mt-1">Marque esta opción si el vehículo cuenta con un reporte de robo. Se habilitarán campos adicionales y el mapa de ubicación.</small>
                </div>
            </div>
        </div>

        <!-- 3. Sección Condicional de Reporte de Robo (Oculta por defecto) -->
        <div id="theft_report_section" class="card shadow-sm mb-4" style="display: {{ old('has_theft_report') ? 'block' : 'none' }}; border-left: 5px solid #dc3545;">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-shield-x"></i> Detalles del Reporte de Robo</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="theft_report_date" class="form-label">Fecha del Reporte *</label>
                        <input type="date" class="form-control" id="theft_report_date" name="theft_report_date" value="{{ old('theft_report_date', date('Y-m-d')) }}">
                        @error('theft_report_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="theft_report_status" class="form-label">Estado del Reporte *</label>
                        <select class="form-select" id="theft_report_status" name="theft_report_status">
                            <option value="activo" {{ old('theft_report_status') == 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="resuelto" {{ old('theft_report_status') == 'resuelto' ? 'selected' : '' }}>Resuelto / Recuperado</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="theft_description" class="form-label">Descripción de los Hechos *</label>
                    <textarea class="form-control" id="theft_description" name="theft_description" rows="3" placeholder="Ej: Sustraído por la fuerza en estacionamiento, última vez visto con dos sujetos a bordo...">{{ old('theft_description') }}</textarea>
                    @error('theft_description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="theft_report_address" class="form-label">Dirección o Referencia del Lugar</label>
                    <input type="text" class="form-control" id="theft_report_address" name="theft_report_address" 
                           value="{{ old('theft_report_address') }}" placeholder="Ej: Frente al Centro Comercial Oakland, Zona 10">
                </div>

                <!-- MAPA INTERACTIVO (Leaflet.js + OpenStreetMap) -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Ubicación Exacta en el Mapa</label>
                    <p class="small text-muted mb-2">Haz clic en el mapa para marcar la ubicación, o usa el buscador integrado (lupa).</p>
                    
                    <div id="map" style="height: 350px; width: 100%; border-radius: 8px; border: 2px solid #dee2e6; z-index: 1;"></div>
                    
                    <!-- Campos ocultos para enviar las coordenadas a Laravel -->
                    <input type="hidden" id="theft_latitude" name="theft_latitude" value="{{ old('theft_latitude') }}">
                    <input type="hidden" id="theft_longitude" name="theft_longitude" value="{{ old('theft_longitude') }}">
                    
                    <div class="mt-2">
                        <small class="text-primary fw-bold">
                            Coordenadas: <span id="coords-display">{{ old('theft_latitude') && old('theft_longitude') ? old('theft_latitude').', '.old('theft_longitude') : 'No seleccionadas' }}</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Observaciones Generales -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <label for="observations" class="form-label fw-bold">Observaciones Adicionales</label>
                <textarea class="form-control" id="observations" name="observations" rows="3" 
                          placeholder="Detalles adicionales sobre el estado, mecánica o historial del vehículo...">{{ old('observations') }}</textarea>
            </div>
        </div>
        
        <!-- Botones de Acción -->
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save"></i> Guardar Vehículo
            </button>
            <a href="{{ route('vehiculos.index') }}" class="btn btn-secondary btn-lg">
                <i class="bi bi-x-circle"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<!-- Estilos y Scripts de Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const theftCheckbox = document.getElementById('has_theft_report');
    const theftSection = document.getElementById('theft_report_section');
    const theftDate = document.getElementById('theft_report_date');
    const theftDesc = document.getElementById('theft_description');

    // 1. Inicializar mapa centrado en Ciudad de Guatemala
    var map = L.map('map').setView([14.6349, -90.5069], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker;

    // 2. Función para actualizar el marcador y los campos ocultos
    function updateMarker(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], {draggable: true}).addTo(map);
            marker.on('dragend', function(event) {
                var position = marker.getLatLng();
                document.getElementById('theft_latitude').value = position.lat;
                document.getElementById('theft_longitude').value = position.lng;
                document.getElementById('coords-display').innerText = position.lat.toFixed(6) + ', ' + position.lng.toFixed(6);
            });
        }
        document.getElementById('theft_latitude').value = lat;
        document.getElementById('theft_longitude').value = lng;
        document.getElementById('coords-display').innerText = lat.toFixed(6) + ', ' + lng.toFixed(6);
        map.setView([lat, lng], 15);
    }

    // 3. Evento: Al hacer clic en el mapa
    map.on('click', function(e) {
        updateMarker(e.latlng.lat, e.latlng.lng);
    });

    // 4. Agregar el buscador de direcciones (Geocoder)
    L.Control.geocoder({ defaultMarkGeocode: false })
    .on('markgeocode', function(e) {
        var bbox = e.geocode.bbox;
        var poly = L.polygon([bbox.getSouthEast(), bbox.getNorthEast(), bbox.getNorthWest(), bbox.getSouthWest()]);
        map.fitBounds(poly.getBounds());
        updateMarker(e.geocode.center.lat, e.geocode.center.lng);
    }).addTo(map);

    // 5. Si hay datos guardados (por error de validación), mostrar el marcador
    var initialLat = document.getElementById('theft_latitude').value;
    var initialLng = document.getElementById('theft_longitude').value;
    if (initialLat && initialLng) {
        updateMarker(parseFloat(initialLat), parseFloat(initialLng));
    }

    // 6. Lógica para mostrar/ocultar la sección de robo
    function toggleTheftSection() {
        if (theftCheckbox.checked) {
            theftSection.style.display = 'block';
            theftDate.required = true;
            theftDesc.required = true;
            
            // FIX CRUCIAL: Forzar a Leaflet a recalcular su tamaño al hacerse visible
            setTimeout(function(){ map.invalidateSize(); }, 200);
            
            theftSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            theftSection.style.display = 'none';
            theftDate.required = false;
            theftDesc.required = false;
            
            // Limpiar campos
            theftDate.value = '';
            theftDesc.value = '';
            document.getElementById('theft_report_status').value = 'activo';
            document.getElementById('theft_report_address').value = '';
            document.getElementById('theft_latitude').value = '';
            document.getElementById('theft_longitude').value = '';
            document.getElementById('coords-display').innerText = 'No seleccionadas';
            
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            map.setView([14.6349, -90.5069], 12); // Resetear vista
        }
    }

    theftCheckbox.addEventListener('change', toggleTheftSection);
    toggleTheftSection(); // Ejecutar al cargar por si hay datos antiguos
});

// ==========================================
// LÓGICA DE DEPARTAMENTOS Y MUNICIPIOS
// ==========================================
const municipalitiesByDepartment = {
    'Guatemala': ['Guatemala', 'Mixco', 'Villa Nueva', 'Villa Canales', 'San Miguel Petapa', 'Chinautla', 'Amatitlán', 'San José Pinula', 'San José del Golfo', 'Palencia', 'San Pedro Ayampuc', 'San Raymundo', 'Chuarrancho', 'Fraijanes', 'Santa Catarina Pinula'],
    'Sacatepéquez': ['Antigua Guatemala', 'Ciudad Vieja', 'Jocotenango', 'Pastores', 'Sumpango', 'Santo Domingo Xenacoj', 'Santiago Sacatepéquez', 'San Bartolomé Milpas Altas', 'San Lucas Sacatepéquez', 'Santa Lucía Milpas Altas', 'Magdalena Milpas Altas', 'Santa María de Jesús', 'San Antonio Aguas Calientes', 'San Miguel Dueñas'],
    'Escuintla': ['Escuintla', 'Santa Lucía Cotzumalguapa', 'La Democracia', 'Siquinalá', 'Masagua', 'Tiquisate', 'La Gomera', 'Guanagazapa', 'San José', 'Iztapa', 'Palín', 'San Vicente Pacaya', 'Nueva Concepción'],
    'Quetzaltenango': ['Quetzaltenango', 'Salcajá', 'Olintepeque', 'San Carlos Sija', 'Sibilia', 'Cabricán', 'Cajolá', 'San Miguel Sigüilá', 'San Juan Ostuncalco', 'San Mateo', 'Concepción Chiquirichapa', 'San Martín Sacatepéquez', 'Almolonga', 'Cantel', 'Huitán', 'Zunil', 'Colomba', 'San Francisco La Unión', 'El Palmar', 'Coatepeque', 'Génova', 'Flores Costa Cuca', 'La Esperanza', 'Palestina de Los Altos'],
    'Alta Verapaz': ['Cobán', 'Santa Cruz Verapaz', 'San Cristóbal Verapaz', 'Tactic', 'Tamahú', 'Tucurú', 'Panzós', 'Senahú', 'San Pedro Carchá', 'San Juan Chamelco', 'Lanquín', 'Cahabón', 'Chisec', 'Chahal', 'Fray Bartolomé de las Casas', 'Santa Catalina La Tinta', 'Raxruhá'],
    'Izabal': ['Puerto Barrios', 'Livingston', 'El Estor', 'Morales', 'Los Amates'],
    'Petén': ['Flores', 'San Benito', 'San Andrés', 'La Libertad', 'San Francisco', 'Santa Ana', 'Dolores', 'San Luis', 'Sayaxché', 'Topoxté', 'Poptún', 'Las Cruces', 'El Chal'],
    'Huehuetenango': ['Huehuetenango', 'Chiantla', 'Malacatancito', 'Cuilco', 'Nentón', 'San Pedro Necta', 'Jacaltenango', 'San Pedro Soloma', 'San Ildefonso Ixtahuacán', 'Santa Bárbara', 'La Libertad', 'La Democracia', 'San Miguel Acatán', 'San Rafael La Independencia', 'Todos Santos Cuchumatán', 'San Juan Atitán', 'Santa Eulalia', 'San Mateo Ixtatán', 'Colotenango', 'San Sebastián Huehuetenango', 'Tectitán', 'Concepción Huista', 'San Juan Ixcoy', 'San Antonio Huista', 'San Sebastián Coatán', 'Santa Cruz Barillas', 'Aguacatán', 'San Rafael Petz', 'San Gaspar Ixchil', 'Santiago Chimaltenango', 'San Marcos La Laguna'],
    'Jalapa': ['Jalapa', 'San Pedro Pinula', 'San Luis Jilotepeque', 'San Manuel Chaparrón', 'San Carlos Alzatate', 'Monjas', 'Mataquescuintla'],
    'Jutiapa': ['Jutiapa', 'El Progreso', 'Santa Catarina Mita', 'Agua Blanca', 'Asunción Mita', 'Yupiltepeque', 'Atescatempa', 'Jerez', 'El Adelanto', 'Zapotitlán', 'Comapa', 'Jalpatagua', 'Conguaco', 'Moyuta', 'Pasaco', 'San José Acatempa', 'Quesada']
};

function updateMunicipalities() {
    const department = document.getElementById('department').value;
    const municipalitySelect = document.getElementById('municipality');
    
    municipalitySelect.innerHTML = '<option value="">Seleccione...</option>';
    
    if (department && municipalitiesByDepartment[department]) {
        municipalitiesByDepartment[department].forEach(mun => {
            const option = document.createElement('option');
            option.value = mun;
            option.textContent = mun;
            municipalitySelect.appendChild(option);
        });
    }
}

// Ejecutar al cargar si hay datos antiguos (ej. error de validación)
document.addEventListener('DOMContentLoaded', function() {
    const oldDepartment = '{{ old("department") }}';
    if (oldDepartment) {
        updateMunicipalities();
        const oldMunicipality = '{{ old("municipality") }}';
        if (oldMunicipality) {
            document.getElementById('municipality').value = oldMunicipality;
        }
    }
});
</script>
@endsection