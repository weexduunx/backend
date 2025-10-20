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
        Schema::create('word_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_word_timing_id')->constrained()->onDelete('cascade');
            $table->integer('word_index'); // position du mot dans le verset
            $table->string('arabic_text'); // texte arabe du mot
            $table->integer('start_time'); // temps de début en ms
            $table->integer('end_time'); // temps de fin en ms
            $table->integer('duration'); // durée en ms
            $table->decimal('confidence', 3, 2)->default(0.75); // confiance 0-1
            $table->json('tajweed_info')->nullable(); // informations tajweed
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['verse_word_timing_id', 'word_index']);
            $table->index(['start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_timings');
    }
};
