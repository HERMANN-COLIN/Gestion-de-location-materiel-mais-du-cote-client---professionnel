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
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
    $table->foreignId('commande_id')->constrained('commandes'); // [cite: 77]
    $table->string('numero_facture', 50); // [cite: 77]
    $table->foreignId('type')->constrained('type_document'); // [cite: 77]
    $table->date('date_emission'); // [cite: 77]
    $table->date('date_echeance'); // [cite: 77]
    $table->decimal('montant_ht', 10, 2); // [cite: 77]
    $table->decimal('montant_tva', 10, 2); // [cite: 77]
    $table->decimal('montant_ttc', 10, 2); // [cite: 77]
    $table->foreignId('statut_paiement')->constrained('statut_paiement'); // [cite: 77]
    $table->string('url_pdf'); // [cite: 78]
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
