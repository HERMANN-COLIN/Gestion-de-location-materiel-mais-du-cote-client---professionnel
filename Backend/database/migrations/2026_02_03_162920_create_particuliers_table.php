<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('particuliers', function (Blueprint $table) {
            $table->id();

            // 🔗 relations
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('langue_id')
                  ->constrained('langues')
                  ->restrictOnDelete();

            // 👤 infos personnelles
            $table->string('nom');
            $table->string('prenom');
           $table->foreignId('adresse_livraison_id')
                  ->nullable()
                  ->constrained('adresses')
                  ->nullOnDelete();
            

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('particuliers');
    }
};
