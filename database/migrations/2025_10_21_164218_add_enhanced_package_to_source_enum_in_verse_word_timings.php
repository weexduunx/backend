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
        Schema::table('verse_word_timings', function (Blueprint $table) {
            $table->enum('source', ['quran_align', 'estimated', 'manual', 'enhanced_package'])->default('estimated')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verse_word_timings', function (Blueprint $table) {
            $table->enum('source', ['quran_align', 'estimated', 'manual'])->default('estimated')->change();
        });
    }
};
