<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vehicles', function (Blueprint $table) {
            // Agregamos después de la dirección del reporte
            $table->decimal('theft_latitude', 10, 8)->nullable()->after('theft_report_address');
            $table->decimal('theft_longitude', 11, 8)->nullable()->after('theft_latitude');
        });
    }

    public function down(): void {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['theft_latitude', 'theft_longitude']);
        });
    }
};