<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Crucial pour l'insertion

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modes_retour', function (Blueprint $table) {
            $table->id();
            $table->string('retour');
            $table->timestamps();
        });

        // Insertion des données pour les modes de retour
        DB::table('modes_retour')->insert([
            [
                'id' => 1,
                'retour' => 'Sur place',
                'created_at' => '2025-12-01 00:00:00',
                'updated_at' => '2025-12-01 00:00:00',
            ],
            [
                'id' => 2,
                'retour' => 'Livraison',
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
        // Correction ici pour correspondre au nom de la table créée au-dessus
        Schema::dropIfExists('modes_retour');
    }
};