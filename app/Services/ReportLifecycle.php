<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

class ReportLifecycle
{
    public function deletePhotos(TheftReport $report): void
    {
        app(ReportPhotos::class)->scheduleDeletion($report);
    }

    // Called inside the vehicle transaction, with its row locked on updates.
    public function save(Vehicle $vehicle, array $data, User $actor): TheftReport
    {
        abort_unless($actor->is_active && $actor->hasVerifiedEmail() && in_array($actor->role?->name, ['admin', 'registro'], true), 403);
        $report = $vehicle->theftReport;
        $new = ! $report;
        $historical = ! $new && $report->validation_method === null;
        abort_unless($new || $actor->role?->name === 'admin' || $report->reported_by === $actor->id, 403);
        $old = $report?->toArray();
        $changed = ! $new && ($report->description !== $data['theft_description'] || $report->report_date->format('Y-m-d') !== $data['theft_report_date']
            || $report->evidence_reference !== ($data['evidence_reference'] ?? null) || $vehicle->wasChanged(['vin', 'plate', 'department', 'municipality', 'zone', 'theft_report_address', 'theft_latitude', 'theft_longitude']));
        $action = ! $new && $actor->role?->name === 'admin' ? ($data['review_action'] ?? null) : null;
        if (! $new && $actor->role?->name === 'admin' && ($changed || $action) && empty($data['review_reason'])) {
            throw ValidationException::withMessages(['review_reason' => 'Indica el motivo de la corrección o decisión administrativa.']);
        }
        if (! $new && $changed && $report->validation_status === 'rechazado' && $action !== 'validar') {
            throw ValidationException::withMessages(['review_action' => 'Un reporte invalidado requiere revisión administrativa explícita para volver a validarlo.']);
        }
        if ($new && TheftReport::where('vehicle_id', $vehicle->id)->exists()) {
            throw ValidationException::withMessages(['vin' => 'El vehículo ya tiene un reporte. Edita el reporte existente.']);
        }
        // Registry users cannot silently undo administrative decisions or reopen closed cases.
        if (! $new && ($changed || $action) && $actor->role?->name !== 'admin' && ($report->case_status !== 'abierto' || $report->validation_status === 'rechazado')) {
            throw ValidationException::withMessages(['theft_description' => 'Este caso requiere una corrección administrativa; no puede reabrirse desde registro.']);
        }
        $report ??= new TheftReport(['vehicle_id' => $vehicle->id, 'reported_by' => $actor->id]);
        $report->report_date = $data['theft_report_date'];
        $report->description = $data['theft_description'];
        $report->evidence_reference = $data['evidence_reference'] ?? null;
        $report->updated_by = $actor->id;
        if ($new || $changed || $action === 'validar') {
            if (empty($report->evidence_reference) || ! $vehicle->department || ! $vehicle->municipality || ! $vehicle->theft_report_address) {
                throw ValidationException::withMessages(['evidence_reference' => 'Completa la referencia documental y la ubicación antes de validar el reporte.']);
            }
            // Invalidation/resolution alone does not confer validation on historical reports.
            if ((! $action && ! $historical) || $new || $action === 'validar') {
                $report->validation_status = 'validado';
                $report->validation_method = $action === 'validar' || (! $new && $actor->role?->name === 'admin') ? 'administrativa' : 'automatica';
                $report->validated_at = now();
                $report->validated_by = $actor->id;
            }
        }
        if ($action) {
            $report->review_reason = $data['review_reason'];
            if ($action === 'invalidar') {
                $report->validation_status = 'rechazado';
            }
            if ($action === 'resolver') {
                $report->case_status = 'resuelto';
            }
            if ($action === 'reabrir') {
                $report->case_status = 'abierto';
            }
        }
        if (! $new && $changed && $actor->role?->name === 'admin') {
            $report->review_reason = $data['review_reason'];
        }
        $report->case_status ??= 'abierto';
        $report->validation_status ??= 'pendiente';
        $report->status = $report->displayStatus();
        $report->save();
        AuditLog::create(['user_id' => $actor->id, 'action' => $new ? 'create_report' : ($action ? 'report_'.$action : 'update_report'),
            'table_name' => 'theft_reports', 'record_id' => $report->id, 'old_values' => $old, 'new_values' => $report->toArray(), 'ip_address' => request()->ip()]);

        return $report;
    }
}
