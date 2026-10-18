@props(['report'])
<x-report-status :status="$report->displayStatus()" />
<p class="small mb-1">Validación: {{ ['validado'=>'completada','pendiente'=>'pendiente','rechazado'=>'invalidada'][$report->validation_status] ?? 'sin acreditar' }} · Caso: {{ $report->case_status }}</p>
<p class="small text-secondary">{{ $report->sourceLabel() }}. No constituye confirmación independiente del hecho.</p>
