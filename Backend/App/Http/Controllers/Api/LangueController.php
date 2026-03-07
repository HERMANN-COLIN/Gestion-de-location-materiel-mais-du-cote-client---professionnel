<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Langue;
use Illuminate\Http\Request;

class LangueController extends Controller
{
    /**
     * Liste toutes les langues disponibles
     */
    public function index()
    {
        try {
            $langues = Langue::orderBy('langue', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $langues
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des langues',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Afficher une langue spécifique
     */
    public function show($id)
    {
        try {
            $langue = Langue::find($id);
            
            if (!$langue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Langue non trouvée'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $langue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la langue',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}