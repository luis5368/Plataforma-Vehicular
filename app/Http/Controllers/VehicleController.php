<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\TheftReport;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Muestra la lista de vehículos (GET /vehiculos)
     */
    public function index()
    {
        $vehicles = Vehicle::with('registeredBy')->orderBy('created_at', 'desc')->paginate(15);
        return view('vehicles.index', compact('vehicles'));
    }

    /**
     * Muestra el formulario para crear un nuevo vehículo (GET /vehiculos/create)
     */
    public function create()
    {
        return view('vehicles.create');
    }

    /**
     * Guarda un nuevo vehículo en la base de datos (POST /vehiculos)
     */
    public function store(Request $request)
    {
        // 1. Validar todos los campos del nuevo formulario (Incluyendo los geográficos)
        $validated = $request->validate([
            'vin' => 'required|string|max:17|unique:vehicles,vin',
            'plate' => 'required|string|max:20|unique:vehicles,plate',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'color' => 'nullable|string|max:50',
            // CAMPOS GEOGRÁFICOS AGREGADOS:
            'department' => 'required|string|max:100',
            'municipality' => 'required|string|max:100',
            'zone' => 'nullable|string|max:50',
            // FIN CAMPOS GEOGRÁFICOS
            'has_theft_report' => 'nullable|in:1',
            'theft_report_date' => 'nullable|date|required_if:has_theft_report,1',
            'theft_description' => 'nullable|string|required_if:has_theft_report,1',
            'theft_report_status' => 'nullable|in:activo,resuelto',
            'theft_report_address' => 'nullable|string|max:255',
            'theft_latitude' => 'nullable|numeric|between:-90,90',
            'theft_longitude' => 'nullable|numeric|between:-180,180',
            'observations' => 'nullable|string',
        ]);

        $validated['registered_by'] = auth()->id();
        $validated['status'] = 'activo';

        // 2. Crear el vehículo
        $vehicle = Vehicle::create($validated);

        // 3. Si se marcó el checkbox, crear el reporte de robo automáticamente
        if ($request->has('has_theft_report') && $request->has_theft_report == '1') {
            TheftReport::create([
                'vehicle_id' => $vehicle->id,
                'report_date' => $validated['theft_report_date'],
                'description' => $validated['theft_description'],
                'status' => $validated['theft_report_status'] ?? 'activo',
                'reported_by' => auth()->id(),
            ]);

            // Actualizar el estado del vehículo a 'reportado'
            $vehicle->update(['status' => 'reportado']);
        }

        // 4. Registrar en la bitácora
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'table_name' => 'vehicles',
            'record_id' => $vehicle->id,
            'new_values' => $validated,
            'ip_address' => $request->ip(),
        ]);

        $message = 'Vehículo registrado exitosamente.';
        if ($request->has('has_theft_report') && $request->has_theft_report == '1') {
            $message .= ' Reporte de robo activado.';
        }

        return redirect()->route('vehiculos.index')->with('success', $message);
    }

    /**
     * Muestra los detalles de un vehículo específico (GET /vehiculos/{id})
     */
    public function show(Vehicle $vehiculo)
    {
        $vehiculo->load('registeredBy', 'theftReport');
        return view('vehicles.show', compact('vehiculo'));
    }

    /**
     * Muestra el formulario para editar un vehículo (GET /vehiculos/{id}/edit)
     */
    public function edit(Vehicle $vehiculo)
    {
        $vehiculo->load('theftReport'); // Cargar el reporte si existe para prellenar el formulario
        return view('vehicles.edit', compact('vehiculo'));
    }

    /**
     * Actualiza un vehículo en la base de datos (PUT/PATCH /vehiculos/{id})
     */
    public function update(Request $request, Vehicle $vehiculo)
    {
        $validated = $request->validate([
            'vin' => 'required|string|max:17|unique:vehicles,vin,' . $vehiculo->id,
            'plate' => 'required|string|max:20|unique:vehicles,plate,' . $vehiculo->id,
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'color' => 'nullable|string|max:50',
            // CAMPOS GEOGRÁFICOS AGREGADOS:
            'department' => 'required|string|max:100',
            'municipality' => 'required|string|max:100',
            'zone' => 'nullable|string|max:50',
            // FIN CAMPOS GEOGRÁFICOS
            'has_theft_report' => 'nullable|in:1',
            'theft_report_date' => 'nullable|date|required_if:has_theft_report,1',
            'theft_description' => 'nullable|string|required_if:has_theft_report,1',
            'theft_report_status' => 'nullable|in:activo,resuelto',
            'theft_report_address' => 'nullable|string|max:255',
            'theft_latitude' => 'nullable|numeric|between:-90,90',
            'theft_longitude' => 'nullable|numeric|between:-180,180',
            'observations' => 'nullable|string',
            'status' => 'required|in:activo,reportado,vendido,inactivo',
        ]);

        $oldValues = $vehiculo->toArray();
        $vehiculo->update($validated);

        // Manejar el reporte de robo (Crear, Actualizar o Eliminar)
        if ($request->has('has_theft_report') && $request->has_theft_report == '1') {
            $existingReport = TheftReport::where('vehicle_id', $vehiculo->id)->first();

            if ($existingReport) {
                $existingReport->update([
                    'report_date' => $validated['theft_report_date'],
                    'description' => $validated['theft_description'],
                    'status' => $validated['theft_report_status'] ?? 'activo',
                ]);
            } else {
                TheftReport::create([
                    'vehicle_id' => $vehiculo->id,
                    'report_date' => $validated['theft_report_date'],
                    'description' => $validated['theft_description'],
                    'status' => $validated['theft_report_status'] ?? 'activo',
                    'reported_by' => auth()->id(),
                ]);
            }
            $vehiculo->update(['status' => 'reportado']);
        } else {
            // Si desmarcan el checkbox, eliminamos el reporte de robo asociado
            TheftReport::where('vehicle_id', $vehiculo->id)->delete();
        }

        // Registrar en la bitácora
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'table_name' => 'vehicles',
            'record_id' => $vehiculo->id,
            'old_values' => $oldValues,
            'new_values' => $validated,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo actualizado exitosamente.');
    }

    /**
     * Elimina un vehículo de la base de datos (DELETE /vehiculos/{id})
     */
    public function destroy(Vehicle $vehiculo)
    {
        $vehiculoId = $vehiculo->id;
        
        // Eliminar también el reporte de robo asociado para mantener la integridad
        TheftReport::where('vehicle_id', $vehiculoId)->delete();
        $vehiculo->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'table_name' => 'vehicles',
            'record_id' => $vehiculoId,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo eliminado exitosamente.');
    }
}