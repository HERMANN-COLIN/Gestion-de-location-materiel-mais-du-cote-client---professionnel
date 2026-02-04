<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('professionnels', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->string('nom_societe');

    // 📍 adresses
    $table->foreignId('adresse_siege_id')
          ->constrained('adresses')
          ->restrictOnDelete();

    $table->foreignId('adresse_livraison_id')
          ->constrained('adresses')
          ->restrictOnDelete();

    // ⏰ horaires
    $table->string('heure_ouverture', 100);
    $table->string('heure_fermeture', 100);

    // 🌍 langue
    $table->foreignId('langue_id')
          ->constrained('langues')
          ->restrictOnDelete();

    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('professionnels');
    }
};
