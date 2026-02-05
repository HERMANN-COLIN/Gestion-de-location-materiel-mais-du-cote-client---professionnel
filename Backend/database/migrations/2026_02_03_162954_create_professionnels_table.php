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
            
            // Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            $table->string('nom_societe');
            
            // 📍 adresses - utilisez nullOnDelete() si une adresse peut être supprimée sans supprimer le professionnel
            $table->foreignId('adresse_siege_id')
                  ->nullable()
                  ->constrained('adresses')
                  ->nullOnDelete();
            
            $table->foreignId('adresse_livraison_id')
                  ->nullable()
                  ->constrained('adresses')
                  ->nullOnDelete();
            
            // ⏰ horaires - utilisez time() pour stocker des heures
            $table->time('heure_ouverture')->nullable();
            $table->time('heure_fermeture')->nullable();
            
            // 🌍 langue
            $table->foreignId('langue_id')
                  ->nullable()
                  ->constrained('langues')
                  ->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professionnels');
    }
};