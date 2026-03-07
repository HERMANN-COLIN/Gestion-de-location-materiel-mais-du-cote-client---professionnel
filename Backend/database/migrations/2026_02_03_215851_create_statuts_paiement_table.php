<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Indispensable pour l'insertion

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('statuts_paiement', function (Blueprint $table) {
            $table->id();
            $table->string('statut');
            $table->timestamps();
        });

        // Insertion des statuts de paiement demandés
        DB::table('statuts_paiement')->insert([
            [
                'id' => 1,
                'statut' => 'Non payée',
                'created_at' => '2025-12-01 00:00:00',
                'updated_at' => '2025-12-01 00:00:00',
            ],
            [
                'id' => 2,
                'statut' => 'Payée',
                'created_at' => '2025-12-02 00:00:00',
                'updated_at' => '2025-12-02 00:00:00',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Correction pour correspondre au nom de la table créée dans up()
        Schema::dropIfExists('statuts_paiement');
    }
};