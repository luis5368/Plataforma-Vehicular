@props(['found', 'status'])
@php
    // displayStatus already gives the case status precedence over validation.
    [$tone, $icon, $message] = match (true) {
        ! $found => ['neutral', 'question-circle', 'Sin coincidencia para el VIN o la placa.'],
        $status === 'resuelto' => ['neutral', 'check-circle', 'Reporte resuelto.'],
        $status === 'retirado' => ['neutral', 'dash-circle', 'Reporte retirado.'],
        $status === 'validado' => ['danger', 'exclamation-octagon', 'Hay un reporte de robo vigente registrado.'],
        $status === 'sin_reporte' => ['success', 'check-circle', 'No se encontró un reporte vigente en InfoVehicularGT. Esto no garantiza que el vehículo esté legalmente libre de robo.'],
        default => ['warning', 'exclamation-triangle', 'Hay un reporte pendiente o no confirmado.'],
    };
@endphp
<div class="public-report-signal public-report-signal--{{ $tone }} mb-3" role="status" data-report-signal="{{ $tone }}">
    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    <span>{{ $message }}</span>
</div>
