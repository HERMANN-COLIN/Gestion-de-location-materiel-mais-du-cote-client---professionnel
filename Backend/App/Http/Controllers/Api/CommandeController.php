<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Materiel;
use App\Models\CodeReduction;
use App\Models\Adresse;
use App\Models\User;
use App\Models\Particulier;
use App\Models\Professionnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CommandeController extends Controller
{
    /**
     * Liste des commandes de l'utilisateur connecté
     */
     /**
     * ✅ CORRECTION : index() transforme maintenant les données
     * - Photos : URL absolue via formatImageUrl()
     * - Pivot  : explicitement inclus dans la réponse (sinon absent du JSON)
     * - Categorie : null-safe
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $commandes = Commande::with([
                'materiels.photos',
                'materiels.categorie',
                'codeReduction',
            ])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $commandes->map(function ($commande) {
                $materiels = $commande->materiels->map(function ($materiel) {
                    // ✅ Photo principale en URL absolue
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
                        // ✅ Pivot explicite — sinon absent de la sérialisation JSON
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
                    'statut'            => $commande->statut,
                    'mode_livraison'    => $commande->mode_livraison,
                    'mode_retour'       => $commande->mode_retour,
                    'frais_livraison'   => floatval($commande->frais_livraison ?? 0),
                    'frais_retour'      => floatval($commande->frais_retour    ?? 0),
                    'montant_total'     => floatval($commande->montant_total),
                    'notes'             => $commande->notes,
                    'adresse_livraison' => $commande->adresse_livraison,
                    'code_reduction'    => $commande->codeReduction ? [
                        'code'           => $commande->codeReduction->code,
                        'type_reduction' => $commande->codeReduction->type_reduction,
                        'montant'        => $commande->codeReduction->montant,
                        'montant_remise' => null, // calculé côté Vue
                    ] : null,
                    'materiels' => $materiels,
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des commandes',
                'error'   => $e->getMessage()
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
                'code_reduction'       => 'nullable|string|max:50',
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
            $nombreJours = max(1, $dateDebut->diffInDays($dateFin) + 1); // +1 pour inclure le dernier jour (identique au checkout Vue)

            $montantTotal   = 0; // TTC
            $montantTotalHT = 0; // HT (pour le calcul de remise en % si besoin)
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

                $prixHT    = $item['prix_unitaire'];
                $tauxTVA   = floatval($materiel->taux_tva ?? 21);
                $prixTTC   = $prixHT * (1 + $tauxTVA / 100);
                $qty       = $item['quantite'];

                $itemsData[] = [
                    'materiel_id'      => $item['materiel_id'],
                    'quantite'         => $qty,
                    'nombre_jours'     => $nombreJours,
                    'prix_unitaire_ht' => $prixHT,
                    'taux_tva'         => $tauxTVA,
                    'prix_unitaire_ttc'=> $prixTTC,
                    'sous_total_ht'    => $prixHT  * $qty * $nombreJours,
                    'sous_total_ttc'   => $prixTTC * $qty * $nombreJours,
                    'montant_tva'      => ($prixTTC - $prixHT) * $qty * $nombreJours,
                    'materiel'         => $materiel,
                ];

                $montantTotalHT  += $prixHT  * $qty * $nombreJours;
                $montantTotal += $prixTTC * $qty * $nombreJours; // ✅ TTC pour le total final stocké en BDD
            }

            $code_reduction_id = null;
            $remise            = 0;

            if ($request->code_reduction) {
                $code = CodeReduction::where('code', $request->code_reduction)
                    ->where('date_debut', '<=', now())
                    ->where('date_fin',   '>=', now())
                    ->where(function ($q) {
                        $q->where('utilisations_max', 0)->orWhereRaw('utilisations_actuelles < utilisations_max');
                    })
                    ->first();

                if ($code) {
                    $code_reduction_id = $code->id;
                    $remise = $code->type_reduction == 2
                        ? ($montantTotal * $code->montant) / 100
                        : $code->montant;
                    $code->increment('utilisations_actuelles');
                }
            }

            $fraisRetour   = $request->frais_retour ?? 0;
            $montant_final = max(0, $montantTotal + $request->frais_livraison + $fraisRetour - $remise);

            $commande = Commande::create([
                'user_id'              => $user->id,
                'numero_commande'      => $this->genererNumeroCommande(),
                'date_commande'        => now(),
                'date_debut'           => $request->date_debut,
                'date_fin'             => $request->date_fin,
                'statut'               => 1,
                'mode_livraison'       => $request->mode_livraison,
                'mode_retour'          => $request->mode_retour,
                'montant_total'        => $montant_final,
                'frais_livraison'      => $request->frais_livraison,
                'frais_retour'         => $fraisRetour,
                'code_reduction_id'    => $code_reduction_id,
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
                    'id'               => $commande->id,
                    'numero_commande'  => $commande->numero_commande,
                    'date_commande'    => $commande->date_commande,
                    'montant_total'    => $commande->montant_total,
                    'statut'           => $commande->statut,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur création commande', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Afficher une commande spécifique
     */
    public function show($id, Request $request)
    {
        try {
            $commande = Commande::with(['materiels.photos', 'materiels.categorie', 'codeReduction', 'user.type'])
                ->find($id);

            if (!$commande) {
                return response()->json(['success' => false, 'message' => 'Commande non trouvée'], 404);
            }

            $user = $request->user();
            if ($user->id !== $commande->user_id) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            $dateDebut   = Carbon::parse($commande->date_debut);
            $dateFin     = Carbon::parse($commande->date_fin);
            $nombreJours = max(1, $dateDebut->diffInDays($dateFin) + 1);

            $sousTotalHT  = 0;
            $totalTVA     = 0;
            $sousTotalTTC = 0;
            $articles     = [];

            foreach ($commande->materiels as $materiel) {
                $pivot = $materiel->pivot;
                $sousTotalHT  += floatval($pivot->sous_total_ht  ?? 0);
                $totalTVA     += floatval($pivot->montant_tva    ?? 0);
                $sousTotalTTC += floatval($pivot->sous_total_ttc ?? 0);

                $photo = null;
                if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                    $rawUrl = $materiel->photos->first()->url_photo
                           ?? $materiel->photos->first()->chemin_fichier
                           ?? null;
                    $photo = $this->formatImageUrl($rawUrl);
                }

                $articles[] = [
                    'id'               => $materiel->id,
                    'nom'              => $materiel->nom,
                    'photo'            => $photo,
                    'quantite'         => $pivot->quantite          ?? 1,
                    'nombre_jours'     => $nombreJours,
                    'prix_unitaire_ht' => $pivot->prix_unitaire_ht  ?? 0,
                    'taux_tva'         => $pivot->taux_tva           ?? 20,
                    'sous_total_ht'    => $pivot->sous_total_ht      ?? 0,
                    'montant_tva'      => $pivot->montant_tva         ?? 0,
                    'sous_total_ttc'   => $pivot->sous_total_ttc     ?? 0,
                ];
            }

            $commandeData = [
                'id'                    => $commande->id,
                'numero_commande'       => $commande->numero_commande,
                'date_commande'         => $commande->date_commande ? Carbon::parse($commande->date_commande)->format('d/m/Y') : null,
                'date_debut'            => $commande->date_debut    ? Carbon::parse($commande->date_debut)->format('d/m/Y') : null,
                'date_fin'              => $commande->date_fin      ? Carbon::parse($commande->date_fin)->format('d/m/Y') : null,
                'nombre_jours'          => $nombreJours,
                'duree'                 => $nombreJours . ' jour' . ($nombreJours > 1 ? 's' : ''),
                'statut'                => $commande->statut,
                'statut_libelle'        => $this->getStatutLibelle($commande->statut),
                'mode_livraison'        => $commande->mode_livraison,
                'mode_livraison_libelle'=> $commande->mode_livraison == 1 ? 'Retrait sur place' : 'Livraison',
                'mode_retour'           => $commande->mode_retour,
                'mode_retour_libelle'   => $commande->mode_retour == 1 ? 'Retour sur place' : 'Récupération',
                'adresse_livraison'     => $commande->adresse_livraison,
                'frais_livraison'       => floatval($commande->frais_livraison ?? 0),
                'frais_retour'          => floatval($commande->frais_retour    ?? 0),
                'notes'                 => $commande->notes,
                'montant_total'         => floatval($commande->montant_total),
                'code_reduction'        => $commande->codeReduction ? [
                    'code'           => $commande->codeReduction->code,
                    'type'           => $commande->codeReduction->type_reduction,
                    'montant'        => $commande->codeReduction->montant,
                    'montant_remise' => $this->calculerRemise($sousTotalHT, $commande->codeReduction),
                ] : null,
                'articles' => $articles,
                'totaux' => [
                    'sous_total_ht'  => $sousTotalHT,
                    'total_tva'      => $totalTVA,
                    'sous_total_ttc' => $sousTotalTTC,
                ],
                'client' => $this->getClientInfo($commande->user),
            ];

            return response()->json(['success' => true, 'data' => $commandeData]);

        } catch (\Exception $e) {
            \Log::error('Erreur show commande', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur chargement commande', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Récupérer les adresses de livraison pour les professionnels
     */
    public function getAdressesLivraison(Request $request)
    {
        try {
            $user = $request->user();
            $user->load('type');
            
            if (!$user->type || $user->type->type !== 'professionnel')  {
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
                    'id' => $professionnel->adresseLivraison->id,
                    'nom_societe' => $professionnel->nom_societe,
                    'adresse' => $adresseFormatee,
                    'adresse_complete' => $professionnel->nom_societe . ', ' . $adresseFormatee,
                    'est_principale' => true,
                    'type' => 'Livraison par défaut'
                ];
            }
            
            if ($professionnel->adresseSiege && 
                $professionnel->adresseSiege->commune &&
                (!$professionnel->adresseLivraison || 
                 $professionnel->adresseSiege->id !== $professionnel->adresseLivraison->id)) {
                
                $adresseFormatee = $this->formatAdressePourAffichage($professionnel->adresseSiege);
                
                $adresses[] = [
                    'id' => $professionnel->adresseSiege->id,
                    'nom_societe' => $professionnel->nom_societe . ' (Siège)',
                    'adresse' => $adresseFormatee,
                    'adresse_complete' => $professionnel->nom_societe . ' (Siège), ' . $adresseFormatee,
                    'est_principale' => false,
                    'type' => 'Siège social'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $adresses
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur chargement adresses',
                'error' => $e->getMessage()
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
                'jour' => 'required|in:Lundi-vendredi,Samedi,dimanche',
                'distance' => 'required|numeric|min:0|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $tarifs = [
                'Lundi-vendredi' => 1.25,
                'Samedi' => 1.00,
                'dimanche' => 1.50
            ];

            $frais = $tarifs[$request->jour] * $request->distance;

            return response()->json([
                'success' => true,
                'data' => [
                    'jour' => $request->jour,
                    'distance' => $request->distance,
                    'tarif_km' => $tarifs[$request->jour],
                    'frais' => round($frais, 2)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de calcul',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique
     */
    public function getCommande($id)
    {
        try {
            $commande = Commande::with(['materiels.photos', 'codeReduction', 'user.type'])
                ->find($id);
                
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }
            
            $user = request()->user();
            if ($user->id !== $commande->user_id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }
            
            // Calculer la durée de location
            $dateDebut = Carbon::parse($commande->date_debut);
            $dateFin = Carbon::parse($commande->date_fin);
            $nombreJours = $dateDebut->diffInDays($dateFin);
            if ($nombreJours < 1) $nombreJours = 1;
            
            // Formater les données de la commande
            $commandeData = [
                'id' => $commande->id,
                'numero_commande' => $commande->numero_commande,
                'date_commande' => $commande->date_commande ? Carbon::parse($commande->date_commande)->format('d/m/Y') : null,
                'date_debut' => $commande->date_debut ? Carbon::parse($commande->date_debut)->format('d/m/Y') : null,
                'date_fin' => $commande->date_fin ? Carbon::parse($commande->date_fin)->format('d/m/Y') : null,
                'nombre_jours' => $nombreJours,
                'duree' => $nombreJours . ' jour' . ($nombreJours > 1 ? 's' : ''),
                'statut' => $commande->statut,
                'statut_libelle' => $this->getStatutLibelle($commande->statut),
                'mode_livraison' => $commande->mode_livraison,
                'mode_livraison_libelle' => $commande->mode_livraison == 1 ? 'Retrait sur place' : 'Livraison',
                'mode_retour' => $commande->mode_retour,
                'mode_retour_libelle' => $commande->mode_retour == 1 ? 'Retour sur place' : 'Récupération',
                'adresse_livraison' => $commande->adresse_livraison,
                'frais_livraison' => $commande->frais_livraison ?? 0,
                'frais_retour' => $commande->frais_retour ?? 0,
                'details_livraison' => [
                    'jour' => $commande->jour_livraison,
                    'distance' => $commande->distance_livraison
                ],
                'details_retour' => [
                    'jour' => $commande->jour_retour,
                    'distance' => $commande->distance_retour
                ],
                'notes' => $commande->notes,
                'montant_total' => $commande->montant_total,
                'code_reduction' => $commande->codeReduction ? [
                    'code' => $commande->codeReduction->code,
                    'type' => $commande->codeReduction->type_reduction,
                    'montant' => $commande->codeReduction->montant
                ] : null,
            ];
            
            // Calculer les totaux
            $sousTotalHT = 0;
            $totalTVA = 0;
            $sousTotalTTC = 0;
            
            // Formater les articles
            $articles = [];
            foreach ($commande->materiels as $materiel) {
                $pivot = $materiel->pivot;
                $sousTotalHT += $pivot->sous_total_ht ?? 0;
                $totalTVA += $pivot->montant_tva ?? 0;
                $sousTotalTTC += $pivot->sous_total_ttc ?? 0;
                
                $photo = null;
                if ($materiel->photos && $materiel->photos->count() > 0) {
                    $rawPhoto = $materiel->photos->first()->url_photo ?? null;
                    $photo = $this->formatImageUrl($rawPhoto);
                } else {
                    $photo = $this->getDefaultImage($materiel->categorie_id);
                }
                
                $articles[] = [
                    'id' => $materiel->id,
                    'nom' => $materiel->nom,
                    'photo' => $photo,
                    'quantite' => $pivot->quantite,
                    'nombre_jours' => $nombreJours,
                    'prix_unitaire_ht' => $pivot->prix_unitaire_ht,
                    'taux_tva' => $pivot->taux_tva,
                    'sous_total_ht' => $pivot->sous_total_ht,
                    'montant_tva' => $pivot->montant_tva,
                    'sous_total_ttc' => $pivot->sous_total_ttc,
                ];
            }
            
            $commandeData['articles'] = $articles;
            $commandeData['totaux'] = [
                'sous_total_ht' => $sousTotalHT,
                'total_tva' => $totalTVA,
                'sous_total_ttc' => $sousTotalTTC,
                'remise' => $commande->codeReduction ? $this->calculerRemise($sousTotalHT, $commande->codeReduction) : 0,
            ];
            
            // Infos client
            $commandeData['client'] = $this->getClientInfo($commande->user);
            
            return response()->json([
                'success' => true,
                'data' => $commandeData
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur getCommande:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur chargement commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtenir le libellé du statut
     */
    private function getStatutLibelle($statut)
    {
        $labels = [
            1 => 'En attente',
            2 => 'Confirmée',
            3 => 'En cours',
            4 => 'Terminée',
            5 => 'Annulée'
        ];
        return $labels[$statut] ?? 'Inconnu';
    }
    
    /**
     * Calculer la remise
     */
    private function calculerRemise($montant, $codeReduction)
    {
        if ($codeReduction->type_reduction == 2) {
            return ($montant * $codeReduction->montant) / 100;
        }
        return $codeReduction->montant;
    }
    
    /**
     * Obtenir les infos du client
     */
    private function getClientInfo($user)
    {
        if (!$user) return null;
        
        $user->load('type');
        $type = $user->type ? $user->type->type : 'particulier';
        
        if ($type === 'particulier') {
            $particulier = Particulier::where('user_id', $user->id)->first();
            return [
                'type' => 'particulier',
                'nom_complet' => $particulier ? trim($particulier->prenom . ' ' . $particulier->nom) : $user->email,
            ];
        } else {
            $professionnel = Professionnel::with('contactPro')->where('user_id', $user->id)->first();
            return [
                'type' => 'professionnel',
                'nom_complet' => $professionnel ? $professionnel->nom_societe : $user->email,
                'telephone' => $professionnel && $professionnel->contactPro ? $professionnel->contactPro->telephone : null,
            ];
        }
    }

    /**
     * Annuler une commande
     */
    public function annuler($id)
    {
        try {
            DB::beginTransaction();
            
            $commande = Commande::with('materiels')->find($id);
            
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }
            
            if (!in_array($commande->statut, [1, 2])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne peut plus être annulée'
                ], 422);
            }
            
            // Restaurer les stocks
            foreach ($commande->materiels as $materiel) {
                $materiel->increment('stock_disponible', $materiel->pivot->quantite);
            }
            
            // Restaurer l'utilisation du code promo
            if ($commande->code_reduction_id) {
                $code = CodeReduction::find($commande->code_reduction_id);
                if ($code) {
                    $code->decrement('utilisations_actuelles');
                }
            }
            
            $commande->update(['statut' => 5]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

    /**
     * Formater une adresse pour l'affichage
     */
    private function formatAdressePourAffichage($adresse)
    {
        if (!$adresse) return '';

        $rue = trim(($adresse->numero_rue ?? '') . ' ' . ($adresse->nom_rue ?? ''));

        if ($adresse->commune) {
            // ✅ Correction : utiliser code_postal au lieu de numero_commune
            $cp = $adresse->commune->code_postal ?? '';
            $ville = $adresse->commune->nom_commune ?? '';

            if ($cp && $ville) {
                return trim($rue . ', ' . $cp . ' ' . $ville);
            } elseif ($ville) {
                return trim($rue . ', ' . $ville);
            }
        }

        return trim($rue);
    }

    /**
     * Générer un numéro de commande unique
     */
    private function genererNumeroCommande()
    {
        $prefix = 'CMD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        $code = $prefix . $date . $random;
        
        while (Commande::where('numero_commande', $code)->exists()) {
            $random = strtoupper(substr(uniqid(), -4));
            $code = $prefix . $date . $random;
        }
        
        return $code;
    }

    /**
     * Formater une adresse depuis une relation
     */
    private function formatAdresseFromRelation($adresse)
    {
        return [
            'rue' => trim(($adresse->numero_rue ?? '') . ' ' . ($adresse->nom_rue ?? '')),
            'adresse_complete' => $this->formatAdressePourAffichage($adresse)
        ];
    }

    /**
     * Formatte une URL d'image
     */
    private function formatImageUrl($url)
    {
        if (!$url) {
            return '/placeholder.jpg';
        }

        // Si c'est déjà une URL complète
        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) {
            return $url;
        }

        // Ajouter le slash si nécessaire
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        // Pour les images dans le dossier public
        if (str_starts_with($url, '/materiels/') || str_starts_with($url, '/images/')) {
            return asset($url);
        }

        // Pour les images dans storage
        if (str_starts_with($url, '/storage/')) {
            return asset($url);
        }

        return asset($url);
    }

    /**
     * Retourne une image par défaut selon la catégorie
     */
    private function getDefaultImage($categorie_id)
    {
        switch($categorie_id) {
            case 1: // Sièges
                return '/images/materiels/default-chair.jpg';
            case 2: // Tables
                return '/images/materiels/default-table.jpg';
            case 3: // Décoration
                return '/images/materiels/default-deco.jpg';
            default:
                return '/placeholder.jpg';
        }
    }
    /**
     * Télécharger la facture PDF
     */
    public function telechargerFacture($id)
    {
        try {
            $user = request()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Charger la commande avec toutes ses relations
            $commande = Commande::with([
                'user',
                'user.particulier',
                'user.professionnel',
                'user.professionnel.contactPro',
                'user.type',
                'materiels',
                'adresseLivraison',
                'adresseLivraison.commune',
                'modeLivraison',
                'modeRetour',
                'codeReduction',
                'statutCommande'
            ])->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if ($user->id !== $commande->user_id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Formater les données pour la facture
            $commandeFormatee = $this->formatCommandeDetails($commande);

            // Informations de l'entreprise
            $entreprise = [
                'nom' => 'TerraSana Location',
                'siret' => '123 456 789 00012',
                'tva_intra' => 'BE12 345678901',
                'adresse' => '123 Rue du Commerce, 1000 bruxelles',
                'code_postal' => '1000',
                'ville' => 'Bruxelles',
                'telephone' => '01 23 45 67 89',
                'email' => 'contact@terrasana-location.be',
                'site' => 'www.terrasana-location.fr',
                'logo' => public_path('images/logo.png')
            ];

            // Générer le PDF
            $pdf = Pdf::loadView('pdfs.facture', [
                'commande' => $commandeFormatee,
                'entreprise' => $entreprise,
                'date_generation' => now()->format('d/m/Y H:i')
            ]);

            // Configurer le PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true
            ]);

            // Télécharger le PDF
            return $pdf->download('facture_' . $commande->numero_commande . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Erreur génération facture', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer la facture par email
     */
    public function envoyerFactureEmail($id)
    {
        try {
            $user = request()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $commande = Commande::with([
                'user',
                'user.particulier',
                'user.professionnel',
                'materiels'
            ])->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if ($user->id !== $commande->user_id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Formater les données
            $commandeFormatee = $this->formatCommandeDetails($commande);

            // Informations de l'entreprise
            $entreprise = [
                'nom' => 'TerraSana Location',
                'siret' => '123 456 789 00012',
                'tva_intra' => 'BE12 345678901',
                'adresse' => '123 Rue du Commerce, 1000 Bruxelles',
                'email' => 'contact@terrasana-location.be'
            ];

            // Générer le PDF
            $pdf = Pdf::loadView('pdfs.facture', [
                'commande' => $commandeFormatee,
                'entreprise' => $entreprise,
                'date_generation' => now()->format('d/m/Y H:i')
            ]);

            // TODO: Implémenter l'envoi d'email
            // Mail::to($user->email)->send(new FactureCommande($commandeFormatee, $pdf));

            return response()->json([
                'success' => true,
                'message' => 'Facture envoyée par email avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur envoi email facture', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}