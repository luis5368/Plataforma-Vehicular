<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
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
        $validated = $request->validate([
            'vin' => 'required|string|max:17|unique:vehicles,vin',
            'plate' => 'required|string|max:20|unique:vehicles,plate',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'color' => 'nullable|string|max:50',
            'theft_report_address' => 'nullable|string|max:255',
            'theft_latitude' => 'nullable|numeric|between:-90,90',
            'theft_longitude' => 'nullable|numeric|between:-180,180',
            'observations' => 'nullable|string',
        ]);

        $validated['registered_by'] = auth()->id();
        $validated['status'] = 'activo';

        $vehicle = Vehicle::create($validated);

        // Bitácora de creación
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create',
            'table_name' => 'vehicles',
            'record_id' => $vehicle->id,
            'new_values' => $validated,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo registrado exitosamente.');
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
            'theft_report_address' => 'nullable|string|max:255',
            'theft_latitude' => 'nullable|numeric|between:-90,90',
            'theft_longitude' => 'nullable|numeric|between:-180,180',
            'observations' => 'nullable|string',
            'status' => 'required|in:activo,reportado,vendido,inactivo',
        ]);

        $oldValues = $vehiculo->toArray();

        $vehiculo->update($validated);

        // Bitácora de actualización
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
        $vehiculo->delete();

        // Bitácora de eliminación
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