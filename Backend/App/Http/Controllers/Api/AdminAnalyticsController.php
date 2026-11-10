<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAnalyticsController extends Controller
{
    /**
     * Statistiques dashboard admin
     */
    public function analytics(Request $request)
    {
        try {
            $periode        = (int) $request->get('periode', 30);
            $dateDebut      = now()->subDays($periode)->startOfDay();
            $dateFin        = now()->endOfDay();
            $datePrecedente = now()->subDays($periode * 2)->startOfDay();

            // ============ KPI ============
            // CA = commandes Confirmée (2) + Livrée (3)
            $caActuel = Commande::whereBetween('created_at', [$dateDebut, $dateFin])
                ->whereIn('statut', [2, 3])
                ->sum('montant_total');

            $caPrecedent = Commande::whereBetween('created_at', [$datePrecedente, $dateDebut])
                ->whereIn('statut', [2, 3])
                ->sum('montant_total');

            $evolution = $caPrecedent > 0
                ? round((($caActuel - $caPrecedent) / $caPrecedent) * 100, 1)
                : 0;

            $nbCommandes = Commande::whereBetween('created_at', [$dateDebut, $dateFin])->count();

            $nbClients = User::whereHas('commandes', function ($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('created_at', [$dateDebut, $dateFin]);
            })->count();

            $panierMoyen = $nbCommandes > 0 ? $caActuel / $nbCommandes : 0;

            // ============ Revenue par jour ============
            $revenueByDay = Commande::whereBetween('created_at', [$dateDebut, $dateFin])
                ->whereIn('statut', [2, 3])
                ->selectRaw('DATE(created_at) as date, SUM(montant_total) as montant')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($r) => [
                    'date'    => Carbon::parse($r->date)->format('d/m'),
                    'montant' => (float) $r->montant,
                ]);

            // ============ Répartition par statut (4 statuts) ============
            $parStatut = [
                'en_attente' => Commande::where('statut', 1)->count(),
                'confirmee'  => Commande::where('statut', 2)->count(),
                'livree'     => Commande::where('statut', 3)->count(),
                'annulee'    => Commande::where('statut', 4)->count(),
            ];

            // ============ Top matériels ============
            // ✅ table pivot réelle : commande_materiel + sous_total_ttc
            $topMateriels = DB::table('commande_materiel')
                ->join('materiels', 'commande_materiel.materiel_id', '=', 'materiels.id')
                ->leftJoin('categories_materiel', 'materiels.categorie_id', '=', 'categories_materiel.id')
                ->join('commandes', 'commande_materiel.commande_id', '=', 'commandes.id')
                ->whereBetween('commandes.created_at', [$dateDebut, $dateFin])
                ->whereIn('commandes.statut', [2, 3]) // Confirmée + Livrée
                ->select(
                    'materiels.id',
                    'materiels.nom',
                    'categories_materiel.nom as categorie',
                    DB::raw('SUM(commande_materiel.quantite) as locations'),
                    DB::raw('SUM(commande_materiel.sous_total_ttc) as revenus')
                )
                ->groupBy('materiels.id', 'materiels.nom', 'categories_materiel.nom')
                ->orderByDesc('locations')
                ->limit(5)
                ->get()
                ->map(fn ($m) => [
                    'id'        => $m->id,
                    'nom'       => $m->nom,
                    'categorie' => $m->categorie ?? 'Non classé',
                    'locations' => (int) $m->locations,
                    'revenus'   => (float) $m->revenus,
                ]);

            // ============ Par catégorie ============
            $parCategorie = DB::table('commande_materiel')
                ->join('materiels', 'commande_materiel.materiel_id', '=', 'materiels.id')
                ->leftJoin('categories_materiel', 'materiels.categorie_id', '=', 'categories_materiel.id')
                ->join('commandes', 'commande_materiel.commande_id', '=', 'commandes.id')
                ->whereBetween('commandes.created_at', [$dateDebut, $dateFin])
                ->whereIn('commandes.statut', [2, 3])
                ->select(
                    DB::raw("COALESCE(categories_materiel.nom, 'Non classé') as nom"),
                    DB::raw('SUM(commande_materiel.quantite) as total')
                )
                ->groupBy('nom')
                ->get()
                ->map(fn ($c) => [
                    'nom'   => $c->nom,
                    'total' => (int) $c->total,
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'periode' => $periode,
                    'kpi' => [
                        'chiffre_affaires' => round((float) $caActuel, 2),
                        'évolution'        => $evolution,
                        'commandes'        => $nbCommandes,
                        'clients'          => $nbClients,
                        'panier_moyen'     => round((float) $panierMoyen, 2),
                    ],
                    'revenue_by_day' => $revenueByDay,
                    'par_statut'     => $parStatut,
                    'top_materiels'  => $topMateriels,
                    'par_categorie'  => $parCategorie,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur analytics',
                'error'   => $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
            ], 500);
        }
    }
}