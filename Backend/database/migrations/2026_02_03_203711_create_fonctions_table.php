<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fonctions', function (Blueprint $table) {
            $table->id(); // INT (PK) - Identifiant unique
            $table->string('fonction'); // VARCHAR(255) - fonction
            $table->timestamps(); // TIMESTAMP - created_at
        });

        // Insertion des 5 exemples de fonctions
        DB::table('fonctions')->insert([
            [
                'fonction' => 'Responsable logistique',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fonction' => 'Directrice commerciale',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fonction' => 'Gestionnaire de compte',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fonction' => 'Responsable événementiel',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fonction' => 'Chef de projet',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fonctions');
    }
};