<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tafsirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('surahs')->onDelete('cascade');
            $table->string('hafiz_name');
            $table->string('audio_file_path');
            $table->string('audio_url')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('language', 10)->default('wo');
            $table->text('description')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['surah_id', 'hafiz_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tafsirs');
    }
};
