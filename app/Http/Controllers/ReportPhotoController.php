<?php

namespace App\Http\Controllers;

use App\Models\ReportPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportPhotoController extends Controller
{
    public function show(Request $request, ReportPhoto $photo)
    {
        $report = $photo->theftReport;
        abort_unless($report && $report->vehicle, 404);
        $user = $request->user();
        abort_unless(in_array($user->role?->name, ['admin', 'consulta'], true)
            || ($user->role?->name === 'registro' && $report->reported_by === $user->id && $report->vehicle->registered_by === $user->id), 403);
        try {
            $stream = Storage::disk($photo->disk)->readStream($photo->path);
        } catch (Throwable) {
            abort(503, 'La fotografía no está disponible temporalmente.');
        }
        abort_unless(is_resource($stream), 404, 'Fotografía no disponible.');

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, ['Content-Type' => $photo->mime_type, 'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }
}
