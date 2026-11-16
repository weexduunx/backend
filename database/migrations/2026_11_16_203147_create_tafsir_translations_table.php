<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tafsirs écrits traduits en français (traduction automatique).
     * Le texte d'origine est conservé pour pouvoir retraduire ou vérifier.
     */
    public function up(): void
    {
        Schema::create('tafsir_translations', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32);            // ex. 'muyassar'
            $table->unsignedSmallInteger('surah');
            $table->unsignedSmallInteger('ayah');
            $table->string('verse_key', 10);         // ex. '2:255'
            $table->text('text_original');
            $table->string('language', 5)->default('fr');
            $table->text('text_translated')->nullable();
            $table->string('translator', 32)->nullable(); // ex. 'deepl'
            $table->timestamp('translated_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'language', 'surah', 'ayah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tafsir_translations');
    }
};
