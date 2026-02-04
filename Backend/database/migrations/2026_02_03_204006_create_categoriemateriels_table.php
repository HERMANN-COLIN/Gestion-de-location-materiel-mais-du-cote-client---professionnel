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
        Schema::create('categorie_materiel', function (Blueprint $table) {
    $table->id(); // [cite: 55]
    $table->string('nom', 100); // [cite: 55]
    $table->text('description')->nullable(); // [cite: 55]
    $table->timestamps(); // Gère created_at [cite: 55]
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoriemateriels');
    }
};
