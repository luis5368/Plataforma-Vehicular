@props(['id', 'label', 'height' => '360px'])
<p id="{{ $id }}-status" class="p-3 text-secondary" role="status" aria-live="polite">Cargando mapa…</p>
<div id="{{ $id }}" {{ $attributes }} role="region" aria-describedby="{{ $id }}-status" aria-label="{{ $label }}" style="height:{{ $height }};width:100%"></div>
<noscript><p class="p-3">Activa JavaScript para ver el mapa. La información del reporte sigue disponible en texto.</p></noscript>
@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endonce
