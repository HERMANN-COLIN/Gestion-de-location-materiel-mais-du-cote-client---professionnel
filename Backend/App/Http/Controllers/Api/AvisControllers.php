<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AvisController extends Controller
{
    /**
     * POST /api/commandes/{id}/avis
     * Créer ou mettre à jour l'avis d'une commande.
     * (Un utilisateur ne peut laisser qu'un seul avis par commande.)
     */
    public function store(Request $request, $commandeId)
    {
        $user = $request->user();

        // Vérifier que la commande appartient à l'utilisateur
        $commande = Commande::where('id', $commandeId)
                            ->where('user_id', $user->id)
                            ->first();

        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable ou accès non autorisé'
            ], 404);
        }

        // La commande doit être terminée (statut 4) pour laisser un avis
        if ($commande->statut !== 4) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez laisser un avis que pour une commande terminée'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'note'        => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors()
            ], 422);
        }

        // updateOrCreate : un seul avis par commande/utilisateur
        $avis = Avis::updateOrCreate(
            [
                'commande_id' => $commandeId,
                'user_id'     => $user->id,
            ],
            [
                'note'        => $request->note,
                'commentaire' => $request->commentaire,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Avis enregistré avec succès',
            'data'    => $avis
        ], 201);
    }

    /**
     * GET /api/commandes/{id}/avis
     * Récupérer l'avis de l'utilisateur connecté pour une commande.
     */
    public function show(Request $request, $commandeId)
    {
        $user = $request->user();

        $avis = Avis::where('commande_id', $commandeId)
                    ->where('user_id', $user->id)
                    ->first();

        if (!$avis) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun avis trouvé pour cette commande'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $avis
        ]);
    }

    /**
     * DELETE /api/commandes/{id}/avis
     * Supprimer son avis.
     */
    public function destroy(Request $request, $commandeId)
    {
        $user = $request->user();

        $avis = Avis::where('commande_id', $commandeId)
                    ->where('user_id', $user->id)
                    ->first();

        if (!$avis) {
            return response()->json([
                'success' => false,
                'message' => 'Avis introuvable'
            ], 404);
        }

        $avis->delete();

        return response()->json([
            'success' => true,
            'message' => 'Avis supprimé'
        ]);
    }
}