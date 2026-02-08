<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos_materiel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materiel_id')
                  ->constrained('materiels')
                  ->onDelete('cascade');
            $table->string('url_photo');
            $table->timestamps();
        });

       DB::table('photos_materiel')->insert([
    // --- SIÈGES (ID 1 à 9) ---
    ['materiel_id' => 1, 'url_photo' => '/materiels/chaise-chiavari.png', 'created_at' => now()],
    ['materiel_id' => 2, 'url_photo' => '/materiels/chaise-pliante.png', 'created_at' => now()],
    ['materiel_id' => 3, 'url_photo' => '/materiels/chaise-banquet.png', 'created_at' => now()],
    ['materiel_id' => 4, 'url_photo' => '/materiels/tabouret-haut.png', 'created_at' => now()],
    ['materiel_id' => 5, 'url_photo' => '/materiels/tabouret-bas.png', 'created_at' => now()],
    ['materiel_id' => 6, 'url_photo' => '/materiels/tabouret-design.png', 'created_at' => now()],
    ['materiel_id' => 7, 'url_photo' => '/materiels/fauteuil-lounge.png', 'created_at' => now()],
    ['materiel_id' => 8, 'url_photo' => '/materiels/fauteuil-mariage.png', 'created_at' => now()],
    ['materiel_id' => 9, 'url_photo' => '/materiels/fauteuil-conference.png', 'created_at' => now()],

    // --- TABLES (ID 10 à 15) ---
    ['materiel_id' => 10, 'url_photo' => '/materiels/table-ronde-6.png', 'created_at' => now()],
    ['materiel_id' => 11, 'url_photo' => '/materiels/table-ronde-8.png', 'created_at' => now()],
    ['materiel_id' => 12, 'url_photo' => '/materiels/table-ronde-pliante.png', 'created_at' => now()],
    ['materiel_id' => 13, 'url_photo' => '/materiels/table-rect-std.png', 'created_at' => now()],
    ['materiel_id' => 14, 'url_photo' => '/materiels/table-rect-longue.png', 'created_at' => now()],
    ['materiel_id' => 15, 'url_photo' => '/materiels/table-rect-pliante.png', 'created_at' => now()],

    // --- DÉCO (ID 16 à 24) ---
    ['materiel_id' => 16, 'url_photo' => '/materiels/guirlande-blanche.png', 'created_at' => now()],
    ['materiel_id' => 17, 'url_photo' => '/materiels/guirlande-chaude.png', 'created_at' => now()],
    ['materiel_id' => 18, 'url_photo' => '/materiels/guirlande-ext.png', 'created_at' => now()],
    ['materiel_id' => 19, 'url_photo' => '/materiels/spot-led.png', 'created_at' => now()],
    ['materiel_id' => 20, 'url_photo' => '/materiels/projecteur-deco.png', 'created_at' => now()],
    ['materiel_id' => 21, 'url_photo' => '/materiels/lampe-pied.png', 'created_at' => now()],
    ['materiel_id' => 22, 'url_photo' => '/materiels/drap-blanc.png', 'created_at' => now()],
    ['materiel_id' => 23, 'url_photo' => '/materiels/drap-noir.png', 'created_at' => now()],
    ['materiel_id' => 24, 'url_photo' => '/materiels/drap-deco.png', 'created_at' => now()],
]);
    }

    public function down(): void
    {
        Schema::dropIfExists('photos_materiel');
    }
};