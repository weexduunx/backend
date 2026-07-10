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
        Schema::table('reciter_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('quran_com_recitation_id')->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reciter_profiles', function (Blueprint $table) {
            $table->dropColumn('quran_com_recitation_id');
        });
    }
};
