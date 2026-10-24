@props(['vehicle'])
@php
    // This schema stores the theft-specific coordinates on vehicles, not on theft_reports.
    $latitude = $vehicle->theft_latitude;
    $longitude = $vehicle->theft_longitude;
    $hasCoordinates = is_numeric($latitude) && is_numeric($longitude)
        && (float) $latitude >= -90 && (float) $latitude <= 90
        && (float) $longitude >= -180 && (float) $longitude <= 180;
    $location = array_filter([
        'Dirección' => $vehicle->theft_report_address,
        'Municipio' => $vehicle->municipality,
        'Departamento' => $vehicle->department,
    ], fn ($value) => filled($value));
@endphp
<section class="mt-4" aria-labelledby="reported-theft-location-heading">
    <h2 id="reported-theft-location-heading" class="h5">Ubicación reportada del robo</h2>
    <p class="small text-secondary">Ubicación declarada en el reporte; el mapa no verifica por sí mismo que el hecho ocurrió.</p>
    @if($location)
        <dl class="row">
            @foreach($location as $label => $value)
                <dt class="col-sm-3">{{ $label }}</dt><dd class="col-sm-9">{{ $value }}</dd>
            @endforeach
        </dl>
    @endif
    @if($hasCoordinates)
        <x-leaflet-map id="reported-theft-map" label="Ubicación reportada del robo"
            data-reported-theft-map="true" :data-latitude="(float) $latitude" :data-longitude="(float) $longitude"
            :data-location="json_encode(array_values($location))" />
    @else
        <p class="text-secondary" role="status">No se registró una ubicación con coordenadas para este reporte</p>
    @endif
</section>
