<?php

namespace App\Console\Commands;

use App\Services\ReportPhotos;
use Illuminate\Console\Command;

class CleanupReportPhotos extends Command
{
    protected $signature = 'reports:cleanup-photos';

    protected $description = 'Reintenta la limpieza privada de fotografías pendientes sin mostrar rutas.';

    public function handle(ReportPhotos $photos): int
    {
        $failed = $photos->cleanup();
        $this->info($failed ? 'Hay limpiezas pendientes. Revisa la disponibilidad del almacenamiento.' : 'Limpieza de fotografías completada.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
