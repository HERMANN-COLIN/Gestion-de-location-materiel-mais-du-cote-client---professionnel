<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importation nécessaire pour l'insertion

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modes_livraison', function (Blueprint $table) {
            $table->id();
            $table->string('livraison');
            $table->timestamps();
        });

        // Insertion des données demandées
        DB::table('modes_livraison')->insert([
            [
                'id' => 1,
                'livraison' => 'Sur place',
                'created_at' => '2025-12-01 00:00:00',
                'updated_at' => '2025-12-01 00:00:00',
            ],
            [
                'id' => 2,
                'livraison' => 'Livraison',
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
        Schema::dropIfExists('modes_livraison');
    }
};