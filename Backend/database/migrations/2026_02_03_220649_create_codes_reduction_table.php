<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Indispensable pour l'insertion

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('codes_reduction', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            // Correction : 'types_reduction' au pluriel pour correspondre à votre table précédente
            $table->foreignId('type_reduction_id')->constrained('types_reduction'); 
            $table->decimal('montant', 10, 2);
            $table->boolean('hors_tva')->default(false);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->integer('utilisations_max');
            $table->integer('utilisations_actuelles')->default(0);
            $table->timestamps();
        });

        // Insertion d'exemples de codes promo
        DB::table('codes_reduction')->insert([
            [
                'code' => 'BIENVENUE10',
                'type_reduction_id' => 1, // Fixe (10€ par ex)
                'montant' => 10.00,
                'hors_tva' => true,
                'date_debut' => '2026-01-01',
                'date_fin' => '2026-12-31',
                'utilisations_max' => 100,
                'utilisations_actuelles' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ETE2026',
                'type_reduction_id' => 2, // Pourcentage (15%)
                'montant' => 15.00,
                'hors_tva' => false,
                'date_debut' => '2026-06-01',
                'date_fin' => '2026-08-31',
                'utilisations_max' => 50,
                'utilisations_actuelles' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PROMOFLASH',
                'type_reduction_id' => 1, // Fixe (50€)
                'montant' => 50.00,
                'hors_tva' => false,
                'date_debut' => '2026-02-10',
                'date_fin' => '2026-02-20',
                'utilisations_max' => 10,
                'utilisations_actuelles' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Correction du nom pour correspondre à la table créée dans up()
        Schema::dropIfExists('codes_reduction');
    }
};