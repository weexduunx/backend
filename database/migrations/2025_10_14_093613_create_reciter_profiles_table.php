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
        Schema::create('reciter_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., 'ar.alafasy'
            $table->string('name'); // e.g., 'Mishary Rashid Alafasy'
            $table->integer('average_speed')->default(80); // mots par minute
            $table->decimal('pause_multiplier', 3, 2)->default(1.3); // multiplicateur de pause
            $table->enum('tajweed_style', ['minimal', 'moderate', 'extensive'])->default('moderate');
            $table->json('supported_verses')->nullable(); // array des versets supportés
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reciter_profiles');
    }
};
