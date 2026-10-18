<?php

namespace App\Support;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleSearch
{
    public static function normalize(Request $request): void
    {
        $request->merge([
            'vin' => strtoupper(trim((string) $request->input('vin'))),
            'plate' => preg_replace('/[\s-]+/', '', strtoupper(trim((string) $request->input('plate')))),
        ]);
    }

    public static function find(Request $request): ?Vehicle
    {
        self::normalize($request);
        $request->validate([
            'vin' => ['nullable', 'required_without:plate', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'plate' => ['nullable', 'required_without:vin', 'regex:/^[A-Z]{1,2}[0-9]{3}[A-Z]{3}$/'],
            'department' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
        ]);

        return Vehicle::with('theftReport')->where($request->filled('vin') ? 'vin' : 'plate', $request->filled('vin') ? $request->vin : $request->plate)
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->department))
            ->when($request->filled('municipality'), fn ($q) => $q->where('municipality', $request->municipality))->first();
    }
}
