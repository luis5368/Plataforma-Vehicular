<?php

namespace App\Services;

use App\Models\PhotoFileDeletion;
use App\Models\TheftReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportPhotos
{
    public function prepare(array $files): array
    {
        $staged = [];
        try {
            foreach ($files as $index => $file) {
                [$bytes, $mime] = $this->sanitize($file, $index);
                $disk = config('reports.photos_disk');
                if ($disk === 'public' || config('filesystems.disks.'.$disk.'.visibility') !== 'private') {
                    throw new \RuntimeException('Private storage required.');
                }
                $path = 'reports/photos/'.Str::uuid();
                // Committed before writing: a crash can always be reconciled by the cleanup command.
                $cleanup = PhotoFileDeletion::create(['disk' => $disk, 'path' => $path, 'available_at' => now()->addDay()]);
                $staged[] = ['cleanup' => $cleanup, 'disk' => $disk, 'path' => $path, 'mime_type' => $mime, 'size' => strlen($bytes)];
                if (! Storage::disk($disk)->put($path, $bytes, ['visibility' => 'private'])) {
                    throw new \RuntimeException('Storage failed.');
                }
            }

            return $staged;
        } catch (Throwable $exception) {
            $this->discard($staged);
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            throw ValidationException::withMessages(['photos' => 'No se pudieron guardar las fotografías. No se guardó el reporte; intenta nuevamente.']);
        }
    }

    private function sanitize(UploadedFile $file, int $index): array
    {
        $fail = fn (string $message) => throw ValidationException::withMessages(['photos.'.$index => $message]);
        if (! extension_loaded('gd')) {
            $fail('La carga de fotografías no está disponible: el servidor requiere la extensión GD.');
        }
        $input = $file->get();
        $dimensions = @getimagesizefromstring($input);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($input);
        if (! $dimensions || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
            || ($dimensions['mime'] ?? null) !== $mime) {
            $fail('Selecciona una imagen JPEG, PNG o WebP válida; no se admite SVG.');
        }
        if ($dimensions[0] > 4096 || $dimensions[1] > 4096 || $dimensions[0] * $dimensions[1] > 8000000) {
            $fail('La imagen supera 4096 píxeles por lado o 8 megapíxeles.');
        }
        $memoryLimit = ini_parse_quantity(ini_get('memory_limit'));
        $estimatedMemory = $dimensions[0] * $dimensions[1] * 16 + strlen($input) * 2 + 16 * 1024 * 1024;
        if ($memoryLimit > 0 && $memoryLimit - memory_get_usage(true) < $estimatedMemory) {
            $fail('La fotografía requiere demasiada memoria para este servidor. Reduce sus dimensiones e intenta nuevamente.');
        }
        $image = @imagecreatefromstring($input);
        if (! $image) {
            $fail('La fotografía está dañada o no se puede decodificar.');
        }
        // Re-encode pixels only: no original filename, EXIF/GPS or appended payload survives.
        ob_start();
        try {
            $ok = match ($mime) {
                'image/jpeg' => imagejpeg($image, null, 85),
                'image/png' => imagepng($image, null, 6),
                'image/webp' => function_exists('imagewebp') && imagewebp($image, null, 85),
            };
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        if (! $ok || ! $bytes || strlen($bytes) > 5 * 1024 * 1024) {
            $fail('No se pudo procesar la fotografía dentro del límite de 5 MB.');
        }

        return [$bytes, $mime];
    }

    // Called with vehicle/report locked, inside the report transaction.
    public function attach(TheftReport $report, array $staged, int $actor): void
    {
        if ($report->photos()->count() + count($staged) > 5) {
            throw ValidationException::withMessages(['photos' => 'Cada reporte admite un máximo de 5 fotografías.']);
        }
        foreach ($staged as $photo) {
            $report->photos()->create(collect($photo)->except('cleanup')->all() + ['uploaded_by' => $actor]);
            $photo['cleanup']->delete();
        }
    }

    public function discard(array $staged): void
    {
        foreach ($staged as $photo) {
            // The record may have been deleted inside a transaction that subsequently rolled back.
            if ($cleanup = PhotoFileDeletion::find($photo['cleanup']->id)) {
                $cleanup->update(['available_at' => now()]);
                $this->remove($cleanup);
            }
        }
    }

    public function scheduleDeletion(TheftReport $report): void
    {
        foreach ($report->photos()->get() as $photo) {
            PhotoFileDeletion::firstOrCreate(['path' => $photo->path], ['disk' => $photo->disk, 'available_at' => now()]);
        }
    }

    public function cleanup(): int
    {
        $failed = 0;
        PhotoFileDeletion::where('available_at', '<=', now())->orderBy('id')->chunkById(100, function ($rows) use (&$failed) {
            foreach ($rows as $row) {
                if (! $this->remove($row)) {
                    $failed++;
                }
            }
        });

        return $failed;
    }

    private function remove(PhotoFileDeletion $row): bool
    {
        try {
            // Idempotent: missing files are already clean; failed deletes remain durable for retry.
            if (Storage::disk($row->disk)->exists($row->path) && ! Storage::disk($row->disk)->delete($row->path)) {
                return false;
            }
            $row->delete();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
