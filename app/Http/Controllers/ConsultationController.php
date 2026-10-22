<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegionalMapRequest;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\TheftReport;
use App\Services\RegionalMap;
use App\Services\ReportAnalytics;
use App\Support\VehicleSearch;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    // Mostrar formulario de búsqueda
    public function index()
    {
        return view('consultations.search');
    }

    // Procesar la búsqueda (Priorizando VIN sobre Placa, con filtros geográficos opcionales)
    public function buscar(Request $request)
    {
        $vehicle = VehicleSearch::find($request);
        $vin = $request->vin;
        $plate = $request->plate;
        $department = $request->department;
        $municipality = $request->municipality;
        $searchMethod = $vin ? 'VIN' : 'PLACA';
        $result = 'no_encontrado';
        $theftReport = null;

        if ($vehicle) {
            // Verificar si tiene reporte de robo activo
            $theftReport = TheftReport::where('vehicle_id', $vehicle->id)
                ->first();

            $result = $theftReport?->displayStatus() ?? 'sin_reporte';
        }

        // 1. Registrar la consulta en el historial del usuario
        DB::transaction(function () use ($request, $vehicle, $vin, $plate, $result, $searchMethod, $department, $municipality) {
            Consultation::create([
                'user_id' => auth()->id(),
                'vehicle_id' => $vehicle ? $vehicle->id : null,
                'consultation_date' => now(),
                'search_value' => $vin ?: $plate,
                'result' => $result,
                'ip_address' => $request->ip(),
            ]);

            // 2. Registrar en la bitácora de auditoría del sistema (Trazabilidad mejorada)
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'consult',
                'table_name' => 'vehicles',
                'record_id' => $vehicle ? $vehicle->id : null,
                'new_values' => [
                    'search_method' => $searchMethod,
                    'search_value' => $searchMethod === 'VIN' ? $vin : $plate,
                    'result' => $result,
                    'department_filter' => $department ?: 'N/A',
                    'municipality_filter' => $municipality ?: 'N/A',
                ],
                'ip_address' => $request->ip(),
            ]);

        });

        // Pasar las variables a la vista para mostrar qué se buscó
        return response()->view('consultations.result', compact(
            'vehicle',
            'theftReport',
            'result',
            'vin',
            'plate',
            'searchMethod',
            'department',
            'municipality'
        ))->header('Cache-Control', 'no-store');
    }

    // Historial de consultas del usuario
    public function historial()
    {
        $consultations = Consultation::where('user_id', auth()->id())
            ->with('vehicle')
            ->orderBy('consultation_date', 'desc')
            ->paginate(15);

        return view('consultations.history', compact('consultations'));
    }

    /**
     * Consulta y Análisis por Región (Filtros geográficos)
     */
    public function region(Request $request)
    {
        $analytics = app(ReportAnalytics::class);
        $filters = $analytics->filters($request);
        try {
            $vehicles = $analytics->activeVehicles($filters)->with('theftReport')->latest()->orderByDesc('id')->paginate(25)->withQueryString();
            $mapBounds = app(RegionalMap::class)->initialBounds($analytics, $filters);

            return view('consultations.region', $analytics->dashboard($filters) + compact('vehicles', 'filters', 'mapBounds'));
        } catch (QueryException $exception) {
            return response()->view('reports.unavailable', [], 503);
        }
    }

    public function map(RegionalMapRequest $request, ReportAnalytics $analytics, RegionalMap $map)
    {
        try {
            return response()->json($map->points($analytics, $analytics->filters($request), $request->validated()))->header('Cache-Control', 'no-store');
        } catch (QueryException $exception) {
            return response()->json(['message' => 'La consulta del mapa falló. Intenta nuevamente más tarde.'], 503)->header('Cache-Control', 'no-store');
        }
    }
}
