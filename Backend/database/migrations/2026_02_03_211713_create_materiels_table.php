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
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')
                  ->constrained('categorie_materiel')
                  ->onDelete('cascade'); // Ajout recommandé pour la cohérence des données
            $table->string('nom', 150);
            $table->text('description');
            $table->decimal('prix_journalier', 10, 2);
            $table->string('dimensions', 100)->nullable(); // Rendre nullable si pas toujours nécessaire
            $table->integer('stock_total');
            $table->integer('stock_disponible');
            $table->timestamps();
            
            // Index pour optimiser les recherches
            $table->index('categorie_id');
            $table->index('nom');
            $table->index(['stock_disponible', 'prix_journalier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};