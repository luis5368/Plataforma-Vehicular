<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staging_import_batches', function (Blueprint $table) {
            $table->id();
            $table->char('file_hash', 64)->unique();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('row_count');
            $table->string('result', 20);
            $table->timestamps();
        });
        Schema::table('vehicles', fn (Blueprint $table) => $table->foreignId('staging_import_batch_id')->nullable()->constrained('staging_import_batches')->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('vehicles', fn (Blueprint $table) => $table->dropConstrainedForeignId('staging_import_batch_id'));
        Schema::dropIfExists('staging_import_batches');
    }
};
