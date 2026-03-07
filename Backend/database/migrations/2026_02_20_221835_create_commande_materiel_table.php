<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_commande_materiel_table.php

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
        Schema::create('commande_materiel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->onDelete('cascade');
            $table->foreignId('materiel_id')->constrained()->onDelete('cascade');
            $table->integer('quantite');
            
            // Prix unitaire HT
            $table->decimal('prix_unitaire_ht', 10, 2);
            
            // ✅ AJOUT : Taux de TVA appliqué au moment de la commande
            $table->decimal('taux_tva', 5, 2)->default(21.00);
            
            // ✅ AJOUT : Prix unitaire TTC calculé
            $table->decimal('prix_unitaire_ttc', 10, 2);
            
            // Sous-total HT (quantite * prix_unitaire_ht)
            $table->decimal('sous_total_ht', 10, 2);
            
            // ✅ AJOUT : Sous-total TTC (quantite * prix_unitaire_ttc)
            $table->decimal('sous_total_ttc', 10, 2);
            
            // ✅ AJOUT : Montant de la TVA pour cette ligne
            $table->decimal('montant_tva', 10, 2);
            
            $table->timestamps();
            
            // Éviter les doublons
            $table->unique(['commande_id', 'materiel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commande_materiel');
    }
};