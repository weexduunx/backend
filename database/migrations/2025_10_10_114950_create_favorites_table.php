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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('verse_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('surah_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type')->default('verse');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'verse_id', 'surah_id', 'type']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
