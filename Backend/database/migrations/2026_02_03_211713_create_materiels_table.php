<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')
                  ->constrained('categories_materiel')
                  ->onDelete('cascade');
            $table->string('nom', 150);
            $table->text('description')->nullable();
            
            // On utilise 'prix_journalier_ht' pour être cohérent avec vos besoins de calcul
            $table->decimal('prix_journalier_ht', 10, 2); 
            $table->decimal('taux_tva', 5, 2)->default(21.00);

            $table->string('dimensions', 100)->nullable();
            $table->integer('stock_total')->default(0);
            $table->integer('stock_disponible')->default(0);
            $table->timestamps();
        });

        DB::table('materiels')->insert([
            // =======================
            // CATÉGORIE 1 : SIÈGES
            // =======================
            [
                'categorie_id' => 1, 'nom' => 'Chaise Chiavari', 'description' => 'Chaise élégante pour événements',
                'prix_journalier_ht' => 2.50, 'dimensions' => '40x40x90cm', 'stock_total' => 200, 'stock_disponible' => 180,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Chaise pliante', 'description' => 'Chaise pratique et légère',
                'prix_journalier_ht' => 1.50, 'dimensions' => '45x45x80cm', 'stock_total' => 500, 'stock_disponible' => 450,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Chaise banquet', 'description' => 'Chaise confortable pour réception',
                'prix_journalier_ht' => 3.00, 'dimensions' => '50x50x95cm', 'stock_total' => 150, 'stock_disponible' => 120,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Tabouret haut', 'description' => 'Tabouret haut pour bar',
                'prix_journalier_ht' => 5.00, 'dimensions' => '35x35x110cm', 'stock_total' => 80, 'stock_disponible' => 70,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Tabouret bas', 'description' => 'Tabouret bas polyvalent',
                'prix_journalier_ht' => 4.00, 'dimensions' => '35x35x45cm', 'stock_total' => 60, 'stock_disponible' => 60,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Tabouret design', 'description' => 'Tabouret moderne et design',
                'prix_journalier_ht' => 7.50, 'dimensions' => '40x40x105cm', 'stock_total' => 40, 'stock_disponible' => 35,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Fauteuil lounge', 'description' => 'Fauteuil confortable pour espaces détente',
                'prix_journalier_ht' => 15.00, 'dimensions' => '80x80x75cm', 'stock_total' => 20, 'stock_disponible' => 15,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Fauteuil mariage', 'description' => 'Fauteuil élégant pour mariages',
                'prix_journalier_ht' => 25.00, 'dimensions' => '70x70x120cm', 'stock_total' => 10, 'stock_disponible' => 8,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 1, 'nom' => 'Fauteuil de conférence', 'description' => 'Fauteuil style conférence',
                'prix_journalier_ht' => 12.00, 'dimensions' => '60x60x100cm', 'stock_total' => 100, 'stock_disponible' => 90,
                'created_at' => now(), 'updated_at' => now()
            ],

            // =======================
            // CATÉGORIE 2 : TABLES
            // =======================
            [
                'categorie_id' => 2, 'nom' => 'Table ronde 6 personnes', 'description' => 'Table ronde pour 6 personnes',
                'prix_journalier_ht' => 12.00, 'dimensions' => 'Ø120cm', 'stock_total' => 30, 'stock_disponible' => 25,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 2, 'nom' => 'Table ronde 8 personnes', 'description' => 'Table ronde pour grands événements',
                'prix_journalier_ht' => 15.00, 'dimensions' => 'Ø150cm', 'stock_total' => 20, 'stock_disponible' => 18,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 2, 'nom' => 'Table ronde pliante', 'description' => 'Table ronde facile à transporter',
                'prix_journalier_ht' => 10.00, 'dimensions' => 'Ø110cm', 'stock_total' => 40, 'stock_disponible' => 40,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 2, 'nom' => 'Table rectangulaire standard', 'description' => 'Table rectangulaire classique',
                'prix_journalier_ht' => 10.00, 'dimensions' => '180x80cm', 'stock_total' => 50, 'stock_disponible' => 45,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 2, 'nom' => 'Table rectangulaire longue', 'description' => 'Table pour buffets et réunions',
                'prix_journalier_ht' => 18.00, 'dimensions' => '240x100cm', 'stock_total' => 15, 'stock_disponible' => 10,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 2, 'nom' => 'Table rectangulaire pliante', 'description' => 'Table facile à stocker',
                'prix_journalier_ht' => 8.00, 'dimensions' => '150x75cm', 'stock_total' => 60, 'stock_disponible' => 55,
                'created_at' => now(), 'updated_at' => now()
            ],

            // =======================
            // CATÉGORIE 3 : DÉCORATION
            // =======================
            [
                'categorie_id' => 3, 'nom' => 'Guirlande LED blanche', 'description' => 'Guirlande LED lumière blanche',
                'prix_journalier_ht' => 5.00, 'dimensions' => '10m', 'stock_total' => 100, 'stock_disponible' => 90,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Guirlande LED chaude', 'description' => 'Ambiance chaleureuse',
                'prix_journalier_ht' => 5.00, 'dimensions' => '10m', 'stock_total' => 100, 'stock_disponible' => 85,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Guirlande extérieure', 'description' => 'Guirlande résistante extérieur',
                'prix_journalier_ht' => 8.00, 'dimensions' => '20m', 'stock_total' => 50, 'stock_disponible' => 40,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Spot LED', 'description' => 'Éclairage puissant',
                'prix_journalier_ht' => 10.00, 'dimensions' => '20x20cm', 'stock_total' => 40, 'stock_disponible' => 35,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Projecteur décoratif', 'description' => 'Éclairage décoratif',
                'prix_journalier_ht' => 15.00, 'dimensions' => '30x30cm', 'stock_total' => 20, 'stock_disponible' => 18,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Lampe sur pied', 'description' => 'Lampe décorative',
                'prix_journalier_ht' => 20.00, 'dimensions' => 'H160cm', 'stock_total' => 10, 'stock_disponible' => 10,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Drap de table blanc', 'description' => 'Drap élégant pour tables',
                'prix_journalier_ht' => 4.00, 'dimensions' => '250x150cm', 'stock_total' => 100, 'stock_disponible' => 95,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Drap de table noir', 'description' => 'Drap chic pour événements',
                'prix_journalier_ht' => 4.00, 'dimensions' => '250x150cm', 'stock_total' => 80, 'stock_disponible' => 75,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'categorie_id' => 3, 'nom' => 'Drap décoratif', 'description' => 'Drap pour décoration murale',
                'prix_journalier_ht' => 6.00, 'dimensions' => '300x200cm', 'stock_total' => 30, 'stock_disponible' => 28,
                'created_at' => now(), 'updated_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};