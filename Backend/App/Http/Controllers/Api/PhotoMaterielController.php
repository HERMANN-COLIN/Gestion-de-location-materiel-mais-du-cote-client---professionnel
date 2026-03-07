<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhotoMateriel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PhotoMaterielController extends Controller
{
    /**
     * Récupère les photos d'un matériel
     */
    public function getByMateriel($materielId)
    {
        try {
            $photos = PhotoMateriel::where('materiel_id', $materielId)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $photos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des photos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajoute une photo à un matériel (Admin)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'materiel_id' => 'required|exists:materiels,id',
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Upload de la photo
            $path = $request->file('photo')->store('photos/materiels', 'public');
            $urlPhoto = Storage::url($path);

            $photo = PhotoMateriel::create([
                'materiel_id' => $request->materiel_id,
                'url_photo' => $urlPhoto
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo ajoutée avec succès',
                'data' => $photo
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une photo (Admin)
     */
    public function update(Request $request, $id)
    {
        try {
            $photo = PhotoMateriel::find($id);

            if (!$photo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Photo non trouvée'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Supprimer l'ancienne photo
            $oldPath = str_replace('/storage/', '', $photo->url_photo);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            // Upload de la nouvelle photo
            $path = $request->file('photo')->store('photos/materiels', 'public');
            $urlPhoto = Storage::url($path);

            $photo->update([
                'url_photo' => $urlPhoto
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'data' => $photo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une photo (Admin)
     */
    public function destroy($id)
    {
        try {
            $photo = PhotoMateriel::find($id);

            if (!$photo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Photo non trouvée'
                ], 404);
            }

            // Vérifier si c'est la dernière photo du matériel
            $photoCount = PhotoMateriel::where('materiel_id', $photo->materiel_id)->count();
            if ($photoCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer la dernière photo du matériel'
                ], 422);
            }

            // Supprimer le fichier physique
            $path = str_replace('/storage/', '', $photo->url_photo);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            $photo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Photo supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}