<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avis des bêta-testeurs envoyés depuis l'app (anonymes, contact facultatif).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rating')->nullable();          // 1 à 5 étoiles
            $table->string('category', 20)->default('general');         // general | bug | idea | content
            $table->json('liked')->nullable();                          // fonctionnalités appréciées
            $table->text('message')->nullable();
            $table->string('contact', 120)->nullable();                 // e-mail ou téléphone, si l'utilisateur veut être recontacté
            $table->string('screen', 60)->nullable();                   // écran d'où l'avis a été envoyé
            $table->uuid('client_id')->nullable()->index();             // identifiant d'installation anonyme
            $table->string('app_version', 20)->nullable();
            $table->string('build', 20)->nullable();
            $table->string('platform', 10)->nullable();
            $table->string('os_version', 20)->nullable();
            $table->string('device', 80)->nullable();
            $table->string('locale', 20)->nullable();
            $table->string('place', 120)->nullable();
            $table->timestamp('sent_at')->nullable();                   // moment de l'écriture (l'envoi peut être différé hors ligne)
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_feedback');
    }
};
