<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Services\ReportLifecycle;
use App\Support\VehicleSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    private function authorizeOwner(Vehicle $vehicle): void
    {
        abort_unless(auth()->user()->role?->name === 'admin' || $vehicle->registered_by === auth()->id(), 403);
    }

    public function index()
    {
        $vehicles = Vehicle::with('registeredBy', 'theftReport')->when(auth()->user()->role?->name !== 'admin', fn ($q) => $q->where('registered_by', auth()->id()))->latest()->paginate(15);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('vehicles.create');
    }

    public function show(Vehicle $vehiculo)
    {
        $this->authorizeOwner($vehiculo);
        $vehiculo->load('registeredBy', 'theftReport');

        return view('vehicles.show', compact('vehiculo'));
    }

    public function edit(Vehicle $vehiculo)
    {
        $this->authorizeOwner($vehiculo);
        $vehiculo->load('theftReport');

        return view('vehicles.edit', compact('vehiculo'));
    }

    private function persist(Request $request, ?Vehicle $vehicle = null)
    {
        if ($vehicle) {
            $this->authorizeOwner($vehicle);
        }
        VehicleSearch::normalize($request);
        $data = $request->validate([
            'vin' => ['required', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/', Rule::unique('vehicles')->ignore($vehicle)],
            'plate' => ['required', 'regex:/^[A-Z]{1,2}[0-9]{3}[A-Z]{3}$/', Rule::unique('vehicles')->ignore($vehicle)],
            'brand' => 'required|string|max:100', 'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:'.date('Y'), 'color' => 'nullable|string|max:50',
            'department' => 'required|string|max:100', 'municipality' => 'required|string|max:100',
            'zone' => 'nullable|string|max:50', 'observations' => 'nullable|string|max:5000',
            'status' => 'nullable|in:activo,reportado,vendido,inactivo',
            'has_theft_report' => 'nullable|in:1',
            'theft_report_date' => 'nullable|date|before_or_equal:today|required_if:has_theft_report,1',
            'theft_description' => 'nullable|string|max:5000|required_if:has_theft_report,1',
            'evidence_reference' => 'nullable|string|max:255',
            'review_action' => ['nullable', Rule::in(['validar', 'invalidar', 'resolver', 'reabrir'])],
            'review_reason' => 'nullable|string|max:2000|required_with:review_action',
            'theft_report_address' => 'nullable|string|max:255',
            'theft_latitude' => 'nullable|required_with:theft_longitude|numeric|between:-90,90', 'theft_longitude' => 'nullable|required_with:theft_latitude|numeric|between:-180,180',
        ]);
        DB::transaction(function () use ($request, $vehicle, $data) {
            $creating = ! $vehicle;
            if ($vehicle) {
                $vehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            }
            $old = $vehicle?->load('theftReport')->toArray();
            $vehicle ??= new Vehicle(['registered_by' => auth()->id()]);
            $vehicle->fill(collect($data)->except(['has_theft_report', 'theft_report_date', 'theft_description', 'theft_report_status', 'evidence_reference', 'review_action', 'review_reason'])->all());
            $vehicle->status = ($data['status'] ?? $vehicle->status) ?: 'activo';
            if ($vehicle->status === 'reportado') {
                $vehicle->status = 'activo';
            }
            $vehicle->save();
            $report = $vehicle->theftReport;
            if ($request->boolean('has_theft_report')) {
                $report = app(ReportLifecycle::class)->save($vehicle, $data, auth()->user());
                $vehicle->status = $report->case_status === 'abierto' && $report->validation_status !== 'rechazado' ? 'reportado' : 'activo';
            } elseif ($report && $report->case_status !== 'retirado') {
                abort_unless(auth()->user()->role?->name === 'admin' || $report->reported_by === auth()->id(), 403);
                if (auth()->user()->role?->name !== 'admin') {
                    abort_if($report->case_status !== 'abierto' || $report->validation_status === 'rechazado', 403, 'Este caso requiere intervención administrativa.');
                } elseif (empty($data['review_reason'])) {
                    throw ValidationException::withMessages(['review_reason' => 'Indica el motivo del retiro administrativo.']);
                }
                $before = $report->toArray();
                $report->case_status = 'retirado';
                $report->status = 'retirado';
                $report->updated_by = auth()->id();
                if (auth()->user()->role?->name === 'admin') {
                    $report->review_reason = $data['review_reason'];
                }
                $report->save();
                $this->audit('withdraw_report', 'theft_reports', $report->id, $before, $report->toArray());
            }
            $vehicle->save();
            $this->audit($creating ? 'create' : 'update', 'vehicles', $vehicle->id, $old, $vehicle->fresh()->load('theftReport')->toArray());
        });

        return redirect()->route('vehiculos.index')->with('success', 'Registro guardado. La validación automática comprueba integridad y autorización; no confirma por sí sola que ocurrió un robo.');
    }

    public function store(Request $request)
    {
        return $this->persist($request);
    }

    public function update(Request $request, Vehicle $vehiculo)
    {
        return $this->persist($request, $vehiculo);
    }

    private function audit($action, $table, $id, $old, $new): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'table_name' => $table, 'record_id' => $id, 'old_values' => $old, 'new_values' => $new, 'ip_address' => request()->ip()]);
    }

    public function destroy(Vehicle $vehiculo)
    {
        $this->authorizeOwner($vehiculo);
        abort_unless(auth()->user()->role?->name === 'admin' || ! $vehiculo->theftReport || $vehiculo->theftReport->reported_by === auth()->id(), 403);
        DB::transaction(function () use ($vehiculo) {
            $this->audit('delete', 'vehicles', $vehiculo->id, $vehiculo->load('theftReport')->toArray(), null);
            $vehiculo->delete();
        });

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo eliminado.');
    }
}
