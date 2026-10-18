<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theft_reports', function (Blueprint $table) {
            $table->string('validation_status', 30)->default('pendiente');
            $table->string('case_status', 20)->default('abierto');
            $table->string('validation_method', 30)->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evidence_reference', 255)->nullable();
            $table->text('review_reason')->nullable();
        });
        // Preserve the legacy state; never infer validation of pending or resolved cases.
        DB::table('theft_reports')->where('status', 'validado')->update(['validation_status' => 'validado', 'validation_method' => 'legado']);
        DB::table('theft_reports')->where('status', 'rechazado')->update(['validation_status' => 'rechazado']);
        DB::table('theft_reports')->where('status', 'resuelto')->update(['case_status' => 'resuelto']);
        DB::table('theft_reports')->where('status', 'retirado')->update(['case_status' => 'retirado']);
    }

    public function down(): void
    {
        // status remains a compatibility projection; audit snapshots retain the full history.
        Schema::table('theft_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['validation_status', 'case_status', 'validation_method', 'validated_at', 'evidence_reference', 'review_reason']);
        });
    }
};
