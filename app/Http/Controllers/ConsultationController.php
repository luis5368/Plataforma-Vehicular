<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\TheftReport;
use App\Models\Consultation;
use App\Models\AuditLog;
use Illuminate\Http\Request;

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
        // Validación: Se requiere al menos el VIN o la Placa. Depto y Municipio son opcionales.
        $request->validate([
            'vin'          => 'nullable|string|max:17|required_without:plate',
            'plate'        => 'nullable|string|max:20|required_without:vin',
            'department'   => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
        ]);

        $vin = strtoupper(trim($request->input('vin')));
        $plate = strtoupper(trim($request->input('plate')));
        $department = trim($request->input('department'));
        $municipality = trim($request->input('municipality'));
        
        $vehicle = null;
        $searchMethod = '';

        // PRIORIDAD 1: Buscar por VIN (Identificador inmutable y más confiable)
        if (!empty($vin)) {
            $query = Vehicle::where('vin', $vin);
            
            // Aplicar filtros geográficos si el usuario los proporcionó
            if (!empty($department)) {
                $query->where('department', $department);
            }
            if (!empty($municipality)) {
                $query->where('municipality', $municipality);
            }
            
            $vehicle = $query->first();
            $searchMethod = 'VIN';
        } 
        // PRIORIDAD 2: Si no hay VIN, buscar por placa (Identificador mutable)
        elseif (!empty($plate)) {
            $query = Vehicle::where('plate', $plate);
            
            // Aplicar filtros geográficos si el usuario los proporcionó
            if (!empty($department)) {
                $query->where('department', $department);
            }
            if (!empty($municipality)) {
                $query->where('municipality', $municipality);
            }
            
            $vehicle = $query->first();
            $searchMethod = 'PLACA';
        }
        
        $result = 'no_encontrado';
        $theftReport = null;

        if ($vehicle) {
            // Verificar si tiene reporte de robo activo
            $theftReport = TheftReport::where('vehicle_id', $vehicle->id)
                ->where('status', 'activo')
                ->first();

            $result = $theftReport ? 'con_reporte_activo' : 'sin_reporte';
        }

        // 1. Registrar la consulta en el historial del usuario
        Consultation::create([
            'user_id' => auth()->id(),
            'vehicle_id' => $vehicle ? $vehicle->id : null,
            'consultation_date' => now(),
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
                'search_method'       => $searchMethod,
                'search_value'        => $searchMethod === 'VIN' ? $vin : $plate,
                'result'              => $result,
                'department_filter'   => $department ?: 'N/A',
                'municipality_filter' => $municipality ?: 'N/A',
            ],
            'ip_address' => $request->ip(),
        ]);

        // Pasar las variables a la vista para mostrar qué se buscó
        return view('consultations.result', compact(
            'vehicle', 
            'theftReport', 
            'result', 
            'vin', 
            'plate', 
            'searchMethod', 
            'department', 
            'municipality'
        ));
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
        // Iniciar la query base: solo vehículos que tengan reporte de robo activo
        $query = Vehicle::whereHas('theftReport', function($q) {
            $q->where('status', 'activo');
        })->with('theftReport');

        // Aplicar filtros solo si el usuario los seleccionó
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        if ($request->filled('municipality')) {
            $query->where('municipality', $request->municipality);
        }
        if ($request->filled('zone')) {
            $query->where('zone', 'like', '%' . $request->zone . '%');
        }

        // Obtener resultados
        $vehicles = $query->orderBy('created_at', 'desc')->get();

        // Preparar datos para el mapa (JSON)
        $mapData = $vehicles->map(function($v) {
            return [
                'lat' => $v->theft_latitude,
                'lng' => $v->theft_longitude,
                'plate' => $v->plate,
                'brand' => $v->brand . ' ' . $v->model,
                'address' => $v->theft_report_address,
                'desc' => $v->theftReport->description ?? 'Sin descripción'
            ];
        });

        return view('consultations.region', compact('vehicles', 'mapData'));
    }
}