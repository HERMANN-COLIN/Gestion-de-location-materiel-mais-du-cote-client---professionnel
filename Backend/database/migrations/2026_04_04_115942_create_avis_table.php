<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();

            // Lien vers la commande (un avis par commande)
            $table->foreignId('commande_id')
                  ->constrained('commandes')
                  ->onDelete('cascade');

            // Lien vers l'utilisateur
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Note de 1 à 5
            $table->tinyInteger('note')
                  ->unsigned()
                  ->comment('Note de satisfaction de 1 (très insatisfait) à 5 (très satisfait)');

            // Commentaire libre (optionnel)
            $table->text('commentaire')->nullable();

            $table->timestamps();

            // Un utilisateur ne peut laisser qu'un seul avis par commande
            $table->unique(['commande_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};