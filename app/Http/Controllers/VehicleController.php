<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Services\ReportLifecycle;
use App\Services\ReportPhotos;
use App\Support\VehicleSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class VehicleController extends Controller
{
    private function authorizeOwner(Vehicle $vehicle): void
    {
        abort_unless(auth()->user()->role?->name === 'admin' || $vehicle->registered_by === auth()->id(), 403);
    }

    public function index()
    {
        $vehicles = Vehicle::with('registeredBy', 'theftReport')->when(auth()->user()->role?->name !== 'admin', fn ($q) => $q->where('registered_by', auth()->id()))->latest()->paginate(15);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('vehicles.create');
    }

    public function show(Vehicle $vehiculo)
    {
        $this->authorizeOwner($vehiculo);
        $vehiculo->load('registeredBy', 'theftReport');

        return view('vehicles.show', compact('vehiculo'));
    }

    public function edit(Vehicle $vehiculo)
    {
        $this->authorizeOwner($vehiculo);
        $vehiculo->load('theftReport');

        return view('vehicles.edit', compact('vehiculo'));
    }

    private function persist(Request $request, ?Vehicle $vehicle = null)
    {
        if ($vehicle) {
            $this->authorizeOwner($vehicle);
        }
        VehicleSearch::normalize($request);
        $data = $request->validate([
            'photos' => 'nullable|array|max:5|prohibited_unless:has_theft_report,1',
            'photos.*' => 'required|file|max:5120|mimetypes:image/jpeg,image/png,image/webp|extensions:jpg,jpeg,png,webp',
            'vin' => ['required', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/', Rule::unique('vehicles')->ignore($vehicle)],
            'plate' => ['required', 'regex:/^[A-Z]{1,2}[0-9]{3}[A-Z]{3}$/', Rule::unique('vehicles')->ignore($vehicle)],
            'brand' => 'required|string|max:100', 'model' => 'required|string|max:100',
            'year' => 'nullable|integer|min:1900|max:'.date('Y'), 'color' => 'nullable|string|max:50',
            'department' => 'required|string|max:100', 'municipality' => 'required|string|max:100',
            'zone' => 'nullable|string|max:50', 'observations' => 'nullable|string|max:5000',
            'status' => 'nullable|in:activo,reportado,vendido,inactivo',
            'has_theft_report' => 'nullable|in:1',
            'theft_report_date' => 'nullable|date|before_or_equal:today|required_if:has_theft_report,1',
            'theft_description' => 'nullable|string|max:5000|required_if:has_theft_report,1',
            'evidence_reference' => 'nullable|string|max:255',
            'review_action' => ['nullable', Rule::in(['validar', 'invalidar', 'resolver', 'reabrir'])],
            'review_reason' => 'nullable|string|max:2000|required_with:review_action',
            'theft_report_address' => 'nullable|string|max:255',
            'theft_latitude' => 'nullable|required_with:theft_longitude|numeric|between:-90,90', 'theft_longitude' => 'nullable|required_with:theft_latitude|numeric|between:-180,180',
        ], [
            'photos.array' => 'Selecciona las fotografías como archivos del formulario.',
            'photos.*.file' => 'Cada fotografía debe ser un archivo válido.',
            'photos.*.required' => 'No se pudo recibir una de las fotografías seleccionadas.',
            'photos.max' => 'Cada reporte admite un máximo de 5 fotografías.',
            'photos.prohibited_unless' => 'Las fotografías requieren un reporte activo en el formulario.',
            'photos.*.max' => 'Cada fotografía debe pesar como máximo 5 MB.',
            'photos.*.mimetypes' => 'Solo se permiten imágenes JPEG, PNG y WebP válidas.',
            'photos.*.extensions' => 'Usa archivos con extensión JPG, JPEG, PNG o WebP.',
            'photos.*.uploaded' => 'La fotografía supera el límite de carga del servidor o no se pudo recibir.',
        ]);
        $photos = app(ReportPhotos::class);
        $staged = $photos->prepare($request->file('photos', []));
        try {
            DB::transaction(function () use ($request, $vehicle, $data, $photos, $staged) {
                $creating = ! $vehicle;
                if ($vehicle) {
                    $vehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
                }
                $old = $vehicle?->load('theftReport')->toArray();
                $vehicle ??= new Vehicle(['registered_by' => auth()->id()]);
                $vehicle->fill(collect($data)->except(['has_theft_report', 'theft_report_date', 'theft_description', 'theft_report_status', 'evidence_reference', 'review_action', 'review_reason', 'photos'])->all());
                $vehicle->status = ($data['status'] ?? $vehicle->status) ?: 'activo';
                if ($vehicle->status === 'reportado') {
                    $vehicle->status = 'activo';
                }
                $vehicle->save();
                $report = $vehicle->theftReport;
                if ($request->boolean('has_theft_report')) {
                    $report = app(ReportLifecycle::class)->save($vehicle, $data, auth()->user());
                    if ($staged) {
                        abort_unless(auth()->user()->role?->name === 'admin' || ($report->reported_by === auth()->id() && $report->case_status === 'abierto'), 403);
                        $photos->attach($report, $staged, auth()->id());
                        $this->audit('attach_report_photos', 'theft_reports', $report->id, null, ['count' => count($staged)]);
                    }
                    $vehicle->status = $report->case_status === 'abierto' && $report->validation_status !== 'rechazado' ? 'reportado' : 'activo';
                } elseif ($report && $report->case_status !== 'retirado') {
                    abort_unless(auth()->user()->role?->name === 'admin' || $report->reported_by === auth()->id(), 403);
                    if (auth()->user()->role?->name !== 'admin') {
                        abort_if($report->case_status !== 'abierto' || $report->validation_status === 'rechazado', 403, 'Este caso requiere intervención administrativa.');
                    } elseif (empty($data['review_reason'])) {
                        throw ValidationException::withMessages(['review_reason' => 'Indica el motivo del retiro administrativo.']);
                    }
                    $before = $report->toArray();
                    $report->case_status = 'retirado';
                    $report->status = 'retirado';
                    $report->updated_by = auth()->id();
                    if (auth()->user()->role?->name === 'admin') {
                        $report->review_reason = $data['review_reason'];
                    }
                    $report->save();
                    $this->audit('withdraw_report', 'theft_reports', $report->id, $before, $report->toArray());
                }
                $vehicle->save();
                $this->audit($creating ? 'create' : 'update', 'vehicles', $vehicle->id, $old, $vehicle->fresh()->load('theftReport')->toArray());
            });
        } catch (\Throwable $exception) {
            $photos->discard($staged);
            if ($staged && ! $exception instanceof ValidationException && ! $exception instanceof HttpExceptionInterface) {
                throw ValidationException::withMessages(['photos' => 'No se pudo guardar el reporte con sus fotografías. Intenta nuevamente.']);
            }
            throw $exception;
        }

        return redirect()->route('vehiculos.index')->with('success', 'Registro guardado. La validación automática comprueba integridad y autorización; no confirma por sí sola que ocurrió un robo.');
    }

    public function store(Request $request)
    {
        return $this->persist($request);
    }

    public function update(Request $request, Vehicle $vehiculo)
    {
        return $this->persist($request, $vehiculo);
    }

    private function audit($action, $table, $id, $old, $new): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'table_name' => $table, 'record_id' => $id, 'old_values' => $old, 'new_values' => $new, 'ip_address' => request()->ip()]);
    }

    public function destroy(Vehicle $vehiculo)
    {
        $this->authorizeOwner($vehiculo);
        DB::transaction(function () use ($vehiculo) {
            $vehiculo = Vehicle::whereKey($vehiculo->id)->lockForUpdate()->firstOrFail();
            $this->authorizeOwner($vehiculo);
            $report = $vehiculo->theftReport()->lockForUpdate()->first();
            if (auth()->user()->role?->name !== 'admin' && $report) {
                abort_unless($report->reported_by === auth()->id() && $report->case_status === 'abierto', 403,
                    'Solo puedes eliminar tus propios reportes abiertos.');
            }
            $this->audit('delete', 'vehicles', $vehiculo->id, $vehiculo->load('theftReport')->toArray(), null);
            if ($report) {
                app(ReportLifecycle::class)->deletePhotos($report);
            }
            $vehiculo->delete();
        });

        app(ReportPhotos::class)->cleanup();

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo eliminado.');
    }
}
