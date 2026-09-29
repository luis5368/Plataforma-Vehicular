<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vin', 17)->unique()->index(); // Número de Identificación Vehicular
            $table->string('plate', 20)->unique()->index(); // Placa
            $table->string('brand', 100); // Marca
            $table->string('model', 100); // Modelo
            $table->year('year')->nullable(); // Año
            $table->string('color', 50)->nullable(); // Color
            $table->string('theft_report_address')->nullable(); // Dirección del reporte de robo
            $table->text('observations')->nullable(); // Observaciones adicionales
            $table->enum('status', ['activo', 'reportado', 'vendido', 'inactivo'])->default('activo');
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};