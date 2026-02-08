<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategorieMateriel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CategorieMaterielController extends Controller
{
    /**
     * Liste toutes les catégories
     */
    public function index()
    {
        try {
            $categories = CategorieMateriel::withCount(['materiels' => function($query) {
                $query->where('stock_disponible', '>', 0); // Compter uniquement les matériels disponibles
            }])
            ->orderBy('nom', 'asc')
            ->get();

            // Formater les URLs des images pour chaque catégorie
            $categories->transform(function($categorie) {
                // Ajouter une image par défaut pour la catégorie
                switch(strtolower($categorie->nom)) {
                    case 'sieges':
                        $categorie->image_url = '/images/categories/sieges.jpg';
                        $categorie->icon = '🪑';
                        break;
                    case 'tables':
                        $categorie->image_url = '/images/categories/tables.jpg';
                        $categorie->icon = '🪟';
                        break;
                    case 'décoration':
                    case 'decoration':
                        $categorie->image_url = '/images/categories/decoration.jpg';
                        $categorie->icon = '✨';
                        break;
                    default:
                        $categorie->image_url = '/images/categories/default.jpg';
                        $categorie->icon = '📦';
                }
                
                // Assurer que la description n'est pas null
                $categorie->description = $categorie->description ?? 'Matériels de location pour vos événements';
                
                return $categorie;
            });

            return response()->json([
                'success' => true,
                'data' => $categories,
                'count' => $categories->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des catégories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une catégorie spécifique avec ses matériels
     */
    public function show($id)
    {
        try {
            // Charger la catégorie avec ses matériels et leurs photos
            $categorie = CategorieMateriel::with([
                'materiels' => function($query) {
                    $query->with('photos')
                          ->where('stock_disponible', '>', 0)
                          ->orderBy('nom', 'asc');
                }
            ])->find($id);

            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée'
                ], 404);
            }

            // Ajouter des statistiques
            $categorie->stats = [
                'total_materiels' => $categorie->materiels->count(),
                'prix_moyen' => $categorie->materiels->avg('prix_journalier'),
                'stock_total' => $categorie->materiels->sum('stock_disponible'),
                'types' => $categorie->materiels->pluck('nom')->map(function($nom) {
                    return explode(' ', $nom)[0]; // Premier mot comme type
                })->unique()->values()
            ];

            // Ajouter l'image de la catégorie
            switch(strtolower($categorie->nom)) {
                case 'sieges':
                    $categorie->image_url = '/images/categories/sieges-banner.jpg';
                    $categorie->icon = '🪑';
                    break;
                case 'tables':
                    $categorie->image_url = '/images/categories/tables-banner.jpg';
                    $categorie->icon = '🪟';
                    break;
                case 'décoration':
                case 'decoration':
                    $categorie->image_url = '/images/categories/decoration-banner.jpg';
                    $categorie->icon = '✨';
                    break;
                default:
                    $categorie->image_url = '/images/categories/default-banner.jpg';
                    $categorie->icon = '📦';
            }

            return response()->json([
                'success' => true,
                'data' => $categorie
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les matériels d'une catégorie avec pagination et filtres
     */
    public function materiels(Request $request, $id)
    {
        try {
            // Vérifier que la catégorie existe
            $categorie = CategorieMateriel::find($id);
            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée'
                ], 404);
            }

            $query = $categorie->materiels()->with(['categorie', 'photos'])
                ->where('stock_disponible', '>', 0);

            // Filtrage par type (premier mot du nom)
            if ($request->has('type') && $request->type) {
                $query->where('nom', 'like', $request->type . '%');
            }

            // Filtrage par prix
            if ($request->has('prix_min') && $request->prix_min) {
                $query->where('prix_journalier', '>=', $request->prix_min);
            }
            
            if ($request->has('prix_max') && $request->prix_max) {
                $query->where('prix_journalier', '<=', $request->prix_max);
            }

            // Recherche par nom
            if ($request->has('search') && $request->search) {
                $query->where('nom', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
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

            // Ajouter les types disponibles pour les filtres
            $types = $categorie->materiels()
                ->where('stock_disponible', '>', 0)
                ->pluck('nom')
                ->map(function($nom) {
                    return explode(' ', $nom)[0]; // Premier mot
                })
                ->unique()
                ->values();

            return response()->json([
                'success' => true,
                'categorie' => [
                    'id' => $categorie->id,
                    'nom' => $categorie->nom,
                    'description' => $categorie->description
                ],
                'filters' => [
                    'types' => $types,
                    'prix_min' => $materiels->isEmpty() ? 0 : $materiels->min('prix_journalier'),
                    'prix_max' => $materiels->isEmpty() ? 0 : $materiels->max('prix_journalier')
                ],
                'data' => $materiels->items(),
                'pagination' => [
                    'current_page' => $materiels->currentPage(),
                    'last_page' => $materiels->lastPage(),
                    'per_page' => $materiels->perPage(),
                    'total' => $materiels->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des matériels de la catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée une nouvelle catégorie (Admin)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:100|unique:categories_materiel',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $categorie = CategorieMateriel::create([
                'nom' => ucfirst($request->nom),
                'description' => $request->description
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => $categorie
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une catégorie (Admin)
     */
    public function update(Request $request, $id)
    {
        try {
            $categorie = CategorieMateriel::find($id);

            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:100|unique:categories_materiel,nom,' . $id,
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $categorie->update([
                'nom' => ucfirst($request->nom),
                'description' => $request->description
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => $categorie
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une catégorie (Admin)
     */
    public function destroy($id)
    {
        try {
            $categorie = CategorieMateriel::withCount('materiels')->find($id);

            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée'
                ], 404);
            }

            if ($categorie->materiels_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette catégorie car elle contient des matériels',
                    'materiels_count' => $categorie->materiels_count
                ], 422);
            }

            DB::beginTransaction();
            $categorie->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche de catégories
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

            $categories = CategorieMateriel::where('nom', 'like', '%' . $query . '%')
                ->orWhere('description', 'like', '%' . $query . '%')
                ->withCount(['materiels' => function($q) {
                    $q->where('stock_disponible', '>', 0);
                }])
                ->orderBy('nom', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'count' => $categories->count()
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
     * Liste toutes les catégories avec un matériel en vedette
     */
    public function avecVedette()
    {
        try {
            $categories = CategorieMateriel::with(['materiels' => function($query) {
                $query->with('photos')
                      ->where('stock_disponible', '>', 0)
                      ->inRandomOrder()
                      ->limit(1); // Un matériel au hasard par catégorie
            }])
            ->withCount(['materiels' => function($query) {
                $query->where('stock_disponible', '>', 0);
            }])
            ->orderBy('nom', 'asc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des catégories avec matériel vedette',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}