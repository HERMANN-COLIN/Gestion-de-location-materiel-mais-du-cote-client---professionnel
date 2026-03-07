<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Panier;
use App\Models\Materiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PanierController extends Controller
{
    /**
     * Récupérer le panier de l'utilisateur connecté
     *
     * CORRECTIONS :
     * - Eager load de 'materiel.photos' ET 'materiel.categorie' (évite N+1 + erreur null)
     * - Calcul explicite des prix au lieu de dépendre d'accessors incertains
     * - formatImageUrl() utilise asset() pour garantir une URL absolue
     */
   /**
     * Récupérer le panier de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // ✅ FORCER le chargement des relations
            $panierItems = Panier::with(['materiel.categorie', 'materiel.photos'])
                ->where('user_id', $user->id)
                ->get();

            \Log::info('📦 Panier chargé pour user ' . $user->id . ' : ' . $panierItems->count() . ' articles');

            $items = $panierItems->map(function ($item) {
                $materiel = $item->materiel;
                
                \Log::info('📦 Article panier: ' . $materiel->nom . ' - Photos: ' . ($materiel->photos ? $materiel->photos->count() : 0));

                // ✅ Calcul des prix
                $prixHT  = floatval($item->prix_unitaire_ht);
                $tauxTVA = floatval($item->taux_tva ?? $materiel->taux_tva ?? 20);
                $prixTTC = round($prixHT * (1 + $tauxTVA / 100), 2);
                $qty     = intval($item->quantite);

                $totalHT  = round($prixHT  * $qty, 2);
                $totalTTC = round($prixTTC * $qty, 2);

                // ✅ GESTION DES PHOTOS AMÉLIORÉE
                $photo = null;
                
                // Essayer de récupérer la photo principale d'abord
                if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                    // Chercher la photo principale
                    $mainPhoto = $materiel->photos->firstWhere('est_principale', true);
                    if ($mainPhoto) {
                        $photo = $this->formatImageUrl($mainPhoto->url_photo);
                        \Log::info('✅ Photo principale trouvée: ' . $photo);
                    } else {
                        // Sinon prendre la première
                        $photo = $this->formatImageUrl($materiel->photos->first()->url_photo);
                        \Log::info('✅ Première photo utilisée: ' . $photo);
                    }
                }
                
                // Si toujours pas de photo, chercher une propriété photo directe
                if (!$photo && isset($materiel->photo)) {
                    $photo = $this->formatImageUrl($materiel->photo);
                    \Log::info('✅ Photo directe utilisée: ' . $photo);
                }

                return [
                    'id'               => $item->id,
                    'materiel_id'      => $item->materiel_id,
                    'nom'              => $materiel->nom,
                    'quantite'         => $qty,
                    'prix_unitaire_ht' => $prixHT,
                    'prix_unitaire_ttc'=> $prixTTC,
                    'taux_tva'         => $tauxTVA,
                    'total_ht'         => $totalHT,
                    'total_ttc'        => $totalTTC,
                    'photo'            => $photo, // Peut être null si pas de photo
                    'categorie'        => $materiel->categorie?->nom ?? 'Non catégorisé',
                    'stock_disponible' => intval($materiel->stock_disponible),
                    'dimensions'       => $materiel->dimensions,
                ];
            });

            // ✅ Totaux
            $totalHT  = round($items->sum('total_ht'),  2);
            $totalTTC = round($items->sum('total_ttc'), 2);
            $totalTVA = round($totalTTC - $totalHT,     2);

            return response()->json([
                'success' => true,
                'data' => [
                    'items'           => $items,
                    'total_ht'        => $totalHT,
                    'total_tva'       => $totalTVA,
                    'total_ttc'       => $totalTTC,
                    'nombre_articles' => $items->count(),
                    'nombre_unites'   => $items->sum('quantite'),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Erreur panier: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du panier',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Ajouter un article au panier
     */
    public function ajouter(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'materiel_id' => 'required|exists:materiels,id',
                'quantite'    => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $user     = $request->user();
            $materiel = Materiel::find($request->materiel_id);

            if ($materiel->stock_disponible < $request->quantite) {
                return response()->json([
                    'success'          => false,
                    'message'          => 'Stock insuffisant',
                    'stock_disponible' => $materiel->stock_disponible
                ], 422);
            }

            $existant = Panier::where('user_id', $user->id)
                ->where('materiel_id', $request->materiel_id)
                ->first();

            if ($existant) {
                $nouvelleQuantite = $existant->quantite + $request->quantite;

                if ($nouvelleQuantite > $materiel->stock_disponible) {
                    return response()->json([
                        'success'          => false,
                        'message'          => 'Quantité totale dépasse le stock disponible',
                        'stock_disponible' => $materiel->stock_disponible
                    ], 422);
                }

                $existant->update(['quantite' => $nouvelleQuantite]);
                $item = $existant->fresh();
            } else {
                $item = Panier::create([
                    'user_id'          => $user->id,
                    'materiel_id'      => $request->materiel_id,
                    'quantite'         => $request->quantite,
                    'prix_unitaire_ht' => $materiel->prix_journalier_ht,
                    'taux_tva'         => $materiel->taux_tva,
                ]);
            }

            // Calcul explicite pour la réponse
            $prixHT  = floatval($item->prix_unitaire_ht);
            $tauxTVA = floatval($item->taux_tva ?? 20);
            $qty     = intval($item->quantite);

            return response()->json([
                'success' => true,
                'message' => 'Article ajouté au panier',
                'data' => [
                    'id'          => $item->id,
                    'materiel_id' => $item->materiel_id,
                    'quantite'    => $qty,
                    'total_ht'    => round($prixHT * $qty, 2),
                    'total_ttc'   => round($prixHT * (1 + $tauxTVA / 100) * $qty, 2),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'ajout au panier",
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour la quantité d'un article
     */
    public function mettreAJour(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantite' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $item = Panier::with('materiel')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé dans le panier'
                ], 404);
            }

            if ($request->quantite > $item->materiel->stock_disponible) {
                return response()->json([
                    'success'          => false,
                    'message'          => 'Stock insuffisant',
                    'stock_disponible' => $item->materiel->stock_disponible
                ], 422);
            }

            $item->update(['quantite' => $request->quantite]);

            $prixHT  = floatval($item->prix_unitaire_ht);
            $tauxTVA = floatval($item->taux_tva ?? 20);
            $qty     = intval($request->quantite);

            return response()->json([
                'success' => true,
                'message' => 'Quantité mise à jour',
                'data' => [
                    'id'        => $item->id,
                    'quantite'  => $qty,
                    'total_ht'  => round($prixHT * $qty, 2),
                    'total_ttc' => round($prixHT * (1 + $tauxTVA / 100) * $qty, 2),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un article du panier
     */
    public function supprimer($id)
    {
        try {
            $user = request()->user();
            $item = Panier::where('id', $id)->where('user_id', $user->id)->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé dans le panier'
                ], 404);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article retiré du panier'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vider le panier
     */
    public function vider(Request $request)
    {
        try {
            Panier::where('user_id', $request->user()->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Panier vidé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du panier',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compter le nombre d'articles dans le panier
     */
    public function compter(Request $request)
    {
        try {
            $count = Panier::where('user_id', $request->user()->id)->sum('quantite');

            return response()->json([
                'success' => true,
                'count'   => intval($count)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatte une URL d'image en URL absolue
     */
    // Dans PanierController.php
private function formatImageUrl(?string $url): ?string
{
    if (!$url) return null;

    // Si c'est déjà une URL complète
    if (str_starts_with($url, 'http')) return $url;

    // Nettoyer le slash au début pour éviter les doubles slashes
    $clean = ltrim($url, '/');

    // On utilise simplement url() qui pointe vers le dossier 'public'
    return url($clean); 
}
}