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

    // Procesar la búsqueda por placa
    public function buscar(Request $request)
    {
        $request->validate([
            'plate' => 'required|string|max:20',
        ]);

        $plate = strtoupper(trim($request->input('plate')));
        
        // Buscar vehículo por placa
        $vehicle = Vehicle::where('plate', $plate)->first();
        
        $result = 'no_encontrado';
        $theftReport = null;

        if ($vehicle) {
            // Verificar si tiene reporte de robo activo
            $theftReport = TheftReport::where('vehicle_id', $vehicle->id)
                ->where('status', 'activo')
                ->first();

            $result = $theftReport ? 'con_reporte_activo' : 'sin_reporte';
        }

        // Registrar la consulta en el historial
        Consultation::create([
            'user_id' => auth()->id(),
            'vehicle_id' => $vehicle ? $vehicle->id : null,
            'consultation_date' => now(),
            'result' => $result,
            'ip_address' => $request->ip(),
        ]);

        // Registrar en bitácora
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'consult',
            'table_name' => 'vehicles',
            'record_id' => $vehicle ? $vehicle->id : null,
            'new_values' => ['plate' => $plate, 'result' => $result],
            'ip_address' => $request->ip(),
        ]);

        return view('consultations.result', compact('vehicle', 'theftReport', 'result', 'plate'));
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
}