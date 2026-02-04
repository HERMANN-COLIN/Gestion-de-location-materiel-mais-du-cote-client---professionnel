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
  Schema::create('contact_pro', function (Blueprint $table) {
    $table->id(); // [cite: 51]
    $table->foreignId('professionnel_id')->constrained('professionnels'); // Référence à professionnels [cite: 51]
    $table->string('nom', 100); // [cite: 51]
    $table->string('prenom', 100); // [cite: 51]
    $table->string('email')->unique(); // [cite: 51]
    $table->string('telephone', 20); // [cite: 51]
    $table->foreignId('fonction')->constrained('fonctions'); // Référence à fonctions [cite: 51]
    $table->timestamps(); // Gère created_at [cite: 51]
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactpros');
    }
};
