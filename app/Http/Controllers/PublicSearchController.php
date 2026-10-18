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
            'Reportes pendientes' => TheftReport::where('status', 'pendiente_validacion')->count(),
            'Reportes validados' => TheftReport::where('status', 'validado')->count(),
            'Reportes resueltos' => TheftReport::where('status', 'resuelto')->count(),
        ]]);
    }

    public function search(Request $request)
    {
        $request->validate(['website' => 'nullable|size:0']);
        $vehicle = VehicleSearch::find($request);
        // Explicit allowlist: never pass an Eloquent record to the public template.
        $result = ['found' => (bool) $vehicle, 'identifier' => $request->vin ?: $request->plate,
            'status' => $vehicle?->theftReport?->status ?? 'sin_reporte'];

        return response()->view('public-result', compact('result'))->header('Cache-Control', 'no-store');
    }
}
