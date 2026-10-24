<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theft_report_id')->constrained('theft_reports')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('disk', 100);
            $table->string('path')->unique();
            $table->string('mime_type', 50);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
        // Durable filesystem cleanup, including files whose upload transaction never committed.
        Schema::create('photo_file_deletions', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 100);
            $table->string('path')->unique();
            $table->timestamp('available_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_photos');
        Schema::dropIfExists('photo_file_deletions');
    }
};
