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
        Schema::create('reductionclients', function (Blueprint $table) {
           $table->id();
    $table->foreignId('user_id')->constrained('users'); // [cite: 72]
    $table->foreignId('code_reduction_id')->constrained('code_reduction'); // [cite: 72]
    $table->date('date_attribution'); // [cite: 72]
    $table->date('date_expiration'); // [cite: 72]
    $table->decimal('montant_fixe', 10, 2)->nullable(); // [cite: 72]
    $table->decimal('pourcentage', 5, 2)->nullable(); // [cite: 72]
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reductionclients');
    }
};
