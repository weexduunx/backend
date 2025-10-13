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
        Schema::create('verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('surahs')->onDelete('cascade');
            $table->integer('verse_number');
            $table->integer('global_number')->unique();
            $table->text('text_arabic');
            $table->text('text_french');
            $table->text('text_transliteration')->nullable();
            $table->integer('juz')->nullable();
            $table->integer('hizb')->nullable();
            $table->integer('rub')->nullable();
            $table->integer('page')->nullable();
            $table->timestamps();

            $table->index(['surah_id', 'verse_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verses');
    }
};
