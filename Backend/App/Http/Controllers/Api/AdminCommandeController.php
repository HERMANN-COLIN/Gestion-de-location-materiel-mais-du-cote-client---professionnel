<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminCommandeController extends Controller
{
    /**
     * Liste toutes les commandes avec filtres et pagination
     */
    public function index(Request $request)
    {
        try {
            $query = Commande::with(['user.type', 'materiels.photos', 'materiels.categorie']);

            // Filtre par statut
            if ($request->filled('statut_id')) {
                $query->where('statut', $request->statut_id);
            }

            // Filtre par recherche (numéro ou email)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('numero_commande', 'like', "%{$search}%")
                      ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"));
                });
            }

            $query->orderBy('created_at', 'desc');
            $commandes = $query->paginate($request->get('per_page', 10));

            // Transformation pour le frontend
            $data = $commandes->getCollection()->map(function ($commande) {
                return [
                    'id'                => $commande->id,
                    'numero_commande'   => $commande->numero_commande,
                    'date_commande'     => $commande->date_commande,
                    'created_at'        => $commande->created_at,
                    'date_debut'        => $commande->date_debut,
                    'date_fin'          => $commande->date_fin,
                    'statut_id'         => (int) $commande->statut,
                    'statut_libelle'    => $this->getStatutLibelle($commande->statut),
                    'montant_total'     => floatval($commande->montant_total),
                    'frais_livraison'   => floatval($commande->frais_livraison ?? 0),
                    'frais_retour'      => floatval($commande->frais_retour ?? 0),
                    'mode_livraison'    => $commande->mode_livraison,
                    'mode_retour'       => $commande->mode_retour,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'notes'             => $commande->notes,
                    'user' => [
                        'id'    => $commande->user?->id,
                        'email' => $commande->user?->email,
                        'type'  => ['type' => $commande->user?->type?->type ?? 'particulier'],
                    ],
                    'details' => $commande->materiels->map(fn ($m) => [
                        'id'         => $m->id,
                        'quantite'   => $m->pivot->quantite ?? 1,
                        'sous_total' => floatval($m->pivot->sous_total_ttc ?? 0),
                        'materiel'   => [
                            'id'    => $m->id,
                            'nom'   => $m->nom,
                            'photo' => optional($m->photos->first())->url_photo,
                        ],
                    ]),
                ];
            });

            // Comptage par statut (4 statuts)
            $counts = [
                'all' => Commande::count(),
                '1'   => Commande::where('statut', 1)->count(), // En attente
                '2'   => Commande::where('statut', 2)->count(), // Confirmée
                '3'   => Commande::where('statut', 3)->count(), // Livrée
                '4'   => Commande::where('statut', 4)->count(), // Annulée
            ];

            return response()->json([
                'success'      => true,
                'data'         => $data,
                'current_page' => $commandes->currentPage(),
                'last_page'    => $commandes->lastPage(),
                'total'        => $commandes->total(),
                'counts'       => $counts,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des commandes',
                'error'   => $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique
     */
    public function show($id)
    {
        try {
            // ✅ relation corrigée : 'materiels' (pivot) au lieu de 'details'
            $commande = Commande::with(['user.type', 'materiels.photos', 'materiels.categorie'])
                ->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            // Calcul durée
            $nombreJours = 1;
            if ($commande->date_debut && $commande->date_fin) {
                $dateDebut   = Carbon::parse($commande->date_debut);
                $dateFin     = Carbon::parse($commande->date_fin);
                $nombreJours = max(1, (int) $dateDebut->diffInDays($dateFin) + 1);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'                => $commande->id,
                    'numero_commande'   => $commande->numero_commande,
                    'date_commande'     => $commande->date_commande,
                    'created_at'        => $commande->created_at,
                    'date_debut'        => $commande->date_debut,
                    'date_fin'          => $commande->date_fin,
                    'nombre_jours'      => $nombreJours,
                    'statut_id'         => (int) $commande->statut,
                    'statut_libelle'    => $this->getStatutLibelle($commande->statut),
                    'montant_total'     => floatval($commande->montant_total),
                    'frais_livraison'   => floatval($commande->frais_livraison ?? 0),
                    'frais_retour'      => floatval($commande->frais_retour ?? 0),
                    'mode_livraison'    => $commande->mode_livraison,
                    'mode_retour'       => $commande->mode_retour,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'jour_livraison'    => $commande->jour_livraison,
                    'distance_livraison'=> $commande->distance_livraison,
                    'jour_retour'       => $commande->jour_retour,
                    'distance_retour'   => $commande->distance_retour,
                    'notes'             => $commande->notes,
                    'user' => [
                        'id'    => $commande->user?->id,
                        'email' => $commande->user?->email,
                        'type'  => ['type' => $commande->user?->type?->type ?? 'particulier'],
                    ],
                    'details' => $commande->materiels->map(fn ($m) => [
                        'id'         => $m->id,
                        'quantite'   => $m->pivot->quantite ?? 1,
                        'sous_total' => floatval($m->pivot->sous_total_ttc ?? 0),
                        'materiel'   => [
                            'id'    => $m->id,
                            'nom'   => $m->nom,
                            'photo' => optional($m->photos->first())->url_photo,
                        ],
                    ]),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la commande',
                'error'   => $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateStatut(Request $request, $id)
    {
        try {
            $commande = Commande::find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                // 1=En attente, 2=Confirmée, 3=Livrée, 4=Annulée
                'statut_id' => 'required|integer|in:1,2,3,4',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Statut invalide',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // ✅ Correctif : la colonne en base est 'statut', pas 'statut_id'
            $commande->statut = $request->statut_id;
            $commande->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour',
                'data'    => [
                    'id'             => $commande->id,
                    'statut_id'      => (int) $commande->statut,
                    'statut_libelle' => $this->getStatutLibelle($commande->statut),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut',
                'error'   => $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')',
            ], 500);
        }
    }

    // ==================== PRIVÉ ====================

    private function getStatutLibelle($statut): string
    {
        return [
            1 => 'En attente',
            2 => 'Confirmée',
            3 => 'Livrée',
            4 => 'Annulée',
        ][(int) $statut] ?? 'Inconnu';
    }
}