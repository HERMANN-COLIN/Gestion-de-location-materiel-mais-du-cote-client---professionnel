<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactPro;
use App\Models\Professionnel;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContactProController extends Controller
{
    /**
     * Display a listing of contacts for a professional.
     */
    public function index(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = $request->user();
        
        // Vérifier que c'est un professionnel
        if ($user->type_id !== 2) {
            return response()->json([
                'message' => 'Accès réservé aux professionnels'
            ], 403);
        }
        
        // Récupérer le professionnel
        $professionnel = $user->professionnel;
        
        if (!$professionnel) {
            return response()->json([
                'message' => 'Profil professionnel non trouvé'
            ], 404);
        }
        
        // Récupérer tous les contacts avec leurs fonctions
        $contacts = ContactPro::where('professionnel_id', $professionnel->id)
            ->with('fonction')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'data' => $contacts,
            'count' => $contacts->count()
        ]);
    }

    /**
     * Store a newly created contact.
     */
    public function store(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = $request->user();
        
        // Vérifier que c'est un professionnel
        if ($user->type_id !== 2) {
            return response()->json([
                'message' => 'Accès réservé aux professionnels'
            ], 403);
        }
        
        // Récupérer le professionnel
        $professionnel = $user->professionnel;
        
        if (!$professionnel) {
            return response()->json([
                'message' => 'Profil professionnel non trouvé'
            ], 404);
        }
        
        // Validation des données
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'telephone' => 'required|string|max:20',
            'fonction_id' => 'required|exists:fonctions,id',
        ]);
        
        // Vérifier que l'email n'existe pas déjà pour ce professionnel
        $existingContact = ContactPro::where('professionnel_id', $professionnel->id)
            ->where('email', $validated['email'])
            ->first();
            
        if ($existingContact) {
            throw ValidationException::withMessages([
                'email' => ['Cet email est déjà utilisé pour un contact de votre entreprise']
            ]);
        }
        
        // Créer le contact
        $contact = ContactPro::create([
            'professionnel_id' => $professionnel->id,
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'],
            'fonction_id' => $validated['fonction_id'],
        ]);
        
        // Charger la relation fonction
        $contact->load('fonction');
        
        return response()->json([
            'message' => 'Contact créé avec succès',
            'data' => $contact
        ], 201);
    }

    /**
     * Display the specified contact.
     */
    public function show(Request $request, $id)
    {
        // Récupérer l'utilisateur connecté
        $user = $request->user();
        
        // Vérifier que c'est un professionnel
        if ($user->type_id !== 2) {
            return response()->json([
                'message' => 'Accès réservé aux professionnels'
            ], 403);
        }
        
        // Récupérer le professionnel
        $professionnel = $user->professionnel;
        
        if (!$professionnel) {
            return response()->json([
                'message' => 'Profil professionnel non trouvé'
            ], 404);
        }
        
        // Récupérer le contact avec vérification de propriété
        $contact = ContactPro::where('professionnel_id', $professionnel->id)
            ->where('id', $id)
            ->with('fonction')
            ->first();
        
        if (!$contact) {
            return response()->json([
                'message' => 'Contact non trouvé ou accès non autorisé'
            ], 404);
        }
        
        return response()->json([
            'data' => $contact
        ]);
    }

    /**
     * Update the specified contact.
     */
    public function update(Request $request, $id)
    {
        // Récupérer l'utilisateur connecté
        $user = $request->user();
        
        // Vérifier que c'est un professionnel
        if ($user->type_id !== 2) {
            return response()->json([
                'message' => 'Accès réservé aux professionnels'
            ], 403);
        }
        
        // Récupérer le professionnel
        $professionnel = $user->professionnel;
        
        if (!$professionnel) {
            return response()->json([
                'message' => 'Profil professionnel non trouvé'
            ], 404);
        }
        
        // Récupérer le contact avec vérification de propriété
        $contact = ContactPro::where('professionnel_id', $professionnel->id)
            ->where('id', $id)
            ->first();
        
        if (!$contact) {
            return response()->json([
                'message' => 'Contact non trouvé ou accès non autorisé'
            ], 404);
        }
        
        // Validation des données
        $validated = $request->validate([
            'nom' => 'string|max:100',
            'prenom' => 'string|max:100',
            'email' => 'email|max:255',
            'telephone' => 'string|max:20',
            'fonction_id' => 'exists:fonctions,id',
        ]);
        
        // Vérifier que l'email n'existe pas déjà pour un autre contact de ce professionnel
        if (isset($validated['email']) && $validated['email'] !== $contact->email) {
            $existingContact = ContactPro::where('professionnel_id', $professionnel->id)
                ->where('email', $validated['email'])
                ->where('id', '!=', $id)
                ->first();
                
            if ($existingContact) {
                throw ValidationException::withMessages([
                    'email' => ['Cet email est déjà utilisé pour un autre contact de votre entreprise']
                ]);
            }
        }
        
        // Mettre à jour le contact
        $contact->update($validated);
        
        // Recharger les relations
        $contact->load('fonction');
        
        return response()->json([
            'message' => 'Contact mis à jour avec succès',
            'data' => $contact
        ]);
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(Request $request, $id)
    {
        // Récupérer l'utilisateur connecté
        $user = $request->user();
        
        // Vérifier que c'est un professionnel
        if ($user->type_id !== 2) {
            return response()->json([
                'message' => 'Accès réservé aux professionnels'
            ], 403);
        }
        
        // Récupérer le professionnel
        $professionnel = $user->professionnel;
        
        if (!$professionnel) {
            return response()->json([
                'message' => 'Profil professionnel non trouvé'
            ], 404);
        }
        
        // Récupérer le contact avec vérification de propriété
        $contact = ContactPro::where('professionnel_id', $professionnel->id)
            ->where('id', $id)
            ->first();
        
        if (!$contact) {
            return response()->json([
                'message' => 'Contact non trouvé ou accès non autorisé'
            ], 404);
        }
        
        // Empêcher la suppression du contact principal (le premier créé)
        $firstContact = ContactPro::where('professionnel_id', $professionnel->id)
            ->orderBy('created_at', 'asc')
            ->first();
            
        if ($contact->id === $firstContact->id) {
            return response()->json([
                'message' => 'Impossible de supprimer le contact principal'
            ], 422);
        }
        
        // Supprimer le contact
        $contact->delete();
        
        return response()->json([
            'message' => 'Contact supprimé avec succès'
        ], 200);
    }

    /**
     * Set a contact as primary (main contact).
     */
    public function setAsPrimary(Request $request, $id)
    {
        // Récupérer l'utilisateur connecté
        $user = $request->user();
        
        // Vérifier que c'est un professionnel
        if ($user->type_id !== 2) {
            return response()->json([
                'message' => 'Accès réservé aux professionnels'
            ], 403);
        }
        
        // Récupérer le professionnel
        $professionnel = $user->professionnel;
        
        if (!$professionnel) {
            return response()->json([
                'message' => 'Profil professionnel non trouvé'
            ], 404);
        }
        
        // Récupérer le contact avec vérification de propriété
        $contact = ContactPro::where('professionnel_id', $professionnel->id)
            ->where('id', $id)
            ->first();
        
        if (!$contact) {
            return response()->json([
                'message' => 'Contact non trouvé ou accès non autorisé'
            ], 404);
        }
        
        // Note: Dans la structure actuelle, il n'y a pas de champ "primary"
        // Tu pourrais ajouter un champ boolean "est_principal" dans la table contact_pro
        // Pour l'instant, on ne fait que renvoyer une confirmation
        
        return response()->json([
            'message' => 'Cette fonctionnalité nécessiterait l\'ajout d\'un champ dans la base de données',
            'data' => $contact
        ]);
    }
}