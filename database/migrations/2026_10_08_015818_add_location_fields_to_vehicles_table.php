<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('department', 100)->nullable()->after('color');
            $table->string('municipality', 100)->nullable()->after('department');
            $table->string('zone', 50)->nullable()->after('municipality');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['department', 'municipality', 'zone']);
        });
    }
};