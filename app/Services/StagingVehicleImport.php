<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\StagingImportException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class StagingVehicleImport
{
    public ?string $fileHash = null;

    public int $rowsRead = 0;

    public const HEADERS = ['source_row_number', 'source_id', 'synthetic_data', 'vin', 'source_plate', 'plate', 'plate_generation', 'brand', 'model', 'catalog_model_candidate', 'model_review_required', 'year', 'source_year', 'year_adjusted_for_validation', 'color', 'catalog_color_candidate', 'color_review_required', 'catalog_candidate_unverified', 'department', 'municipality', 'zone', 'theft_report_address', 'location_precision', 'theft_latitude', 'theft_longitude', 'observations', 'vehicle_status', 'registered_by', 'has_theft_report', 'theft_report_date', 'theft_description', 'evidence_reference', 'report_status', 'validation_status', 'case_status', 'reported_by', 'updated_by', 'validation_method', 'validated_at', 'validated_by', 'source_report_status', 'source_vehicle_status', 'import_note'];

    public function guard(): void
    {
        if (! app()->environment('staging') || config('staging_import.enabled') !== true) {
            throw new StagingImportException('Importación bloqueada: requiere APP_ENV=staging y STAGING_IMPORT_ENABLED=true.');
        }
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");
        $database = config("database.connections.$connection.database");
        $url = config("database.connections.$connection.url");
        if ($url || ! is_string($database) || ! $database || config('staging_import.database') !== $database
            || ($driver !== 'mysql' && ! ($driver === 'sqlite' && $database === ':memory:' && config('staging_import.testing_sqlite') === true))) {
            throw new StagingImportException('Destino bloqueado: confirma la base exclusiva de staging mediante STAGING_IMPORT_DATABASE; no se permite DB_URL.');
        }
    }

    public function actor(int $id, bool $lock = false): User
    {
        $user = User::whereKey($id)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        $role = $user?->role()->when($lock, fn ($q) => $q->lockForUpdate())->first();
        if (! $user || ! $user->is_active || ! $user->hasVerifiedEmail() || $role?->name !== 'admin') {
            throw new StagingImportException('El actor debe ser un administrador existente, activo y con correo verificado.');
        }

        return $user;
    }

    public function schema(): void
    {
        foreach (['vehicles' => ['synthetic_data', 'staging_import_batch_id', 'vin', 'plate', 'theft_latitude', 'theft_longitude'],
            'theft_reports' => ['vehicle_id', 'status', 'case_status', 'validation_status', 'validation_method', 'validated_at', 'validated_by', 'reported_by', 'updated_by'],
            'staging_import_batches' => ['file_hash', 'actor_id', 'row_count', 'result']] as $table => $columns) {
            if (! Schema::hasColumns($table, $columns)) {
                throw new StagingImportException('El esquema de staging no está preparado; revisa las migraciones antes de importar.');
            }
        }
        if (DB::connection()->getDriverName() === 'mysql') {
            $expected = ['vehicles' => ['vin' => 'varchar(17)', 'plate' => 'varchar(20)', 'brand' => 'varchar(100)', 'model' => 'varchar(100)', 'year' => 'year', 'color' => 'varchar(50)', 'department' => 'varchar(100)', 'municipality' => 'varchar(100)', 'zone' => 'varchar(50)', 'theft_report_address' => 'varchar(255)', 'theft_latitude' => 'decimal(10,8)', 'theft_longitude' => 'decimal(11,8)'],
                'theft_reports' => ['report_date' => 'date', 'status' => 'varchar(40)', 'validation_status' => 'varchar(30)', 'case_status' => 'varchar(20)', 'validation_method' => 'varchar(30)', 'evidence_reference' => 'varchar(255)']];
            foreach ($expected as $table => $columns) {
                $actual = array_column(Schema::getColumns($table), 'type', 'name');
                foreach ($columns as $column => $type) {
                    if (strtolower($actual[$column] ?? '') !== $type) {
                        throw new StagingImportException("Esquema MySQL incompatible en $table.$column; no se importó.");
                    }
                }
            }
            $engines = DB::select("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('vehicles','theft_reports','staging_import_batches','audit_logs','users','roles')");
            if (count($engines) !== 6 || collect($engines)->contains(fn ($row) => strtoupper($row->ENGINE) !== 'INNODB')) {
                throw new StagingImportException('La importación requiere tablas InnoDB para rollback completo.');
            }
        }
    }

    public function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new StagingImportException('No se puede leer el CSV privado.');
        }
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new StagingImportException('No se puede abrir el CSV.');
        }
        try {
            $bytes = stream_get_contents($handle, config('staging_import.max_bytes') + 1);
        } finally {
            fclose($handle);
        }
        if (is_string($bytes) && strlen($bytes) <= config('staging_import.max_bytes')) {
            $this->fileHash = hash('sha256', $bytes);
        }
        if (! is_string($bytes) || strlen($bytes) > config('staging_import.max_bytes') || ! mb_check_encoding($bytes, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $bytes)) {
            throw new StagingImportException('CSV inválido: tamaño, UTF-8 o caracteres de control.');
        }
        $hash = hash('sha256', $bytes);
        $bytes = preg_replace('/^\xEF\xBB\xBF/', '', $bytes);
        $this->csvSyntax($bytes);
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $bytes);
        rewind($stream);
        try {
            $headers = fgetcsv($stream, null, ',', '"', '');
            if (! is_array($headers) || count($headers) !== count(self::HEADERS) || count(array_unique($headers)) !== count($headers)
                || array_diff(self::HEADERS, $headers) || array_diff($headers, self::HEADERS)) {
                throw new StagingImportException('Encabezados inválidos: exporta únicamente CARGA_STAGING_CORREGIDA con sus 43 columnas.');
            }
            $rows = [];
            $seen = [];
            while (($values = fgetcsv($stream, null, ',', '"', '')) !== false) {
                $line = count($rows) + 2;
                if (count($rows) >= config('staging_import.max_rows') || count($values) !== count($headers)) {
                    throw new StagingImportException("Fila lógica $line: número de columnas o límite de filas inválido.");
                }
                $row = array_combine($headers, $values);
                $this->rowsRead++;
                $this->validateRow($row, $line);
                foreach (['vin', 'plate', 'evidence_reference'] as $field) {
                    if (isset($seen[$field][$row[$field]])) {
                        throw new StagingImportException("Fila lógica $line: clave duplicada en $field.");
                    }
                    $seen[$field][$row[$field]] = true;
                }
                $rows[] = $row;
            }
            if (! $rows) {
                throw new StagingImportException('El CSV no contiene filas.');
            }
        } finally {
            fclose($stream);
        }

        return ['hash' => $hash, 'rows' => $rows];
    }

    private function csvSyntax(string $bytes): void
    {
        $state = 'start';
        for ($i = 0, $length = strlen($bytes); $i < $length; $i++) {
            $c = $bytes[$i];
            if ($state === 'quoted') {
                if ($c === '"') {
                    if (($bytes[$i + 1] ?? '') === '"') {
                        $i++;
                    } else {
                        $state = 'closed';
                    }
                }

                continue;
            }
            if ($c === ',' || $c === "\n" || ($c === "\r" && ($bytes[$i + 1] ?? '') === "\n")) {
                $state = 'start';
                if ($c === "\r") {
                    $i++;
                }
            } elseif ($c === '"' && $state === 'start') {
                $state = 'quoted';
            } elseif ($state === 'closed' || $c === '"' || $c === "\r") {
                throw new StagingImportException('CSV malformado: comillas o separadores inválidos.');
            } else {
                $state = 'unquoted';
            }
        }
        if ($state === 'quoted') {
            throw new StagingImportException('CSV malformado: comillas sin cerrar.');
        }
    }

    private function validateRow(array $row, int $line): void
    {
        $rules = array_fill_keys(self::HEADERS, ['nullable', 'string', 'max:5000']);
        $rules = array_replace($rules, [
            'vin' => ['required', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
            'plate' => ['required', 'regex:/^[A-Z]{1,2}[0-9]{3}[A-Z]{3}$/'],
            'source_row_number' => ['required', 'integer', 'min:1'],
            'synthetic_data' => ['required', 'in:1'], 'has_theft_report' => ['required', 'in:1'],
            'vehicle_status' => ['required', 'in:reportado'], 'report_status' => ['required', 'in:activo'],
            'validation_status' => ['required', 'in:pendiente'], 'case_status' => ['required', 'in:abierto'],
            'validation_method' => ['prohibited'], 'validated_at' => ['prohibited'], 'validated_by' => ['prohibited'],
            'brand' => ['required', 'string', 'max:100'], 'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1901', 'max:'.date('Y')],
            'color' => ['nullable', 'string', 'max:50'], 'department' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'], 'zone' => ['nullable', 'string', 'max:50'],
            'theft_report_address' => ['required', 'string', 'max:255'],
            'theft_report_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'theft_description' => ['required', 'string', 'max:5000'],
            'evidence_reference' => ['required', 'regex:/^STAGING-SINTETICO-[0-9]{6}$/'],
            'theft_latitude' => ['nullable', 'required_with:theft_longitude', 'regex:/^-?\d{1,2}(\.\d{1,8})?$/', 'numeric', 'between:-90,90'],
            'theft_longitude' => ['nullable', 'required_with:theft_latitude', 'regex:/^-?\d{1,3}(\.\d{1,8})?$/', 'numeric', 'between:-180,180'],
        ]);
        foreach (['registered_by', 'reported_by', 'updated_by'] as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:1']; // Validate syntax, never use these IDs.
        }
        foreach (['model_review_required', 'year_adjusted_for_validation', 'color_review_required', 'catalog_candidate_unverified'] as $field) {
            $rules[$field] = ['nullable', 'in:0,1']; // Source annotations are validated but never imported.
        }
        $validation = Validator::make($row, $rules);
        if ($validation->fails()) {
            throw new StagingImportException("Fila lógica $line: campos inválidos (".implode(', ', array_keys($validation->errors()->messages())).').');
        }
    }

    public function checkDatabase(array $input): void
    {
        if (DB::table('staging_import_batches')->where('file_hash', $input['hash'])->exists()) {
            throw new StagingImportException('Este archivo ya fue importado.');
        }
        foreach (array_chunk($input['rows'], 500) as $chunk) {
            if (DB::table('vehicles')->whereIn('vin', array_column($chunk, 'vin'))->orWhereIn('plate', array_column($chunk, 'plate'))->exists()) {
                throw new StagingImportException('Existen VIN o placas del archivo en la base de staging.');
            }
        }
    }

    public function commit(array $input, int $actorId): int
    {
        $this->guard();
        $this->schema();

        return DB::transaction(function () use ($input, $actorId) {
            $actor = $this->actor($actorId, true);
            $this->checkDatabase($input);
            $now = now();
            $batch = DB::table('staging_import_batches')->insertGetId(['file_hash' => $input['hash'], 'actor_id' => $actor->id, 'row_count' => count($input['rows']), 'result' => 'committed', 'created_at' => $now, 'updated_at' => $now]);
            foreach (array_chunk($input['rows'], 500) as $chunk) {
                $vehicles = [];
                foreach ($chunk as $row) {
                    $vehicle = array_intersect_key($row, array_flip(['vin', 'plate', 'brand', 'model', 'year', 'color', 'department', 'municipality', 'zone', 'theft_report_address', 'theft_latitude', 'theft_longitude', 'observations']));
                    $vehicle = array_map(fn ($value) => $value === '' ? null : $value, $vehicle);
                    $vehicles[] = $vehicle + ['registered_by' => $actor->id, 'synthetic_data' => true, 'status' => 'reportado', 'staging_import_batch_id' => $batch, 'created_at' => $now, 'updated_at' => $now];
                }
                DB::table('vehicles')->insert($vehicles);
                $ids = DB::table('vehicles')->where('staging_import_batch_id', $batch)->whereIn('vin', array_column($chunk, 'vin'))->pluck('id', 'vin');
                $reports = [];
                foreach ($chunk as $row) {
                    $reports[] = ['vehicle_id' => $ids[$row['vin']], 'report_date' => $row['theft_report_date'], 'description' => $row['theft_description'], 'evidence_reference' => $row['evidence_reference'], 'reported_by' => $actor->id, 'updated_by' => $actor->id, 'status' => 'activo', 'case_status' => 'abierto', 'validation_status' => 'pendiente', 'validation_method' => null, 'validated_at' => null, 'validated_by' => null, 'created_at' => $now, 'updated_at' => $now];
                }
                DB::table('theft_reports')->insert($reports);
            }
            AuditLog::create(['user_id' => $actor->id, 'action' => 'staging_import_committed', 'table_name' => 'staging_import_batches', 'record_id' => $batch, 'new_values' => ['file_hash' => $input['hash'], 'count' => count($input['rows']), 'result' => 'committed', 'at' => $now->toIso8601String()], 'ip_address' => '127.0.0.1']);

            return $batch;
        });
    }
}
