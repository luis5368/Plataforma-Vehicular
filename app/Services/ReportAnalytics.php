<?php

namespace App\Services;

use App\Models\TheftReport;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ReportAnalytics
{
    public function filters(Request $request): array
    {
        $values = $request->validate(['department' => 'nullable|string|max:100', 'municipality' => 'nullable|string|max:100', 'zone' => 'nullable|string|max:50']);

        return array_filter(array_map(fn ($v) => trim((string) $v), $values), fn ($v) => $v !== '');
    }

    public function vehicles(array $filters)
    {
        $query = Vehicle::query();
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        return $query;
    }

    public function reports(array $filters)
    {
        return TheftReport::whereIn('vehicle_id', $this->vehicles($filters)->select('id'));
    }

    public function summary(array $filters): array
    {
        $reports = $this->reports($filters);

        return [
            'total_vehicles' => $this->vehicles($filters)->distinct()->count('vin'),
            'active_reports' => (clone $reports)->active()->distinct()->count('vehicle_id'),
            'resolved_reports' => (clone $reports)->where('case_status', 'resuelto')->distinct()->count('vehicle_id'),
            'pending_reports' => (clone $reports)->where('validation_status', 'pendiente')->where('case_status', 'abierto')->distinct()->count('vehicle_id'),
        ];
    }

    public function distribution(array $filters, array $fields)
    {
        $query = $this->vehicles($filters)->whereHas('theftReport', fn ($q) => $q->active());
        foreach ($fields as $field) {
            $query->whereNotNull($field)->where($field, '!=', '');
        }

        return $query->select($fields)->selectRaw('COUNT(DISTINCT vin) as total')->groupBy($fields)->orderByDesc('total')->limit(5)->get();
    }

    public function dashboard(array $filters): array
    {
        return [
            'stats' => $this->summary($filters),
            'topDepartments' => $this->distribution($filters, ['department']),
            'topMunicipalities' => $this->distribution($filters, ['municipality', 'department']),
            'zoneDistribution' => $this->distribution($filters, ['zone']),
            'topBrands' => $this->distribution($filters, ['brand']),
            'monthlyTrend' => $this->reports($filters)->accepted()->whereIn('case_status', ['abierto', 'resuelto'])->where('report_date', '>=', now()->subMonths(6))->get()->groupBy(fn ($r) => $r->report_date->format('Y-m'))->sortKeys()->map(fn ($rows, $month) => (object) ['month' => $month, 'total' => $rows->unique('vehicle_id')->count()])->values(),
        ];
    }
}
