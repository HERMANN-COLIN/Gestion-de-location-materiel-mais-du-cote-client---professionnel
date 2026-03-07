<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('numero_commande', 50)->unique();
            $table->timestamp('date_commande');
            $table->date('date_debut');
            $table->date('date_fin');
            
            // Statuts et Modes
            $table->foreignId('statut')->constrained('statuts');
            $table->foreignId('mode_livraison')->constrained('modes_livraison');
            $table->foreignId('mode_retour')->constrained('modes_retour');

            // 🔥 ADRESSES - Les deux champs !
            $table->foreignId('adresse_livraison_id')->nullable()->constrained('adresses')->onDelete('set null');
            $table->string('adresse_livraison', 255)->nullable(); // ✅ AJOUTÉ - Texte figé

            // Montants
            $table->decimal('montant_total', 10, 2);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->decimal('frais_retour', 10, 2)->default(0);
            
            // Code réduction (si vous l'utilisez)
            $table->foreignId('code_reduction_id')->nullable()->constrained('codes_reduction')->onDelete('set null');

            // Logistique spécifique
            $table->string('jour_livraison')->nullable();
            $table->decimal('distance_livraison', 8, 2)->nullable();
            $table->string('jour_retour')->nullable();
            $table->decimal('distance_retour', 8, 2)->nullable();

            // Infos complémentaires
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};