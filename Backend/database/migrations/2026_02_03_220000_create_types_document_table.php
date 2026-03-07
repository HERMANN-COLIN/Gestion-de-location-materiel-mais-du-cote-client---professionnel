<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Toujours indispensable pour l'insertion

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('types_document', function (Blueprint $table) {
            $table->id();
            $table->string('document');
            $table->timestamps();
        });

        // Insertion des données demandées
        DB::table('types_document')->insert([
            [
                'id' => 1,
                'document' => 'Devis',
                'created_at' => '2025-12-01 00:00:00',
                'updated_at' => '2025-12-01 00:00:00',
            ],
            [
                'id' => 2,
                'document' => 'Facture',
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
        // Correction du nom de la table ici
        Schema::dropIfExists('types_document');
    }
};