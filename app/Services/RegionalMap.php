<?php

namespace App\Services;

class RegionalMap
{
    public const LIMIT = 300;

    public const DETAIL_ZOOM = 14;

    public function initialBounds(ReportAnalytics $analytics, array $filters): ?array
    {
        $bounds = $analytics->activeVehicles($filters)->whereBetween('theft_latitude', [-90, 90])->whereBetween('theft_longitude', [-180, 180])
            ->selectRaw('MIN(theft_latitude) AS south, MAX(theft_latitude) AS north, MIN(theft_longitude) AS west, MAX(theft_longitude) AS east')->first();
        if ($bounds->south === null) {
            return null;
        }

        return array_map(fn ($value) => (float) $value, $bounds->only(['south', 'north', 'west', 'east']));
    }

    public function points(ReportAnalytics $analytics, array $filters, array $area): array
    {
        $query = $analytics->activeVehicles($filters)
            ->whereBetween('theft_latitude', [$area['south'], $area['north']])
            ->whereNotNull('theft_longitude')
            ->where(function ($q) use ($area) {
                if ($area['west'] > $area['east']) {
                    $q->where('theft_longitude', '>=', $area['west'])->orWhere('theft_longitude', '<=', $area['east']);
                } else {
                    $q->whereBetween('theft_longitude', [$area['west'], $area['east']]);
                }
            });
        $total = (clone $query)->count();
        $clustered = $area['zoom'] < self::DETAIL_ZOOM;
        if ($clustered) {
            // Fixed world grid: stable cells across viewport changes; averages calculated in SQL.
            $cell = 360 / (2 ** $area['zoom']) / 4;
            // Shifted coordinates are non-negative, so integer truncation is floor on SQLite.
            $y = $query->getConnection()->getDriverName() === 'sqlite' ? 'CAST((theft_latitude + 90) / ? AS INTEGER)' : 'FLOOR((theft_latitude + 90) / ?)';
            $x = $query->getConnection()->getDriverName() === 'sqlite' ? 'CAST((theft_longitude + 180) / ? AS INTEGER)' : 'FLOOR((theft_longitude + 180) / ?)';
            $features = (clone $query)->selectRaw($y.' AS grid_y, '.$x.' AS grid_x', [$cell, $cell])
                ->selectRaw('COUNT(*) AS total, AVG(theft_latitude) AS lat, AVG(theft_longitude) AS lng, MIN(theft_latitude) AS south, MAX(theft_latitude) AS north, MIN(theft_longitude) AS west, MAX(theft_longitude) AS east')
                ->groupBy('grid_y', 'grid_x')->orderByDesc('total')->orderBy('grid_y')->orderBy('grid_x')->limit(self::LIMIT)->get()
                ->map(fn ($row) => ['type' => 'cluster', 'count' => (int) $row->total, 'lat' => (float) $row->lat, 'lng' => (float) $row->lng,
                    'bounds' => ['south' => (float) $row->south, 'north' => (float) $row->north, 'west' => (float) $row->west, 'east' => (float) $row->east]])->values();
        } else {
            $features = (clone $query)->select(['id', 'vin', 'plate', 'brand', 'model', 'theft_latitude', 'theft_longitude', 'theft_report_address'])
                ->with('theftReport:id,vehicle_id,validation_method')->orderBy('id')->limit(self::LIMIT)->get()
                ->map(fn ($v) => ['type' => 'point', 'count' => 1, 'lat' => (float) $v->theft_latitude, 'lng' => (float) $v->theft_longitude,
                    'vin' => $v->vin, 'plate' => $v->plate, 'brand' => $v->brand.' '.$v->model,
                    'address' => $v->theft_report_address, 'desc' => $v->theftReport->sourceLabel()])->values();
        }
        $represented = $features->sum('count');

        return ['features' => $features, 'meta' => ['total_matching' => $total, 'represented_count' => $represented,
            'feature_count' => $features->count(), 'limit' => self::LIMIT, 'truncated' => $represented < $total,
            'mode' => $clustered ? 'clusters' : 'points', 'detail_zoom' => self::DETAIL_ZOOM]];
    }
}
