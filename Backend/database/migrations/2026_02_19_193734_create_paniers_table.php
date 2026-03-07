<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('materiel_id')->constrained()->onDelete('cascade');
            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire_ht', 10, 2);
            $table->decimal('taux_tva', 5, 2)->default(21.00);
            $table->timestamps();

            // Un utilisateur ne peut avoir qu'une seule entrée par matériel dans son panier
            $table->unique(['user_id', 'materiel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paniers');
    }
};