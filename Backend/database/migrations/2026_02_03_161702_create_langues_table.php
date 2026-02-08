<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('langues', function (Blueprint $table) {
            $table->id();
            $table->string('langue')->unique();
            $table->timestamps();
        });
        
        // Optionnel : insérer des données de test
        DB::table('langues')->insert([
            ['langue' => 'Français'],
            ['langue' => 'Anglais'],
            ['langue' => 'Néerlandais'],
            ['langue' => 'Allemand'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('langues');
    }
};