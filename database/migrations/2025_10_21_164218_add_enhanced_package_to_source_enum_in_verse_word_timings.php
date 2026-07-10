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
        // Modify the enum to add 'enhanced_package'
        DB::statement("ALTER TABLE verse_word_timings MODIFY COLUMN source ENUM('quran_align', 'estimated', 'manual', 'enhanced_package') DEFAULT 'estimated'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the enum to original values
        DB::statement("ALTER TABLE verse_word_timings MODIFY COLUMN source ENUM('quran_align', 'estimated', 'manual') DEFAULT 'estimated'");
    }
};
