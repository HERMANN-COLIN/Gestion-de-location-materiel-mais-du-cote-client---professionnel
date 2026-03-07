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
        Schema::create('categories_materiel', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insertion des données d'exemple
        DB::table('categories_materiel')->insert([
            [
                'nom' => 'Sièges',
                'description' => 'Chaises, tabourets, fauteuils',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nom' => 'Tables',
                'description' => 'Tables rondes, rectangulaires, carrées',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nom' => 'Décoration',
                'description' => 'Guirlandes, luminaires, draps, décoration événementielle',
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
        Schema::dropIfExists('categories_materiel');
    }
};