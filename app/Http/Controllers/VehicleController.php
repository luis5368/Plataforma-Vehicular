<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function create()
    {
        return view('vehicles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vin' => 'required|string|max:17|unique:vehicles,vin',
            'plate' => 'required|string|max:20|unique:vehicles,plate',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:'.date('Y'),
            'color' => 'nullable|string|max:50',
            'theft_report_address' => 'nullable|string|max:255',
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
}
