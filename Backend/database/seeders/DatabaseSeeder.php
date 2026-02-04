<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // NE PAS créer d'utilisateur test - la table users n'a pas de champ 'name'
        // Les utilisateurs seront créés via le formulaire d'inscription
        
        echo "\n🌱 Démarrage du seeding...\n\n";

        // Types d'utilisateurs
        echo "📝 Création des types d'utilisateurs...\n";
        DB::table('types')->insert([
            ['type' => 'Particulier', 'created_at' => now()],
            ['type' => 'Professionnel', 'created_at' => now()],
        ]);
        echo "   ✅ 2 types créés\n\n";

        // Langues
        echo "🌍 Création des langues...\n";
        DB::table('langues')->insert([
            ['langue' => 'Français', 'created_at' => now()],
            ['langue' => 'Anglais', 'created_at' => now()],
            ['langue' => 'Néerlandais', 'created_at' => now()],
        ]);
        echo "   ✅ 3 langues créées\n\n";

        // Fonctions
        echo "💼 Création des fonctions...\n";
        DB::table('fonctions')->insert([
            ['fonction' => 'Responsable logistique', 'created_at' => now()],
            ['fonction' => 'Directrice commerciale', 'created_at' => now()],
            ['fonction' => 'Gérant', 'created_at' => now()],
            ['fonction' => 'Directeur', 'created_at' => now()],
            ['fonction' => 'Chef de projet', 'created_at' => now()],
            ['fonction' => 'Assistant', 'created_at' => now()],
        ]);
        echo "   ✅ 6 fonctions créées\n\n";

        // Communes (quelques exemples)
        echo "🏙️  Création des communes...\n";
        DB::table('communes')->insert([
            ['nom_commune' => 'Bruxelles', 'numero_commune' => 1000, 'created_at' => now()],
            ['nom_commune' => 'Ixelles', 'numero_commune' => 1050, 'created_at' => now()],
            ['nom_commune' => 'Etterbeek', 'numero_commune' => 1040, 'created_at' => now()],
            ['nom_commune' => 'Schaerbeek', 'numero_commune' => 1030, 'created_at' => now()],
            ['nom_commune' => 'Anderlecht', 'numero_commune' => 1070, 'created_at' => now()],
            ['nom_commune' => 'Molenbeek', 'numero_commune' => 1080, 'created_at' => now()],
            ['nom_commune' => 'Paris', 'numero_commune' => 75000, 'created_at' => now()],
            ['nom_commune' => 'Lyon', 'numero_commune' => 69000, 'created_at' => now()],
            ['nom_commune' => 'Marseille', 'numero_commune' => 13000, 'created_at' => now()],
        ]);
        echo "   ✅ 9 communes créées\n\n";

        // Statuts de commande
        echo "📊 Création des statuts de commande...\n";
        DB::table('statuts')->insert([
            ['statut' => 'En attente', 'created_at' => now()],
            ['statut' => 'Confirmée', 'created_at' => now()],
            ['statut' => 'Livrée', 'created_at' => now()],
            ['statut' => 'Retournée', 'created_at' => now()],
            ['statut' => 'Annulée', 'created_at' => now()],
        ]);
        echo "   ✅ 5 statuts créés\n\n";

        // Modes de livraison
        echo "🚚 Création des modes de livraison...\n";
        DB::table('mode_livraison')->insert([
            ['livraison' => 'Sur place', 'created_at' => now()],
            ['livraison' => 'Livraison', 'created_at' => now()],
        ]);
        echo "   ✅ 2 modes de livraison créés\n\n";

        // Modes de retour
        echo "↩️  Création des modes de retour...\n";
        DB::table('mode_retour')->insert([
            ['retour' => 'Sur place', 'created_at' => now()],
            ['retour' => 'Récupération', 'created_at' => now()],
        ]);
        echo "   ✅ 2 modes de retour créés\n\n";

        // Types de réduction
        echo "🎟️  Création des types de réduction...\n";
        DB::table('type_reduction')->insert([
            ['reduction' => 'Fixe', 'created_at' => now()],
            ['reduction' => 'Pourcentage', 'created_at' => now()],
        ]);
        echo "   ✅ 2 types de réduction créés\n\n";

        // Types de document
        echo "📄 Création des types de document...\n";
        DB::table('type_document')->insert([
            ['document' => 'Devis', 'created_at' => now()],
            ['document' => 'Facture', 'created_at' => now()],
        ]);
        echo "   ✅ 2 types de document créés\n\n";

        // Statuts de paiement
        echo "💳 Création des statuts de paiement...\n";
        DB::table('statut_paiement')->insert([
            ['statut' => 'Non payée', 'created_at' => now()],
            ['statut' => 'Payée', 'created_at' => now()],
            ['statut' => 'Partiellement payée', 'created_at' => now()],
        ]);
        echo "   ✅ 3 statuts de paiement créés\n\n";

        echo "═══════════════════════════════════════════════════════\n";
        echo "✅ Base de données initialisée avec succès!\n";
        echo "═══════════════════════════════════════════════════════\n\n";
        echo "📊 Récapitulatif:\n";
        echo "   • 2 types d'utilisateurs\n";
        echo "   • 3 langues\n";
        echo "   • 6 fonctions professionnelles\n";
        echo "   • 9 communes\n";
        echo "   • 5 statuts de commande\n";
        echo "   • 2 modes de livraison\n";
        echo "   • 2 modes de retour\n";
        echo "   • 2 types de réduction\n";
        echo "   • 2 types de document\n";
        echo "   • 3 statuts de paiement\n\n";
        echo "🎉 Vous pouvez maintenant tester l'inscription!\n\n";
    }
}