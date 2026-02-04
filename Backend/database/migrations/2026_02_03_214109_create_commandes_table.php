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
        Schema::create('commandes', function (Blueprint $table) {
           $table->id();
    $table->foreignId('user_id')->constrained('users'); // [cite: 61]
    $table->string('numero_commande', 50); // [cite: 61]
    $table->date('date_debut'); // [cite: 61]
    $table->date('date_fin'); // [cite: 61]
    $table->foreignId('statut')->constrained('statuts'); // [cite: 61]
    $table->foreignId('mode_livraison')->constrained('mode_livraison'); // [cite: 61]
    $table->foreignId('mode_retour')->constrained('mode_retour'); // [cite: 61]
    $table->string('adresse_livraison', 255)->nullable(); // [cite: 61]
    $table->decimal('montant_total', 10, 2); // [cite: 62]
    $table->decimal('frais_livraison', 10, 2); // [cite: 62]
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
