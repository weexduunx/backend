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
        Schema::create('verse_word_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained()->onDelete('cascade');
            $table->foreignId('reciter_profile_id')->constrained()->onDelete('cascade');
            $table->integer('total_duration'); // durée totale en millisecondes
            $table->json('words_data'); // données des mots avec timing
            $table->enum('source', ['quran_align', 'estimated', 'manual'])->default('estimated');
            $table->decimal('accuracy', 3, 2)->default(0.75); // précision 0-1
            $table->json('metadata')->nullable(); // données supplémentaires
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->unique(['verse_id', 'reciter_profile_id']);
            $table->index(['verse_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verse_word_timings');
    }
};
