<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fonction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FonctionController extends Controller
{
    /**
     * Display a listing of functions.
     */
    public function index(Request $request)
    {
        // Récupérer toutes les fonctions triées par nom
        $fonctions = Fonction::orderBy('fonction', 'asc')->get();
        
        return response()->json([
            'data' => $fonctions,
            'count' => $fonctions->count()
        ]);
    }

    /**
     * Store a newly created function (admin only).
     */
    public function store(Request $request)
    {
        // Vérifier les permissions (admin seulement)
        $user = $request->user();
        if (!$user || $user->type_id !== 3) { // Supposons que type_id = 3 est admin
            return response()->json([
                'message' => 'Accès non autorisé. Admin seulement.'
            ], 403);
        }
        
        // Validation des données
        $validated = $request->validate([
            'fonction' => 'required|string|max:255|unique:fonctions,fonction',
            'description' => 'nullable|string',
        ]);
        
        // Créer la fonction
        $fonction = Fonction::create($validated);
        
        return response()->json([
            'message' => 'Fonction créée avec succès',
            'data' => $fonction
        ], 201);
    }

    /**
     * Display the specified function.
     */
    public function show($id)
    {
        $fonction = Fonction::find($id);
        
        if (!$fonction) {
            return response()->json([
                'message' => 'Fonction non trouvée'
            ], 404);
        }
        
        // Compter combien de contacts ont cette fonction
        $contactsCount = DB::table('contact_pro')
            ->where('fonction_id', $id)
            ->count();
        
        return response()->json([
            'data' => $fonction,
            'contacts_count' => $contactsCount
        ]);
    }

    /**
     * Update the specified function (admin only).
     */
    public function update(Request $request, $id)
    {
        // Vérifier les permissions (admin seulement)
        $user = $request->user();
        if (!$user || $user->type_id !== 3) {
            return response()->json([
                'message' => 'Accès non autorisé. Admin seulement.'
            ], 403);
        }
        
        $fonction = Fonction::find($id);
        
        if (!$fonction) {
            return response()->json([
                'message' => 'Fonction non trouvée'
            ], 404);
        }
        
        // Validation des données
        $validated = $request->validate([
            'fonction' => 'string|max:255|unique:fonctions,fonction,' . $id,
            'description' => 'nullable|string',
        ]);
        
        // Mettre à jour la fonction
        $fonction->update($validated);
        
        return response()->json([
            'message' => 'Fonction mise à jour avec succès',
            'data' => $fonction
        ]);
    }

    /**
     * Remove the specified function (admin only).
     */
    public function destroy(Request $request, $id)
    {
        // Vérifier les permissions (admin seulement)
        $user = $request->user();
        if (!$user || $user->type_id !== 3) {
            return response()->json([
                'message' => 'Accès non autorisé. Admin seulement.'
            ], 403);
        }
        
        $fonction = Fonction::find($id);
        
        if (!$fonction) {
            return response()->json([
                'message' => 'Fonction non trouvée'
            ], 404);
        }
        
        // Vérifier si la fonction est utilisée par des contacts
        $contactsCount = DB::table('contact_pro')
            ->where('fonction_id', $id)
            ->count();
        
        if ($contactsCount > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cette fonction car elle est utilisée par ' . $contactsCount . ' contact(s)',
                'contacts_count' => $contactsCount
            ], 422);
        }
        
        // Supprimer la fonction
        $fonction->delete();
        
        return response()->json([
            'message' => 'Fonction supprimée avec succès'
        ], 200);
    }

    /**
     * Get functions with contact count (for statistics).
     */
    public function withStats()
    {
        $fonctions = Fonction::select('fonctions.*')
            ->addSelect(DB::raw('COUNT(contact_pro.id) as contacts_count'))
            ->leftJoin('contact_pro', 'fonctions.id', '=', 'contact_pro.fonction_id')
            ->groupBy('fonctions.id', 'fonctions.fonction', 'fonctions.created_at', 'fonctions.updated_at')
            ->orderBy('contacts_count', 'desc')
            ->orderBy('fonction', 'asc')
            ->get();
        
        return response()->json([
            'data' => $fonctions
        ]);
    }

    /**
     * Search functions by name.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return response()->json([
                'message' => 'Le paramètre de recherche est requis'
            ], 400);
        }
        
        $fonctions = Fonction::where('fonction', 'like', '%' . $query . '%')
            ->orderBy('fonction', 'asc')
            ->get();
        
        return response()->json([
            'data' => $fonctions,
            'count' => $fonctions->count(),
            'search_query' => $query
        ]);
    }

    /**
     * Get default functions (for seeding or initial setup).
     */
    public function defaults()
    {
        $defaultFunctions = [
            [
                'fonction' => 'Responsable logistique',
                'description' => 'Responsable de la logistique et des livraisons'
            ],
            [
                'fonction' => 'Directeur commercial',
                'description' => 'Responsable des ventes et de la relation client'
            ],
            [
                'fonction' => 'Gestionnaire de compte',
                'description' => 'Gère les comptes clients et les commandes'
            ],
            [
                'fonction' => 'Responsable événementiel',
                'description' => 'Organise et gère les événements'
            ],
            [
                'fonction' => 'Assistant administratif',
                'description' => 'Gère les tâches administratives'
            ],
            [
                'fonction' => 'Propriétaire',
                'description' => 'Propriétaire de l\'entreprise'
            ],
            [
                'fonction' => 'Gérant',
                'description' => 'Gérant de l\'entreprise'
            ],
            [
                'fonction' => 'Chef de projet',
                'description' => 'Gère les projets clients'
            ]
        ];
        
        return response()->json([
            'data' => $defaultFunctions,
            'message' => 'Liste des fonctions par défaut'
        ]);
    }
}