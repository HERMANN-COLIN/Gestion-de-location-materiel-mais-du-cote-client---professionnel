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
        Schema::create('detailcommandes', function (Blueprint $table) {
            $table->id();
    $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade'); // [cite: 67]
    $table->foreignId('materiel_id')->constrained('materiels'); // [cite: 67]
    $table->integer('quantite'); // [cite: 67]
    $table->decimal('prix_unitaire', 10, 2); // [cite: 67]
    $table->decimal('sous_total', 10, 2); // [cite: 67]
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detailcommandes');
    }
};
