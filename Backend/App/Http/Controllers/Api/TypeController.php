<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    /**
     * Liste tous les types d'utilisateurs
     */
    public function index()
    {
        try {
            $types = Type::orderBy('id', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche un type spécifique
     */
    public function show($id)
    {
        try {
            $type = Type::find($id);

            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}