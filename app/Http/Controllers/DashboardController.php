<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\TheftReport;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $stats = [
            'total_vehicles' => Vehicle::count(),
            'active_reports' => TheftReport::where('status', 'activo')->count(),
        ];

        return view('dashboard.index', compact('user', 'stats'));
    }
}