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
        Schema::create('codereductions', function (Blueprint $table) {
            $table->id();
    $table->string('code', 50)->unique(); // [cite: 69]
    $table->foreignId('type_reduction')->constrained('type_reduction'); // [cite: 69]
    $table->decimal('montant', 10, 2); // [cite: 69]
    $table->boolean('hors_tva')->default(false); // [cite: 69]
    $table->date('date_debut'); // [cite: 69]
    $table->date('date_fin'); // [cite: 69]
    $table->integer('utilisations_max'); // [cite: 69]
    $table->integer('utilisations_actuelles')->default(0); // [cite: 69]
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codereductions');
    }
};
