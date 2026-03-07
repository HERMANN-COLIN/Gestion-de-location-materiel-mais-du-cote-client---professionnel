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
        Schema::create('frais_livraison', function (Blueprint $table) {
            $table->id();
            // Attention : respectez bien la casse définie dans l'enum lors de l'insertion
            $table->enum('jour_semaine', ['Lundi-vendredi', 'Samedi', 'dimanche']); 
            $table->integer('distance_max'); // Distance en km
            $table->decimal('montant', 10, 2); // Montant des frais
            $table->timestamps();
        });

        // Insertion des données d'exemple
        DB::table('frais_livraison')->insert([
            [
                'id' => 1,
                'jour_semaine' => 'Samedi',
                'distance_max' => 50,
                'montant' => 50.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'jour_semaine' => 'dimanche',
                'distance_max' => 50,
                'montant' => 75.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'jour_semaine' => 'Lundi-vendredi',
                'distance_max' => 20,
                'montant' => 25.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frais_livraison');
    }
};