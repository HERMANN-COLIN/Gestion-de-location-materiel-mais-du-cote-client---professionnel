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
            $query = Materiel::with(['categorie', 'photos'])
                ->where('stock_disponible', '>', 0);

            // Filtrage par catégorie
            if ($request->has('categorie_id') && $request->categorie_id) {
                $query->where('categorie_id', $request->categorie_id);
                
                // Ajouter les infos de la catégorie
                $categorie = CategorieMateriel::find($request->categorie_id);
            }

            // Filtrage par prix
            if ($request->has('prix_min') && $request->prix_min !== null) {
                $query->where('prix_journalier', '>=', floatval($request->prix_min));
            }
            
            if ($request->has('prix_max') && $request->prix_max !== null) {
                $query->where('prix_journalier', '<=', floatval($request->prix_max));
            }

            // Recherche par nom ou description
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('nom', 'like', '%' . $searchTerm . '%')
                      ->orWhere('description', 'like', '%' . $searchTerm . '%')
                      ->orWhereHas('categorie', function($q) use ($searchTerm) {
                          $q->where('nom', 'like', '%' . $searchTerm . '%');
                      });
                });
            }

            // Tri
            $sort = $request->get('sort', 'nom');
            $order = $request->get('order', 'asc');
            
            $allowedSorts = ['nom', 'prix_journalier', 'created_at', 'stock_disponible'];
            if (in_array($sort, $allowedSorts)) {
                $query->orderBy($sort, $order);
            } else {
                $query->orderBy('nom', 'asc');
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $materiels = $query->paginate($perPage);

            // Formater les URLs des images
            $materiels->getCollection()->transform(function($materiel) {
                // S'assurer que les photos ont des URLs complètes
                if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                    $materiel->photos->transform(function($photo) {
                        $photo->url_photo = $this->formatImageUrl($photo->url_photo);
                        return $photo;
                    });
                } else {
                    // Ajouter une photo par défaut si aucune photo n'existe
                    $materiel->photos = collect([[
                        'id' => 0,
                        'materiel_id' => $materiel->id,
                        'url_photo' => $this->getDefaultImage($materiel->categorie_id),
                        'is_default' => true
                    ]]);
                }
                
                // Formater le prix
                $materiel->prix_formatted = number_format($materiel->prix_journalier, 2, ',', ' ') . ' €';
                
                // Ajouter un indicateur si c'est nouveau (moins de 30 jours)
                $materiel->is_new = $materiel->created_at->diffInDays(now()) < 30;
                
                return $materiel;
            });

            $response = [
                'success' => true,
                'data' => $materiels->items(),
                'pagination' => [
                    'current_page' => $materiels->currentPage(),
                    'last_page' => $materiels->lastPage(),
                    'per_page' => $materiels->perPage(),
                    'total' => $materiels->total(),
                    'from' => $materiels->firstItem(),
                    'to' => $materiels->lastItem()
                ]
            ];

            // Ajouter les infos de la catégorie si filtré par catégorie
            if (isset($categorie) && $categorie) {
                $response['categorie'] = $categorie;
            }

            // Ajouter les filtres disponibles
            $response['filters'] = [
                'categories' => CategorieMateriel::all(['id', 'nom']),
                'prix_range' => [
                    'min' => Materiel::where('stock_disponible', '>', 0)->min('prix_journalier') ?? 0,
                    'max' => Materiel::where('stock_disponible', '>', 0)->max('prix_journalier') ?? 100
                ]
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des matériels',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Matériel non trouvé'
                ], 404);
            }

            // Formater les URLs des photos
            if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                $materiel->photos->transform(function($photo) {
                    $photo->url_photo = $this->formatImageUrl($photo->url_photo);
                    return $photo;
                });
            } else {
                // Photo par défaut
                $materiel->photos = collect([[
                    'id' => 0,
                    'materiel_id' => $materiel->id,
                    'url_photo' => $this->getDefaultImage($materiel->categorie_id),
                    'is_default' => true
                ]]);
            }

            // Formater les données
            $materiel->prix_formatted = number_format($materiel->prix_journalier, 2, ',', ' ') . ' €';
            $materiel->is_new = $materiel->created_at->diffInDays(now()) < 30;
            
            // Calculer la disponibilité
            $materiel->disponibilite = [
                'disponible' => $materiel->stock_disponible > 0,
                'niveau' => $materiel->stock_disponible < 10 ? 'low' : ($materiel->stock_disponible < 20 ? 'medium' : 'high'),
                'message' => $materiel->stock_disponible > 0 
                    ? ($materiel->stock_disponible < 10 
                        ? 'Stock faible' 
                        : 'En stock') 
                    : 'Rupture de stock'
            ];

            // Matériels similaires (même catégorie)
            $similaires = Materiel::with(['categorie', 'photos'])
                ->where('categorie_id', $materiel->categorie_id)
                ->where('id', '!=', $materiel->id)
                ->where('stock_disponible', '>', 0)
                ->limit(6)
                ->get();

            // Formater les similaires
            $similaires->transform(function($similar) {
                if ($similar->photos && $similar->photos->isNotEmpty()) {
                    $similar->main_photo = $this->formatImageUrl($similar->photos->first()->url_photo);
                } else {
                    $similar->main_photo = $this->getDefaultImage($similar->categorie_id);
                }
                $similar->prix_formatted = number_format($similar->prix_journalier, 2, ',', ' ') . ' €';
                return $similar;
            });

            return response()->json([
                'success' => true,
                'data' => $materiel,
                'similaires' => $similaires
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du matériel',
                'error' => $e->getMessage()
            ], 500);
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
                'prix_journalier' => 'required|numeric|min:0',
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

            $materiel = Materiel::create($request->all());

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
                'prix_journalier' => 'required|numeric|min:0',
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
            $materiels = Materiel::with(['categorie', 'photos'])
                ->where('stock_disponible', '>', 0)
                ->inRandomOrder()
                ->limit(8)
                ->get();

            // Formater les photos
            $materiels->transform(function($materiel) {
                if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                    $materiel->main_photo = $this->formatImageUrl($materiel->photos->first()->url_photo);
                } else {
                    $materiel->main_photo = $this->getDefaultImage($materiel->categorie_id);
                }
                $materiel->prix_formatted = number_format($materiel->prix_journalier, 2, ',', ' ') . ' €';
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

            $materiels = Materiel::with(['categorie', 'photos'])
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
                if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                    $materiel->main_photo = $this->formatImageUrl($materiel->photos->first()->url_photo);
                } else {
                    $materiel->main_photo = $this->getDefaultImage($materiel->categorie_id);
                }
                $materiel->prix_formatted = number_format($materiel->prix_journalier, 2, ',', ' ') . ' €';
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

        // Vérifier si c'est une URL relative à votre storage
        if (str_starts_with($url, '/storage/')) {
            return asset($url);
        }

        // Pour les images dans le dossier public/materiels
        if (str_starts_with($url, '/materiels/')) {
            return asset('materiels/' . basename($url));
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
     * Matériels récemment ajoutés
     */
    public function recents()
    {
        try {
            $materiels = Materiel::with(['categorie', 'photos'])
                ->where('stock_disponible', '>', 0)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get();

            // Marquer comme nouveaux
            $materiels->transform(function($materiel) {
                $materiel->is_new = $materiel->created_at->diffInDays(now()) < 30;
                if ($materiel->photos && $materiel->photos->isNotEmpty()) {
                    $materiel->main_photo = $this->formatImageUrl($materiel->photos->first()->url_photo);
                } else {
                    $materiel->main_photo = $this->getDefaultImage($materiel->categorie_id);
                }
                $materiel->prix_formatted = number_format($materiel->prix_journalier, 2, ',', ' ') . ' €';
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
}