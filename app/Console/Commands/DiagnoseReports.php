<?php

namespace App\Console\Commands;

use App\Models\TheftReport;
use Illuminate\Console\Command;

class DiagnoseReports extends Command
{
    protected $signature = 'reports:diagnose {--details : Incluir solo IDs y motivos, sin datos personales}';

    protected $description = 'Diagnóstico de solo lectura de reportes históricos; nunca reclasifica registros';

    public function handle(): int
    {
        $states = [];
        $blockers = [];
        $details = [];
        $seen = [];
        TheftReport::with(['vehicle', 'reportedBy' => fn ($q) => $q->select('id', 'role_id', 'is_active', 'email_verified_at')->with('role')])->orderBy('id')->chunkById(100, function ($reports) use (&$states, &$blockers, &$details, &$seen) {
            foreach ($reports as $report) {
                $states[$report->status] = ($states[$report->status] ?? 0) + 1;
                $reasons = [];
                $v = $report->vehicle;
                $author = $report->reportedBy;
                if (! $v) {
                    $reasons[] = 'vehiculo_ausente';
                }
                if (! $v || ! preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', (string) $v->vin)) {
                    $reasons[] = 'vin_invalido';
                }
                if (! $v || ! preg_match('/^[A-Z]{1,2}[0-9]{3}[A-Z]{3}$/', (string) $v->plate)) {
                    $reasons[] = 'placa_invalida';
                }
                if ($v && (($v->theft_latitude === null) !== ($v->theft_longitude === null) ||
                    ($v->theft_latitude !== null && (! is_numeric($v->theft_latitude) || abs((float) $v->theft_latitude) > 90)) ||
                    ($v->theft_longitude !== null && (! is_numeric($v->theft_longitude) || abs((float) $v->theft_longitude) > 180)))) {
                    $reasons[] = 'coordenadas_inconsistentes';
                }
                if ($v && isset($seen[strtoupper(trim($v->vin))])) {
                    $reasons[] = 'reporte_vin_repetido';
                }
                if ($v) {
                    $seen[strtoupper(trim($v->vin))] = true;
                }
                if (! $author || ! $author->is_active || ! $author->hasVerifiedEmail() || ! in_array($author->role?->name, ['admin', 'registro'], true)) {
                    $reasons[] = 'autor_sin_autorizacion_actual_acreditada';
                }
                if ($author && $v && $v->registered_by !== $author->id && $author->role?->name !== 'admin') {
                    $reasons[] = 'relacion_autor_vehiculo';
                }
                foreach (['brand', 'model', 'department', 'municipality', 'theft_report_address'] as $field) {
                    if (! $v || ! trim((string) $v->$field)) {
                        $reasons[] = 'falta_'.$field;
                    }
                }
                if (! $report->report_date || $report->report_date->isFuture()) {
                    $reasons[] = 'fecha_invalida';
                }
                if (! trim((string) $report->description)) {
                    $reasons[] = 'falta_descripcion';
                }
                if (! $report->evidence_reference) {
                    $reasons[] = 'falta_referencia_documental';
                }
                if (! $report->validation_method || ! $report->validated_at) {
                    $reasons[] = 'sin_trazabilidad_validacion';
                }
                foreach ($reasons as $reason) {
                    $blockers[$reason] = ($blockers[$reason] ?? 0) + 1;
                }
                if ($this->option('details')) {
                    $details[] = ['report_id' => $report->id, 'motivos' => $reasons];
                }
            }
        });
        $this->line(json_encode(['solo_lectura' => true, 'estados_legacy' => $states, 'bloqueos' => $blockers,
            'detalle' => $details, 'nota' => 'Cumplir reglas hoy no acredita el hecho ni autoriza una reclasificación masiva.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
