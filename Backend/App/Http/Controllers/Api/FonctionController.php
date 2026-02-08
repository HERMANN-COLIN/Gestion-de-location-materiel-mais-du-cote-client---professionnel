<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fonction;
use Illuminate\Http\Request;

class FonctionController extends Controller
{
    public function index()
    {
        try {
            $fonctions = Fonction::orderBy('fonction', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $fonctions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des fonctions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}