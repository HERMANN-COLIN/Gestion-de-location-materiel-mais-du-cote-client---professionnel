<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Nécessaire pour l'insertion

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('types_reduction', function (Blueprint $table) {
            $table->id();
            $table->string('reduction');
            $table->timestamps();
        });

        // Insertion des données demandées
        DB::table('types_reduction')->insert([
            [
                'id' => 1,
                'reduction' => 'Fixe',
                'created_at' => '2025-12-01 00:00:00',
                'updated_at' => '2025-12-01 00:00:00',
            ],
            [
                'id' => 2,
                'reduction' => 'Pourcentage',
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
        Schema::dropIfExists('types_reduction');
    }
};