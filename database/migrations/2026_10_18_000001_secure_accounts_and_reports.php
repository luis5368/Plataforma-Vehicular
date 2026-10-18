<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $normalized = DB::table('vehicles')->get(['id', 'vin', 'plate'])->map(fn ($v) => [
            'id' => $v->id, 'vin' => strtoupper(trim($v->vin)),
            'plate' => preg_replace('/[\s-]+/', '', strtoupper(trim($v->plate))),
        ]);
        foreach (['vin', 'plate'] as $field) {
            if ($normalized->pluck($field)->duplicates()->isNotEmpty()) {
                throw new RuntimeException("Existen duplicados de {$field} al normalizar. Revíselos antes de migrar; no se fusionaron registros.");
            }
        }
        if (DB::table('theft_reports')->select('vehicle_id')->groupBy('vehicle_id')->havingRaw('count(*) > 1')->exists()) {
            throw new RuntimeException('Existen reportes repetidos por vehículo. Revise los registros antes de migrar.');
        }
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_active')->default(true));
        Schema::table('theft_reports', function (Blueprint $t) {
            $t->string('status', 40)->default('pendiente_validacion')->change();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unique('vehicle_id');
        });
        // Legacy reports were never reviewed: do not silently certify them.
        DB::table('theft_reports')->where('status', 'activo')->update(['status' => 'pendiente_validacion']);
        Schema::table('consultations', function (Blueprint $t) {
            $t->dropForeign(['vehicle_id']);
        });
        Schema::table('consultations', function (Blueprint $t) {
            $t->unsignedBigInteger('vehicle_id')->nullable()->change();
            $t->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $t->string('search_value', 30)->nullable();
        });
        DB::table('vehicles')->whereNotIn('id', DB::table('theft_reports')->whereIn('status', ['pendiente_validacion', 'validado'])->select('vehicle_id'))->where('status', 'reportado')->update(['status' => 'activo']);
        DB::table('vehicles')->whereIn('id', DB::table('theft_reports')->whereIn('status', ['pendiente_validacion', 'validado'])->select('vehicle_id'))->update(['status' => 'reportado']);
        DB::transaction(function () use ($normalized) {
            foreach ($normalized as $vehicle) {
                DB::table('vehicles')->where('id', $vehicle['id'])->update(['vin' => $vehicle['vin'], 'plate' => $vehicle['plate']]);
            }
        });
    }

    public function down(): void
    {
        // Keep nullable consultation references and expanded statuses to avoid data loss.
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('theft_reports', fn (Blueprint $t) => $t->dropUnique(['vehicle_id']));
        Schema::table('theft_reports', fn (Blueprint $t) => $t->dropConstrainedForeignId('updated_by'));
        Schema::table('consultations', fn (Blueprint $t) => $t->dropColumn('search_value'));
    }
};
