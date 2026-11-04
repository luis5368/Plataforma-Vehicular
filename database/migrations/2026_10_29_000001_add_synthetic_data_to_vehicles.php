<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', fn (Blueprint $table) => $table->boolean('synthetic_data')->default(false)->index());
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['synthetic_data']);
            $table->dropColumn('synthetic_data');
        });
    }
};
