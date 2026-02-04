<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Commande;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    /**
     * Liste des commandes de l'utilisateur connecté
     */
    public function index()
    {
        $user = Auth::user();

        $commandes = Commande::where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json($commandes, 200);
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        $request->validate([
            'total' => 'required|numeric|min:0',
            'status' => 'nullable|string',
        ]);

        $commande = Commande::create([
            'user_id' => Auth::id(),
            'total'   => $request->total,
            'status'  => $request->status ?? 'en_attente',
        ]);

        return response()->json([
            'message' => 'Commande créée avec succès',
            'commande' => $commande
        ], 201);
    }

    /**
     * Afficher une commande spécifique
     */
    public function show($id)
    {
        $commande = Commande::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$commande) {
            return response()->json([
                'message' => 'Commande introuvable'
            ], 404);
        }

        return response()->json($commande, 200);
    }

    /**
     * Supprimer une commande (optionnel)
     */
    public function destroy($id)
    {
        $commande = Commande::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$commande) {
            return response()->json([
                'message' => 'Commande introuvable'
            ], 404);
        }

        $commande->delete();

        return response()->json([
            'message' => 'Commande supprimée'
        ], 200);
    }
}
