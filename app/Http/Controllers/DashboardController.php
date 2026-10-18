<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\User;
use App\Services\ReportAnalytics;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ReportAnalytics $analytics)
    {
        $user = $request->user();
        if ($user->role?->name === 'registro') {
            return view('dashboard.registro', ['count' => $user->registeredVehicles()->count()]);
        }
        $filters = $analytics->filters($request);
        try {
            $data = $analytics->dashboard($filters);
            $data['stats'] += ['total_users' => User::count(), 'total_consultations' => Consultation::count(), 'consultations_today' => Consultation::whereDate('consultation_date', today())->count()];

            return view('dashboard.index', $data + compact('user', 'filters'));
        } catch (QueryException $exception) {
            return response()->view('reports.unavailable', [], 503);
        }
    }
}
