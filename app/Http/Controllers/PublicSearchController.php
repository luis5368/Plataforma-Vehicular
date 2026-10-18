<?php

namespace App\Http\Controllers;

use App\Models\TheftReport;
use App\Models\Vehicle;
use App\Support\VehicleSearch;
use Illuminate\Http\Request;

class PublicSearchController extends Controller
{
    public function index()
    {
        return view('welcome', ['stats' => [
            'Vehículos registrados' => Vehicle::count(),
            'Reportes pendientes' => TheftReport::where('validation_status', 'pendiente')->where('case_status', 'abierto')->distinct()->count('vehicle_id'),
            'Reportes validados' => TheftReport::active()->distinct()->count('vehicle_id'),
            'Reportes resueltos' => TheftReport::where('case_status', 'resuelto')->distinct()->count('vehicle_id'),
        ]]);
    }

    public function search(Request $request)
    {
        $request->validate(['website' => 'nullable|size:0']);
        $vehicle = VehicleSearch::find($request);
        // Explicit allowlist: never pass an Eloquent record to the public template.
        $result = ['found' => (bool) $vehicle, 'identifier' => $request->vin ?: $request->plate,
            'status' => $vehicle?->theftReport?->displayStatus() ?? 'sin_reporte', 'source' => $vehicle?->theftReport?->sourceLabel(), 'validation_status' => $vehicle?->theftReport?->validation_status];

        return response()->view('public-result', compact('result'))->header('Cache-Control', 'no-store');
    }
}
