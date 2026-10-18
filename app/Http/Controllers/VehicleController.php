<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TheftReport;
use App\Models\Vehicle;
use App\Support\VehicleSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'theft_report_status' => ['nullable', Rule::in(['pendiente_validacion', 'validado', 'rechazado', 'resuelto'])],
            'theft_report_address' => 'nullable|string|max:255',
            'theft_latitude' => 'nullable|numeric|between:-90,90', 'theft_longitude' => 'nullable|numeric|between:-180,180',
        ]);
        DB::transaction(function () use ($request, $vehicle, $data) {
            $old = $vehicle?->load('theftReport')->toArray();
            $creating = ! $vehicle;
            if ($vehicle) {
                $vehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            }
            $vehicle ??= new Vehicle(['registered_by' => auth()->id()]);
            $vehicle->fill(collect($data)->except(['has_theft_report', 'theft_report_date', 'theft_description', 'theft_report_status'])->all());
            $vehicle->status = ($data['status'] ?? $vehicle->status) ?: 'activo';
            if ($vehicle->status === 'reportado') {
                $vehicle->status = 'activo';
            }
            $vehicle->save();
            $report = $vehicle->theftReport;
            if ($request->boolean('has_theft_report')) {
                $before = $report?->toArray();
                $new = ! $report;
                $report ??= new TheftReport(['vehicle_id' => $vehicle->id, 'reported_by' => auth()->id()]);
                abort_unless($new || auth()->user()->role?->name === 'admin' || $report->reported_by === auth()->id(), 403);
                $changed = ! $new && ($report->description !== $data['theft_description'] || $report->report_date->format('Y-m-d') !== $data['theft_report_date']);
                $report->fill(['description' => $data['theft_description'], 'report_date' => $data['theft_report_date'], 'updated_by' => auth()->id()]);
                $report->status = $new || $changed ? 'pendiente_validacion' : $report->status;
                if (! $new && auth()->user()->role?->name === 'admin' && isset($data['theft_report_status'])) {
                    $report->status = $data['theft_report_status'];
                }
                $report->save();
                $this->audit($new ? 'create_report' : 'update_report', 'theft_reports', $report->id, $before, $report->toArray());
                $vehicle->status = in_array($report->status, ['pendiente_validacion', 'validado']) ? 'reportado' : 'activo';
            } elseif ($report) {
                abort_unless(auth()->user()->role?->name === 'admin' || $report->reported_by === auth()->id(), 403);
                $this->audit('withdraw_report', 'theft_reports', $report->id, $report->toArray(), null);
                $report->delete();
            }
            $vehicle->save();
            $this->audit($creating ? 'create' : 'update', 'vehicles', $vehicle->id, $old, $vehicle->fresh()->load('theftReport')->toArray());
        });

        return redirect()->route('vehiculos.index')->with('success', 'Registro guardado. Los reportes nuevos quedan pendientes de validación.');
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
