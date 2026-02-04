<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fraislivraisons', function (Blueprint $table) {
            $table->id();
          $table->enum('jour_semaine', ['Lundi-vendredi', 'Samedi', 'dimanche']); 
    $table->integer('distance_max'); // Distance en km [cite: 75]
    $table->decimal('montant', 10, 2); // Montant des frais [cite: 75]
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraislivraisons');
    }
};
