<?php

namespace App\Console\Commands;

use App\Services\StagingVehicleImport;
use App\Support\StagingImportException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportStagingVehicles extends Command
{
    protected $signature = 'vehicles:import-staging {csv} {--admin-id= : Administrador activo y verificado, sin valor predeterminado} {--commit : Confirmar escritura atómica; por defecto solo valida}';

    protected $description = 'Valida o importa exclusivamente escenarios sintéticos de staging desde CARGA_STAGING_CORREGIDA.';

    public function handle(StagingVehicleImport $import): int
    {
        $input = null;
        $actorId = null;
        try {
            $import->guard(); // Must run before any database access.
            $import->schema();
            $id = $this->option('admin-id');
            if (! is_string($id) || ! preg_match('/^[1-9][0-9]{0,17}$/D', $id)) {
                throw new StagingImportException('Indica --admin-id explícito y válido, también para dry-run.');
            }
            $actor = $import->actor((int) $id);
            $actorId = $actor->id;
            $input = $import->read($this->argument('csv'));
            $import->checkDatabase($input);
            $this->line('SHA-256: '.$input['hash'].'; filas: '.count($input['rows']));
            if (! $this->option('commit')) {
                $this->info('Dry-run válido. No se escribió ninguna tabla.');
            } else {
                $batch = $import->commit($input, $actorId);
                $this->info("Lote $batch confirmado: escenarios sintéticos pendientes, abiertos y activos.");
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if ($this->option('commit') && $actorId) {
                // Failure audit without inserting a row after a rejected/rolled-back import.
                Log::warning('staging_import_failed', ['actor_id' => $actorId, 'file_hash' => $import->fileHash, 'count' => $input ? count($input['rows']) : null, 'rows_read' => $import->rowsRead, 'result' => 'failed', 'at' => now()->toIso8601String()]);
            }
            $this->error($exception instanceof StagingImportException ? $exception->getMessage() : 'Importación rechazada o revertida. Verifica esquema, restricciones y destino aislado; no se muestran detalles internos.');

            return self::FAILURE;
        }
    }
}
