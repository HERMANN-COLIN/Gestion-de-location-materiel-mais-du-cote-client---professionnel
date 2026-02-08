<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // Ajout de la colonne 'type'
            $table->timestamps();
        });

        // Les données sont insérées APRÈS la création de la table
        // Cette partie doit être en dehors de la fonction de création de schéma
        DB::table('types')->insert([
            ['type' => 'particulier', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'professionnel', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('types');
    }
};