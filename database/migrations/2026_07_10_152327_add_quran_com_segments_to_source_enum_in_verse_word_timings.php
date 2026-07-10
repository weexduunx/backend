<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE verse_word_timings MODIFY COLUMN source ENUM('quran_align', 'estimated', 'manual', 'enhanced_package', 'quran_com_segments') DEFAULT 'estimated'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE verse_word_timings MODIFY COLUMN source ENUM('quran_align', 'estimated', 'manual', 'enhanced_package') DEFAULT 'estimated'");
    }
};
