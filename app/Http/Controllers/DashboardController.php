<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\TheftReport;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->role?->name === 'registro') {
            return view('dashboard.registro', ['count' => $user->registeredVehicles()->count()]);
        }

        // Estadísticas generales
        $stats = [
            'total_vehicles' => Vehicle::count(),
            'active_reports' => TheftReport::where('status', 'validado')->count(),
            'resolved_reports' => TheftReport::where('status', 'resuelto')->count(),
            'total_consultations' => Consultation::count(),
            'total_users' => User::count(),
            'consultations_today' => Consultation::whereDate('consultation_date', today())->count(),
        ];

        // Top 5 Departamentos con más robos
        $topDepartments = DB::table('vehicles')
            ->join('theft_reports', 'vehicles.id', '=', 'theft_reports.vehicle_id')
            ->where('theft_reports.status', 'validado')
            ->select('vehicles.department', DB::raw('count(*) as total'))
            ->groupBy('vehicles.department')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Top 5 Municipios con más robos
        $topMunicipalities = DB::table('vehicles')
            ->join('theft_reports', 'vehicles.id', '=', 'theft_reports.vehicle_id')
            ->where('theft_reports.status', 'validado')
            ->select('vehicles.municipality', 'vehicles.department', DB::raw('count(*) as total'))
            ->groupBy('vehicles.municipality', 'vehicles.department')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Distribución por zonas (solo Guatemala departamento)
        $zoneDistribution = DB::table('vehicles')
            ->join('theft_reports', 'vehicles.id', '=', 'theft_reports.vehicle_id')
            ->where('theft_reports.status', 'validado')
            ->where('vehicles.department', 'Guatemala')
            ->select('vehicles.zone', DB::raw('count(*) as total'))
            ->groupBy('vehicles.zone')
            ->orderByDesc('total')
            ->get();

        // Tendencia mensual de robos (últimos 6 meses)
        $monthlyTrend = TheftReport::where('report_date', '>=', now()->subMonths(6))->get()
            ->groupBy(fn ($r) => $r->report_date->format('Y-m'))
            ->map(fn ($rows, $month) => (object) ['month' => $month, 'total' => $rows->count()])->values();
        // Marcas más robadas
        $topBrands = DB::table('vehicles')
            ->join('theft_reports', 'vehicles.id', '=', 'theft_reports.vehicle_id')
            ->where('theft_reports.status', 'validado')
            ->select('vehicles.brand', DB::raw('count(*) as total'))
            ->groupBy('vehicles.brand')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Consultas recientes del usuario
        $recentConsultations = Consultation::where('user_id', $user->id)
            ->with('vehicle')
            ->orderBy('consultation_date', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'user', 'stats',
            'topDepartments', 'topMunicipalities', 'zoneDistribution',
            'monthlyTrend', 'topBrands', 'recentConsultations'
        ));
    }
}
