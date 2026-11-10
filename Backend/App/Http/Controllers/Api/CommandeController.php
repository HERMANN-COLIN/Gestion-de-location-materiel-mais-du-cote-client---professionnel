<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Materiel;
use App\Models\Adresse;
use App\Models\Particulier;
use App\Models\Professionnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CommandeController extends Controller
{
    /**
     * Liste des commandes de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $commandes = Commande::with([
                'materiels.photos',
                'materiels.categorie',
            ])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $commandes->map(function ($commande) {
                $materiels = $commande->materiels->map(function ($materiel) {
                    $photo = null;
                    if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                        $rawUrl = $materiel->photos->first()->url_photo
                               ?? $materiel->photos->first()->chemin_fichier
                               ?? null;
                        $photo = $this->formatImageUrl($rawUrl);
                    }

                    return [
                        'id'        => $materiel->id,
                        'nom'       => $materiel->nom,
                        'photo'     => $photo,
                        'categorie' => $materiel->categorie?->nom ?? '',
                        'taux_tva'  => floatval($materiel->taux_tva ?? 20),
                        'pivot' => [
                            'quantite'          => $materiel->pivot->quantite          ?? 1,
                            'prix_unitaire_ht'  => $materiel->pivot->prix_unitaire_ht  ?? 0,
                            'prix_unitaire_ttc' => $materiel->pivot->prix_unitaire_ttc ?? 0,
                            'taux_tva'          => $materiel->pivot->taux_tva          ?? 20,
                            'sous_total_ht'     => $materiel->pivot->sous_total_ht     ?? 0,
                            'sous_total_ttc'    => $materiel->pivot->sous_total_ttc    ?? 0,
                            'montant_tva'       => $materiel->pivot->montant_tva       ?? 0,
                        ],
                    ];
                });

                return [
                    'id'                => $commande->id,
                    'numero_commande'   => $commande->numero_commande,
                    'date_commande'     => $commande->date_commande,
                    'date_debut'        => $commande->date_debut,
                    'date_fin'          => $commande->date_fin,
                    'statut'            => (int) $commande->statut,
                    'statut_libelle'    => $this->getStatutLibelle($commande->statut),
                    'mode_livraison'    => $commande->mode_livraison,
                    'mode_retour'       => $commande->mode_retour,
                    'frais_livraison'   => floatval($commande->frais_livraison ?? 0),
                    'frais_retour'      => floatval($commande->frais_retour    ?? 0),
                    'montant_total'     => floatval($commande->montant_total),
                    'notes'             => $commande->notes,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'materiels'         => $materiels,
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur index commandes', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des commandes',
                'error'   => $this->detailErreur($e)
            ], 500);
        }
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $user->load('type');

            if (!$user->type) {
                return response()->json(['success' => false, 'message' => 'Type utilisateur introuvable'], 400);
            }

            $validator = Validator::make($request->all(), [
                'date_debut'           => 'required|date|after_or_equal:' . now()->format('Y-m-d'),
                'date_fin'             => 'required|date|after:date_debut',
                'mode_livraison'       => 'required|integer|exists:modes_livraison,id',
                'mode_retour'          => 'required|integer|exists:modes_retour,id',
                'adresse_livraison_id' => 'nullable|exists:adresses,id',
                'frais_livraison'      => 'required|numeric|min:0',
                'frais_retour'         => 'nullable|numeric|min:0',
                'jour_livraison'       => 'required_if:mode_livraison,2|nullable|string',
                'jour_retour'          => 'required_if:mode_retour,2|nullable|string',
                'distance_livraison'   => 'required_if:mode_livraison,2|nullable|numeric|min:0|max:50',
                'distance_retour'      => 'required_if:mode_retour,2|nullable|numeric|min:0|max:50',
                'notes'                => 'nullable|string',
                'items'                => 'required|array|min:1',
                'items.*.materiel_id'  => 'required|exists:materiels,id',
                'items.*.quantite'     => 'required|integer|min:1',
                'items.*.prix_unitaire'=> 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Erreur de validation', 'errors' => $validator->errors()], 422);
            }

            $adresseLivraisonId    = null;
            $adresseLivraisonTexte = null;

            if ($request->mode_livraison == 2) {
                if ($user->type->type === 'particulier') {
                    $particulier = Particulier::with('adresseLivraison.commune')->where('user_id', $user->id)->first();
                    if (!$particulier || !$particulier->adresseLivraison) {
                        return response()->json(['success' => false, 'message' => 'Adresse de livraison manquante'], 422);
                    }
                    $adresseLivraisonTexte = $this->formatAdressePourAffichage($particulier->adresseLivraison);
                } else {
                    if (!$request->adresse_livraison_id) {
                        return response()->json(['success' => false, 'message' => 'Adresse de livraison requise pour un professionnel'], 422);
                    }
                    $adresse = Adresse::with('commune')->find($request->adresse_livraison_id);
                    if (!$adresse) {
                        return response()->json(['success' => false, 'message' => 'Adresse de livraison invalide'], 422);
                    }
                    $adresseLivraisonId    = $adresse->id;
                    $adresseLivraisonTexte = $this->formatAdressePourAffichage($adresse);
                }
            }

            DB::beginTransaction();

            $dateDebut   = Carbon::parse($request->date_debut);
            $dateFin     = Carbon::parse($request->date_fin);
            $nombreJours = max(1, (int) $dateDebut->diffInDays($dateFin) + 1);

            $montantTotal = 0;
            $itemsData    = [];

            foreach ($request->items as $item) {
                $materiel = Materiel::find($item['materiel_id']);

                if (!$materiel) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Matériel non trouvé'], 422);
                }

                if ($materiel->stock_disponible < $item['quantite']) {
                    DB::rollBack();
                    return response()->json([
                        'success'  => false,
                        'message'  => "Stock insuffisant pour {$materiel->nom}",
                        'materiel' => ['id' => $materiel->id, 'nom' => $materiel->nom, 'stock_disponible' => $materiel->stock_disponible]
                    ], 422);
                }

                $prixHT  = $item['prix_unitaire'];
                $tauxTVA = floatval($materiel->taux_tva ?? 21);
                $prixTTC = $prixHT * (1 + $tauxTVA / 100);
                $qty     = $item['quantite'];

                $itemsData[] = [
                    'materiel_id'       => $item['materiel_id'],
                    'quantite'          => $qty,
                    'prix_unitaire_ht'  => $prixHT,
                    'taux_tva'          => $tauxTVA,
                    'prix_unitaire_ttc' => $prixTTC,
                    'sous_total_ht'     => $prixHT  * $qty * $nombreJours,
                    'sous_total_ttc'    => $prixTTC * $qty * $nombreJours,
                    'montant_tva'       => ($prixTTC - $prixHT) * $qty * $nombreJours,
                    'materiel'          => $materiel,
                ];

                $montantTotal += $prixTTC * $qty * $nombreJours;
            }

            $fraisLivraison = $request->frais_livraison ?? 0;
            $fraisRetour    = $request->frais_retour ?? 0;
            $montant_final  = max(0, $montantTotal + $fraisLivraison + $fraisRetour);

            $commande = Commande::create([
                'user_id'              => $user->id,
                'numero_commande'      => $this->genererNumeroCommande(),
                'date_commande'        => now(),
                'date_debut'           => $request->date_debut,
                'date_fin'             => $request->date_fin,
                'statut'               => 1, // ✅ En attente
                'mode_livraison'       => $request->mode_livraison,
                'mode_retour'          => $request->mode_retour,
                'montant_total'        => $montant_final,
                'frais_livraison'      => $fraisLivraison,
                'frais_retour'         => $fraisRetour,
                'notes'                => $request->notes,
                'adresse_livraison_id' => $adresseLivraisonId,
                'adresse_livraison'    => $adresseLivraisonTexte,
                'jour_livraison'       => $request->jour_livraison,
                'distance_livraison'   => $request->distance_livraison,
                'jour_retour'          => $request->jour_retour,
                'distance_retour'      => $request->distance_retour,
            ]);

            foreach ($itemsData as $itemData) {
                $commande->materiels()->attach($itemData['materiel_id'], [
                    'quantite'          => $itemData['quantite'],
                    'prix_unitaire_ht'  => $itemData['prix_unitaire_ht'],
                    'taux_tva'          => $itemData['taux_tva'],
                    'prix_unitaire_ttc' => $itemData['prix_unitaire_ttc'],
                    'sous_total_ht'     => $itemData['sous_total_ht'],
                    'sous_total_ttc'    => $itemData['sous_total_ttc'],
                    'montant_tva'       => $itemData['montant_tva'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                $itemData['materiel']->decrement('stock_disponible', $itemData['quantite']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => [
                    'id'              => $commande->id,
                    'numero_commande' => $commande->numero_commande,
                    'date_commande'   => $commande->date_commande,
                    'montant_total'   => $commande->montant_total,
                    'statut'          => $commande->statut,
                ]
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur création commande', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error'   => $this->detailErreur($e)
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique
     */
    public function show($id, Request $request)
    {
        try {
            $commande = Commande::with(['materiels.photos', 'materiels.categorie', 'user.type'])
                ->find($id);

            if (!$commande) {
                return response()->json(['success' => false, 'message' => 'Commande non trouvée'], 404);
            }

            if (!$this->peutAcceder($request->user(), $commande)) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            $dateDebut   = Carbon::parse($commande->date_debut);
            $dateFin     = Carbon::parse($commande->date_fin);
            $nombreJours = max(1, (int) $dateDebut->diffInDays($dateFin) + 1);

            $sousTotalHT  = 0;
            $totalTVA     = 0;
            $sousTotalTTC = 0;
            $articles     = [];

            foreach ($commande->materiels as $materiel) {
                $pivot = $materiel->pivot;
                $sousTotalHT  += floatval($pivot->sous_total_ht  ?? 0);
                $totalTVA     += floatval($pivot->montant_tva    ?? 0);
                $sousTotalTTC += floatval($pivot->sous_total_ttc ?? 0);

                if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                    $rawUrl = $materiel->photos->first()->url_photo
                           ?? $materiel->photos->first()->chemin_fichier
                           ?? null;
                    $photo = $this->formatImageUrl($rawUrl);
                } else {
                    $photo = '/placeholder.jpg';
                }

                $articles[] = [
                    'id'               => $materiel->id,
                    'nom'              => $materiel->nom,
                    'photo'            => $photo,
                    'quantite'         => $pivot->quantite         ?? 1,
                    'nombre_jours'     => $nombreJours,
                    'prix_unitaire_ht' => $pivot->prix_unitaire_ht ?? 0,
                    'taux_tva'         => $pivot->taux_tva         ?? 20,
                    'sous_total_ht'    => $pivot->sous_total_ht    ?? 0,
                    'montant_tva'      => $pivot->montant_tva      ?? 0,
                    'sous_total_ttc'   => $pivot->sous_total_ttc   ?? 0,
                ];
            }

            $commandeData = [
                'id'                     => $commande->id,
                'numero_commande'        => $commande->numero_commande,
                'date_commande'          => $commande->date_commande ? Carbon::parse($commande->date_commande)->format('d/m/Y') : null,
                'date_debut'             => $commande->date_debut    ? Carbon::parse($commande->date_debut)->format('d/m/Y') : null,
                'date_fin'               => $commande->date_fin      ? Carbon::parse($commande->date_fin)->format('d/m/Y') : null,
                'nombre_jours'           => $nombreJours,
                'duree'                  => $nombreJours . ' jour' . ($nombreJours > 1 ? 's' : ''),
                'statut'                 => (int) $commande->statut,
                'statut_libelle'         => $this->getStatutLibelle($commande->statut),
                'mode_livraison'         => $commande->mode_livraison,
                'mode_livraison_libelle' => $commande->mode_livraison == 1 ? 'Retrait sur place' : 'Livraison',
                'mode_retour'            => $commande->mode_retour,
                'mode_retour_libelle'    => $commande->mode_retour == 1 ? 'Retour sur place' : 'Récupération',
                'adresse_livraison'      => $commande->adresse_livraison,
                'frais_livraison'        => floatval($commande->frais_livraison ?? 0),
                'frais_retour'           => floatval($commande->frais_retour    ?? 0),
                'details_livraison'      => [
                    'jour'     => $commande->jour_livraison,
                    'distance' => $commande->distance_livraison,
                ],
                'details_retour'         => [
                    'jour'     => $commande->jour_retour,
                    'distance' => $commande->distance_retour,
                ],
                'notes'                  => $commande->notes,
                'montant_total'          => floatval($commande->montant_total),
                'code_reduction'         => null,
                'articles'               => $articles,
                'totaux' => [
                    'sous_total_ht'  => $sousTotalHT,
                    'total_tva'      => $totalTVA,
                    'sous_total_ttc' => $sousTotalTTC,
                    'remise'         => 0,
                ],
                'client' => $this->getClientInfo($commande->user),
            ];

            return response()->json(['success' => true, 'data' => $commandeData]);

        } catch (\Throwable $e) {
            Log::error('Erreur show commande', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur chargement commande',
                'error'   => $this->detailErreur($e)
            ], 500);
        }
    }

    /**
     * Ancienne route : même résultat que show()
     */
    public function getCommande($id)
    {
        return $this->show($id, request());
    }

    /**
     * Récupérer les adresses de livraison pour les professionnels
     */
    public function getAdressesLivraison(Request $request)
    {
        try {
            $user = $request->user();
            $user->load('type');

            if (!$user->type || $user->type->type !== 'professionnel') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès réservé aux professionnels'
                ], 403);
            }

            $professionnel = Professionnel::with([
                'adresseLivraison.commune',
                'adresseSiege.commune'
            ])->where('user_id', $user->id)->first();

            if (!$professionnel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil professionnel non trouvé'
                ], 404);
            }

            $adresses = [];

            if ($professionnel->adresseLivraison && $professionnel->adresseLivraison->commune) {
                $adresseFormatee = $this->formatAdressePourAffichage($professionnel->adresseLivraison);

                $adresses[] = [
                    'id'               => $professionnel->adresseLivraison->id,
                    'nom_societe'      => $professionnel->nom_societe,
                    'adresse'          => $adresseFormatee,
                    'adresse_complete' => $professionnel->nom_societe . ', ' . $adresseFormatee,
                    'est_principale'   => true,
                    'type'             => 'Livraison par défaut'
                ];
            }

            if ($professionnel->adresseSiege &&
                $professionnel->adresseSiege->commune &&
                (!$professionnel->adresseLivraison ||
                 $professionnel->adresseSiege->id !== $professionnel->adresseLivraison->id)) {

                $adresseFormatee = $this->formatAdressePourAffichage($professionnel->adresseSiege);

                $adresses[] = [
                    'id'               => $professionnel->adresseSiege->id,
                    'nom_societe'      => $professionnel->nom_societe . ' (Siège)',
                    'adresse'          => $adresseFormatee,
                    'adresse_complete' => $professionnel->nom_societe . ' (Siège), ' . $adresseFormatee,
                    'est_principale'   => false,
                    'type'             => 'Siège social'
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => $adresses
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur chargement adresses',
                'error'   => $this->detailErreur($e)
            ], 500);
        }
    }

    /**
     * Calculer les frais de livraison
     */
    public function calculerFrais(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'jour'     => 'required|in:Lundi-vendredi,Samedi,dimanche',
                'distance' => 'required|numeric|min:0|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            $tarifs = [
                'Lundi-vendredi' => 1.25,
                'Samedi'         => 1.00,
                'dimanche'       => 1.50
            ];

            $frais = $tarifs[$request->jour] * $request->distance;

            return response()->json([
                'success' => true,
                'data' => [
                    'jour'     => $request->jour,
                    'distance' => $request->distance,
                    'tarif_km' => $tarifs[$request->jour],
                    'frais'    => round($frais, 2)
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de calcul',
                'error'   => $this->detailErreur($e)
            ], 500);
        }
    }

    /**
     * Annuler une commande (propriétaire ou admin uniquement)
     */
    public function annuler($id)
    {
        try {
            $user = request()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $commande = Commande::with('materiels')->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if (!$this->peutAcceder($user, $commande)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // ✅ Seules les commandes En attente (1) ou Confirmée (2) peuvent être annulées
            if (!in_array((int) $commande->statut, [1, 2])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut plus être annulée'
                ], 422);
            }

            DB::beginTransaction();

            foreach ($commande->materiels as $materiel) {
                $materiel->increment('stock_disponible', $materiel->pivot->quantite);
            }

            // ✅ Annulée = 4
            $commande->update(['statut' => 4]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur annulation commande', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
                'error'   => $this->detailErreur($e)
            ], 500);
        }
    }

    /**
     * Facture PDF : déléguée à CommandeDetailController
     */
    public function telechargerFacture($id)
    {
        return app(CommandeDetailController::class)->telechargerFacture($id);
    }

    /**
     * Envoi de la facture par email : délégué à CommandeDetailController
     */
    public function envoyerFactureEmail($id)
    {
        return app(CommandeDetailController::class)->envoyerFactureEmail($id);
    }

    // ==================== MÉTHODES PRIVÉES ====================

    private function peutAcceder($user, $commande): bool
    {
        if (!$user) {
            return false;
        }

        if ((int) $user->id === (int) $commande->user_id) {
            return true;
        }

        return method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    /**
     * Message d'erreur détaillé (fichier:ligne). À simplifier en production.
     */
    private function detailErreur(\Throwable $e): string
    {
        return $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
    }

    /**
     * ✅ 4 statuts
     * 1 = En attente | 2 = Confirmée | 3 = Livrée | 4 = Annulée
     */
    private function getStatutLibelle($statut)
    {
        $labels = [
            1 => 'En attente',
            2 => 'Confirmée',
            3 => 'Livrée',
            4 => 'Annulée',
        ];
        return $labels[(int) $statut] ?? 'Inconnu';
    }

    private function getClientInfo($user)
    {
        if (!$user) {
            return null;
        }

        try {
            $type = $user->type->type ?? 'particulier';

            if ($type === 'professionnel') {
                $pro = Professionnel::where('user_id', $user->id)->first();
                $contact = null;

                if ($pro && method_exists($pro, 'contactPro')) {
                    $contact = $pro->contactPro;
                }

                return [
                    'type'        => 'professionnel',
                    'nom_complet' => $pro->nom_societe ?? $user->email,
                    'telephone'   => $contact->telephone ?? ($pro->telephone ?? null),
                ];
            }

            $particulier = Particulier::where('user_id', $user->id)->first();

            return [
                'type'        => 'particulier',
                'nom_complet' => $particulier
                    ? (trim(($particulier->prenom ?? '') . ' ' . ($particulier->nom ?? '')) ?: $user->email)
                    : $user->email,
                'telephone'   => $particulier->telephone ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('getClientInfo a échoué', ['message' => $e->getMessage()]);

            return [
                'type'        => 'particulier',
                'nom_complet' => $user->email,
            ];
        }
    }

    private function formatAdressePourAffichage($adresse)
    {
        if (!$adresse) return '';

        $rue = trim(($adresse->numero_rue ?? '') . ' ' . ($adresse->nom_rue ?? ''));

        if ($adresse->commune) {
            $cp    = $adresse->commune->code_postal ?? '';
            $ville = $adresse->commune->nom_commune ?? '';

            if ($cp && $ville) {
                return trim($rue . ', ' . $cp . ' ' . $ville);
            } elseif ($ville) {
                return trim($rue . ', ' . $ville);
            }
        }

        return trim($rue);
    }

    private function genererNumeroCommande()
    {
        $prefix = 'CMD';
        $date   = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        $code = $prefix . $date . $random;

        while (Commande::where('numero_commande', $code)->exists()) {
            $random = strtoupper(substr(uniqid(), -4));
            $code   = $prefix . $date . $random;
        }

        return $code;
    }

    private function formatImageUrl($url)
    {
        if (!$url) {
            return '/placeholder.jpg';
        }

        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) {
            return $url;
        }

        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        return asset($url);
    }
}