<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materiel;
use App\Models\CategorieMateriel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MaterielController extends Controller
{
    /**
     * Liste tous les matériels avec pagination et filtres
     */
    public function index(Request $request)
    {
        try {
            // On charge la catégorie et les photos
            $query = Materiel::with(['categorie', 'photos', 'photoPrincipale']);

            // Par défaut, on cache ce qui est en rupture, sauf si demandé explicitement
            if (!$request->has('include_out_of_stock')) {
                $query->where('stock_disponible', '>', 0);
            }

            // Filtrage par catégorie
            if ($request->filled('categorie_id')) {
                $query->where('categorie_id', $request->categorie_id);
            }

            // Filtrage par prix HT
            if ($request->filled('prix_min')) {
                $query->where('prix_journalier_ht', '>=', floatval($request->prix_min));
            }
            if ($request->filled('prix_max')) {
                $query->where('prix_journalier_ht', '<=', floatval($request->prix_max));
            }

            // Recherche textuelle
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('nom', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%');
                });
            }

            // Tri
            $sort = $request->get('sort', 'nom');
            $order = $request->get('order', 'asc');
            $allowedSorts = ['nom', 'prix_journalier_ht', 'created_at', 'stock_disponible'];
            
            $query->orderBy(in_array($sort, $allowedSorts) ? $sort : 'nom', $order);

            // Pagination
            $perPage = $request->get('per_page', 12);
            $materiels = $query->paginate($perPage);

            // Transformation de la collection
            $materiels->getCollection()->transform(function($materiel) {
                // On utilise l'accessor du modèle pour marquer les nouveautés
                $materiel->is_new = $materiel->created_at->diffInDays(now()) < 30;
                
                // Ajouter le prix TTC
                $materiel->prix_ttc = $materiel->prix_ttc;
                $materiel->prix_formatted = $materiel->prix_formatted;
                
                // Formater les URLs des photos
                if ($materiel->photos) {
                    foreach ($materiel->photos as $photo) {
                        $photo->url_photo = $this->formatImageUrl($photo->url_photo);
                    }
                }
                
                // On s'assure que l'URL de la photo principale est formatée
                if ($materiel->photoPrincipale) {
                    $materiel->main_photo = $this->formatImageUrl($materiel->photoPrincipale->url_photo);
                } else {
                    $materiel->main_photo = $this->getDefaultImage($materiel->categorie_id);
                }
                return $materiel;
            });

            return response()->json([
                'success' => true,
                'data' => $materiels->items(),
                'pagination' => [
                    'current_page' => $materiels->currentPage(),
                    'last_page' => $materiels->lastPage(),
                    'total' => $materiels->total(),
                ],
                'filters' => [
                    'categories' => CategorieMateriel::all(['id', 'nom']),
                    'prix_range' => [
                        'min' => Materiel::min('prix_journalier_ht') ?? 0,
                        'max' => Materiel::max('prix_journalier_ht') ?? 100
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Affiche un matériel spécifique
     */
    public function show($id)
    {
        try {
            $materiel = Materiel::with(['categorie', 'photos'])->find($id);

            if (!$materiel) {
                return response()->json(['success' => false, 'message' => 'Matériel non trouvé'], 404);
            }

            // Ajout d'infos de disponibilité pour le Front
            $materiel->status_stock = [
                'disponible' => $materiel->stock_disponible > 0,
                'niveau' => $materiel->stock_disponible < 5 ? 'critique' : 'ok',
                'quantite' => $materiel->stock_disponible
            ];

            // Ajouter les prix formatés
            $materiel->prix_ttc = $materiel->prix_ttc;
            $materiel->prix_formatted = $materiel->prix_formatted;

            // Formater les URLs des photos
            foreach ($materiel->photos as $photo) {
                $photo->url_photo = $this->formatImageUrl($photo->url_photo);
            }

            // Matériels similaires
            $similaires = Materiel::with('photoPrincipale')
                ->where('categorie_id', $materiel->categorie_id)
                ->where('id', '!=', $materiel->id)
                ->where('stock_disponible', '>', 0)
                ->limit(4)
                ->get()
                ->map(function($item) {
                    $item->prix_ttc = $item->prix_ttc;
                    $item->prix_formatted = $item->prix_formatted;
                    if ($item->photoPrincipale) {
                        $item->main_photo = $this->formatImageUrl($item->photoPrincipale->url_photo);
                    } else {
                        $item->main_photo = $this->getDefaultImage($item->categorie_id);
                    }
                    return $item;
                });

            return response()->json([
                'success' => true,
                'data' => $materiel,
                'similaires' => $similaires
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Crée un nouveau matériel (Admin)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'categorie_id' => 'required|exists:categories_materiel,id',
                'nom' => 'required|string|max:150',
                'description' => 'nullable|string',
                'prix_journalier_ht' => 'required|numeric|min:0',
                'taux_tva' => 'required|numeric|min:0|max:100',
                'dimensions' => 'nullable|string|max:100',
                'stock_total' => 'required|integer|min:0',
                'stock_disponible' => 'required|integer|min:0|lte:stock_total'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $materiel = Materiel::create([
                'categorie_id' => $request->categorie_id,
                'nom' => $request->nom,
                'description' => $request->description,
                'prix_journalier_ht' => $request->prix_journalier_ht,
                'taux_tva' => $request->taux_tva ?? 21.00,
                'dimensions' => $request->dimensions,
                'stock_total' => $request->stock_total,
                'stock_disponible' => $request->stock_disponible
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Matériel créé avec succès',
                'data' => $materiel->load(['categorie', 'photos'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du matériel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un matériel (Admin)
     */
    public function update(Request $request, $id)
    {
        try {
            $materiel = Materiel::find($id);

            if (!$materiel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Matériel non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'categorie_id' => 'required|exists:categories_materiel,id',
                'nom' => 'required|string|max:150',
                'description' => 'nullable|string',
                'prix_journalier_ht' => 'required|numeric|min:0',
                'taux_tva' => 'required|numeric|min:0|max:100',
                'dimensions' => 'nullable|string|max:100',
                'stock_total' => 'required|integer|min:0',
                'stock_disponible' => 'required|integer|min:0|lte:stock_total'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $materiel->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Matériel mis à jour avec succès',
                'data' => $materiel->load(['categorie', 'photos'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du matériel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un matériel (Admin)
     */
    public function destroy($id)
    {
        try {
            $materiel = Materiel::find($id);

            if (!$materiel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Matériel non trouvé'
                ], 404);
            }

            // Vérifier si le matériel est dans des commandes actives
            // TODO: Implémenter cette vérification quand les commandes seront créées

            DB::beginTransaction();
            $materiel->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Matériel supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du matériel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Matériels populaires
     */
    public function populaires()
    {
        try {
            $materiels = Materiel::with(['categorie', 'photos', 'photoPrincipale'])
                ->where('stock_disponible', '>', 0)
                ->inRandomOrder()
                ->limit(8)
                ->get();

            // Formater les photos
            $materiels->transform(function($materiel) {
                $materiel->is_new = $materiel->created_at->diffInDays(now()) < 30;
                $materiel->prix_ttc = $materiel->prix_ttc;
                $materiel->prix_formatted = $materiel->prix_formatted;
                
                // Formater les URLs des photos
                if ($materiel->photos) {
                    foreach ($materiel->photos as $photo) {
                        $photo->url_photo = $this->formatImageUrl($photo->url_photo);
                    }
                }
                
                if ($materiel->photoPrincipale) {
                    $materiel->main_photo = $this->formatImageUrl($materiel->photoPrincipale->url_photo);
                } else {
                    $materiel->main_photo = $this->getDefaultImage($materiel->categorie_id);
                }
                return $materiel;
            });

            return response()->json([
                'success' => true,
                'data' => $materiels
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des matériels populaires',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche de matériels
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q');
            
            if (!$query || strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paramètre de recherche doit contenir au moins 2 caractères'
                ], 400);
            }

            $materiels = Materiel::with(['categorie', 'photos', 'photoPrincipale'])
                ->where(function($q) use ($query) {
                    $q->where('nom', 'like', '%' . $query . '%')
                      ->orWhere('description', 'like', '%' . $query . '%')
                      ->orWhereHas('categorie', function($q) use ($query) {
                          $q->where('nom', 'like', '%' . $query . '%');
                      });
                })
                ->where('stock_disponible', '>', 0)
                ->orderBy('nom', 'asc')
                ->get();

            // Formater les photos
            $materiels->transform(function($materiel) {
                $materiel->prix_ttc = $materiel->prix_ttc;
                $materiel->prix_formatted = $materiel->prix_formatted;
                
                // Formater les URLs des photos
                if ($materiel->photos) {
                    foreach ($materiel->photos as $photo) {
                        $photo->url_photo = $this->formatImageUrl($photo->url_photo);
                    }
                }
                
                if ($materiel->photoPrincipale) {
                    $materiel->main_photo = $this->formatImageUrl($materiel->photoPrincipale->url_photo);
                } else {
                    $materiel->main_photo = $this->getDefaultImage($materiel->categorie_id);
                }
                return $materiel;
            });

            return response()->json([
                'success' => true,
                'data' => $materiels,
                'count' => $materiels->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie la disponibilité d'un matériel
     */
    public function disponibilite(Request $request, $id)
    {
        try {
            $materiel = Materiel::find($id);
            
            if (!$materiel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Matériel non trouvé'
                ], 404);
            }

            $quantite = $request->get('quantite', 1);
            $disponible = $materiel->stock_disponible >= $quantite;

            return response()->json([
                'success' => true,
                'disponible' => $disponible,
                'stock_disponible' => $materiel->stock_disponible,
                'quantite_demandee' => $quantite,
                'message' => $disponible 
                    ? 'Matériel disponible en quantité suffisante' 
                    : 'Stock insuffisant. Disponible: ' . $materiel->stock_disponible . ' unité(s)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification de disponibilité',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour le stock d'un matériel (Admin)
     */
    public function updateStock(Request $request, $id)
    {
        try {
            $materiel = Materiel::find($id);

            if (!$materiel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Matériel non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'stock_total' => 'required|integer|min:0',
                'stock_disponible' => 'required|integer|min:0|lte:stock_total'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $materiel->update([
                'stock_total' => $request->stock_total,
                'stock_disponible' => $request->stock_disponible
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour avec succès',
                'data' => $materiel
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Matériels récemment ajoutés
     */
    public function recents()
    {
        try {
            $materiels = Materiel::with(['categorie', 'photos', 'photoPrincipale'])
                ->where('stock_disponible', '>', 0)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get();

            // Marquer comme nouveaux
            $materiels->transform(function($materiel) {
                $materiel->is_new = $materiel->created_at->diffInDays(now()) < 30;
                $materiel->prix_ttc = $materiel->prix_ttc;
                $materiel->prix_formatted = $materiel->prix_formatted;
                
                // Formater les URLs des photos
                if ($materiel->photos) {
                    foreach ($materiel->photos as $photo) {
                        $photo->url_photo = $this->formatImageUrl($photo->url_photo);
                    }
                }
                
                if ($materiel->photoPrincipale) {
                    $materiel->main_photo = $this->formatImageUrl($materiel->photoPrincipale->url_photo);
                } else {
                    $materiel->main_photo = $this->getDefaultImage($materiel->categorie_id);
                }
                return $materiel;
            });

            return response()->json([
                'success' => true,
                'data' => $materiels
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des matériels récents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatte une URL d'image
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
}