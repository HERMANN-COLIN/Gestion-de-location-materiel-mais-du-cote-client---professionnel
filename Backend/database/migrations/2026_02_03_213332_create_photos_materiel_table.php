<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photos_materiel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materiel_id')
                  ->constrained('materiels')
                  ->onDelete('cascade');
            $table->string('url_photo');
            $table->integer('ordre_affichage')->default(0); 
            $table->boolean('est_principale')->default(false);
            $table->timestamps();
        });

        // Liste complète basée sur vos fichiers réels
        $data = [
            // --- CHAISE CHIAVARI (ID 1) : 3 Photos ---
            ['materiel_id' => 1, 'url_photo' => '/materiels/chaise-chiavari1.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 1, 'url_photo' => '/materiels/chaise-chiavari2.png', 'ordre' => 2, 'main' => false],
            ['materiel_id' => 1, 'url_photo' => '/materiels/chaise-chiavari3.png', 'ordre' => 3, 'main' => false],

            // --- CHAISE PLIANTE (ID 2) ---
            ['materiel_id' => 2, 'url_photo' => '/materiels/chaise-pliante.png', 'ordre' => 1, 'main' => true],

            // --- CHAISE BANQUET (ID 3) : 3 Photos ---
            ['materiel_id' => 3, 'url_photo' => '/materiels/chaise-banquet1.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 3, 'url_photo' => '/materiels/chaise-banquet2.png', 'ordre' => 2, 'main' => false],
            ['materiel_id' => 3, 'url_photo' => '/materiels/chaise-banquet3.png', 'ordre' => 3, 'main' => false],

            // --- TABOURETS (ID 4 à 6) ---
            ['materiel_id' => 4, 'url_photo' => '/materiels/tabouret-haut.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 5, 'url_photo' => '/materiels/tabouret-bas.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 6, 'url_photo' => '/materiels/tabouret-design.png', 'ordre' => 1, 'main' => true],

            // --- FAUTEUILS (ID 7 à 9) ---
            ['materiel_id' => 7, 'url_photo' => '/materiels/fauteuil-lounge.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 8, 'url_photo' => '/materiels/fauteuil-mariage.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 9, 'url_photo' => '/materiels/fauteuil-conference.png', 'ordre' => 1, 'main' => true],

            // --- TABLES (ID 10 à 15) ---
            ['materiel_id' => 10, 'url_photo' => '/materiels/table-ronde-6.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 11, 'url_photo' => '/materiels/table-ronde-8.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 12, 'url_photo' => '/materiels/table-ronde-pliante.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 13, 'url_photo' => '/materiels/table-rect-std.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 14, 'url_photo' => '/materiels/table-rect-longue.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 15, 'url_photo' => '/materiels/table-rect-pliante.png', 'ordre' => 1, 'main' => true],

            // --- DÉCORATION (ID 16 à 24) ---
            ['materiel_id' => 16, 'url_photo' => '/materiels/guirlande-blanche.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 17, 'url_photo' => '/materiels/guirlande-chaude.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 18, 'url_photo' => '/materiels/guirlande-ext.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 19, 'url_photo' => '/materiels/spot-led.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 20, 'url_photo' => '/materiels/projecteur-deco.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 21, 'url_photo' => '/materiels/lampe-pied.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 22, 'url_photo' => '/materiels/drap-blanc.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 23, 'url_photo' => '/materiels/drap-noir.png', 'ordre' => 1, 'main' => true],
            ['materiel_id' => 24, 'url_photo' => '/materiels/drap-deco.png', 'ordre' => 1, 'main' => true],
        ];

        foreach ($data as $item) {
            DB::table('photos_materiel')->insert([
                'materiel_id'     => $item['materiel_id'],
                'url_photo'       => $item['url_photo'],
                'ordre_affichage' => $item['ordre'],
                'est_principale'  => $item['main'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos_materiel');
    }
};