<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Particulier;
use App\Models\Professionnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends Controller
{
    /**
     * Récupérer le profil de l'utilisateur connecté
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();
            
            // Charger le type d'utilisateur
            $user->load('type');
            
            $profileData = [
                'id' => $user->id,
                'email' => $user->email,
                'type' => $user->type->type,
                'type_id' => $user->type_id,
                'created_at' => $user->created_at,
            ];
            
            // Charger les données spécifiques selon le type
            if ($user->type->type === 'particulier') {
                $particulier = Particulier::with(['langue', 'adresseLivraison.commune'])
                    ->where('user_id', $user->id)
                    ->first();

                if ($particulier) {
                    $profileData['nom'] = $particulier->nom;
                    $profileData['prenom'] = $particulier->prenom;
                    $profileData['langue_id'] = $particulier->langue_id;
                    $profileData['langue'] = $particulier->langue;

                    // Formater l'adresse si elle existe
                    if ($particulier->adresseLivraison) {
                        $profileData['adresse'] = $this->formatAdresse($particulier->adresseLivraison);
                        // Optionnellement, on peut aussi renvoyer les composants séparés
                        $profileData['adresse_details'] = [
                            'rue' => trim(($particulier->adresseLivraison->numero_rue ?? '') . ' ' . ($particulier->adresseLivraison->nom_rue ?? '')),
                            'code_postal' => $particulier->adresseLivraison->commune->code_postal ?? '',
                            'ville' => $particulier->adresseLivraison->commune->nom_commune ?? '',
                        ];
                    } else {
                        $profileData['adresse'] = '';
                        $profileData['adresse_details'] = null;
                    }
                }
            } else { // professionnel
                $professionnel = Professionnel::with([
                    'langue',
                    'adresseSiege.commune',
                    'adresseLivraison.commune',
                    'contactPro'
                ])->where('user_id', $user->id)->first();
                
                if ($professionnel) {
                    $profileData['nom_societe'] = $professionnel->nom_societe;
                    $profileData['telephone'] = $professionnel->telephone ?? '';
                    $profileData['heure_ouverture'] = $professionnel->heure_ouverture;
                    $profileData['heure_fermeture'] = $professionnel->heure_fermeture;
                    $profileData['langue_id'] = $professionnel->langue_id;
                    $profileData['langue'] = $professionnel->langue;
                    
                    // Adresse du siège
                    if ($professionnel->adresseSiege) {
                        $siege = $professionnel->adresseSiege;
                        $profileData['adresse_siege'] = $this->formatAdresse($siege);
                        $profileData['adresse_siege_details'] = [
                            'rue' => trim(($siege->numero_rue ?? '') . ' ' . ($siege->nom_rue ?? '')),
                            'code_postal' => $siege->commune->code_postal ?? '',
                            'ville' => $siege->commune->nom_commune ?? '',
                        ];
                    } else {
                        $profileData['adresse_siege'] = '';
                        $profileData['adresse_siege_details'] = null;
                    }
                    
                    // Adresse de livraison par défaut
                    if ($professionnel->adresseLivraison) {
                        $livraison = $professionnel->adresseLivraison;
                        $profileData['adresse_livraison_defaut'] = $this->formatAdresse($livraison);
                        $profileData['adresse_livraison_details'] = [
                            'rue' => trim(($livraison->numero_rue ?? '') . ' ' . ($livraison->nom_rue ?? '')),
                            'code_postal' => $livraison->commune->code_postal ?? '',
                            'ville' => $livraison->commune->nom_commune ?? '',
                        ];
                    } else {
                        $profileData['adresse_livraison_defaut'] = '';
                        $profileData['adresse_livraison_details'] = null;
                    }
                    
                    // Contact professionnel
                    if ($professionnel->contactPro) {
                        $profileData['contact'] = [
                            'nom' => $professionnel->contactPro->nom,
                            'prenom' => $professionnel->contactPro->prenom,
                            'telephone' => $professionnel->contactPro->telephone,
                            'email' => $professionnel->contactPro->email,
                        ];
                    } else {
                        $profileData['contact'] = null;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $profileData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur chargement profil: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mettre à jour le profil de l'utilisateur
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            $user->load('type');
            
            // Validation selon le type d'utilisateur
            if ($user->type->type === 'particulier') {
                $validator = Validator::make($request->all(), [
                    'nom' => 'required|string|max:255',
                    'prenom' => 'required|string|max:255',
                    'adresse' => 'required|string|max:500',
                    'langue_id' => 'required|exists:langues,id',
                ], [
                    'nom.required' => 'Le nom est requis',
                    'prenom.required' => 'Le prénom est requis',
                    'adresse.required' => 'L\'adresse est requise',
                    'langue_id.required' => 'La langue est requise',
                    'langue_id.exists' => 'La langue sélectionnée n\'existe pas',
                ]);
                
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur de validation',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                // Mettre à jour le particulier
                $particulier = Particulier::where('user_id', $user->id)->first();
                
                if (!$particulier) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Profil particulier introuvable'
                    ], 404);
                }
                
                $particulier->update([
                    'nom' => $request->nom,
                    'prenom' => $request->prenom,
                    'adresse' => $request->adresse,
                    'langue_id' => $request->langue_id,
                ]);
                
                $message = 'Profil mis à jour avec succès';
                
            } else { // professionnel
                $validator = Validator::make($request->all(), [
                    'nom_societe' => 'required|string|max:255',
                    'telephone' => 'nullable|string|max:20',
                    'heure_ouverture' => 'required|date_format:H:i',
                    'heure_fermeture' => 'required|date_format:H:i|after:heure_ouverture',
                    'langue_id' => 'required|exists:langues,id',
                ], [
                    'nom_societe.required' => 'Le nom de la société est requis',
                    'heure_ouverture.required' => 'L\'heure d\'ouverture est requise',
                    'heure_ouverture.date_format' => 'Format d\'heure invalide',
                    'heure_fermeture.required' => 'L\'heure de fermeture est requise',
                    'heure_fermeture.after' => 'L\'heure de fermeture doit être après l\'heure d\'ouverture',
                    'langue_id.required' => 'La langue est requise',
                    'langue_id.exists' => 'La langue sélectionnée n\'existe pas',
                ]);
                
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur de validation',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                // Mettre à jour le professionnel
                $professionnel = Professionnel::where('user_id', $user->id)->first();
                
                if (!$professionnel) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Profil professionnel introuvable'
                    ], 404);
                }
                
                $professionnel->update([
                    'nom_societe' => $request->nom_societe,
                    'telephone' => $request->telephone,
                    'heure_ouverture' => $request->heure_ouverture,
                    'heure_fermeture' => $request->heure_fermeture,
                    'langue_id' => $request->langue_id,
                ]);
                
                $message = 'Profil mis à jour avec succès';
            }
            
            // Recharger les données pour la réponse
            return $this->show($request);
            
        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour profil: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                ],
            ], [
                'current_password.required' => 'Le mot de passe actuel est requis',
                'new_password.required' => 'Le nouveau mot de passe est requis',
                'new_password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
                'new_password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = $request->user();
            
            // Vérifier le mot de passe actuel
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect',
                    'errors' => [
                        'current_password' => ['Le mot de passe actuel est incorrect']
                    ]
                ], 422);
            }
            
            // Vérifier que le nouveau mot de passe est différent
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le nouveau mot de passe doit être différent de l\'ancien',
                    'errors' => [
                        'new_password' => ['Le nouveau mot de passe doit être différent de l\'ancien']
                    ]
                ], 422);
            }
            
            // Mettre à jour le mot de passe
            $user->password = Hash::make($request->new_password);
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour mot de passe: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du mot de passe',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Supprimer le compte utilisateur
     */
    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
                'confirmation' => 'required|in:DELETE',
            ], [
                'password.required' => 'Le mot de passe est requis pour supprimer le compte',
                'confirmation.required' => 'Veuillez confirmer la suppression',
                'confirmation.in' => 'Confirmation invalide',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = $request->user();
            
            // Vérifier le mot de passe
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mot de passe incorrect',
                    'errors' => [
                        'password' => ['Le mot de passe est incorrect']
                    ]
                ], 422);
            }
            
            // Vérifier s'il y a des commandes en cours
            $commandesEnCours = $user->commandes()
                ->whereIn('statut', [1, 2, 3]) // En attente, confirmée, en préparation
                ->count();
            
            if ($commandesEnCours > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer le compte',
                    'error' => 'Vous avez des commandes en cours. Veuillez d\'abord les finaliser ou les annuler.'
                ], 422);
            }
            
            // Vérifier les commandes livrées mais non retournées
            $commandesNonRetournees = $user->commandes()
                ->where('statut', 4) // Livrée
                ->count();
            
            if ($commandesNonRetournees > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer le compte',
                    'error' => 'Vous avez des commandes livrées en attente de retour.'
                ], 422);
            }
            
            // Révoquer tous les tokens
            $user->tokens()->delete();
            
            // Supprimer l'utilisateur (la suppression en cascade supprimera le particulier/professionnel)
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Compte supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur suppression compte: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupérer les statistiques du profil
     */
    public function statistics(Request $request)
    {
        try {
            $user = $request->user();
            
            $commandes = $user->commandes();
            
            $stats = [
                'total_commandes' => $commandes->count(),
                'commandes_en_cours' => $commandes->whereIn('statut', [1, 2, 3])->count(),
                'commandes_terminees' => $commandes->where('statut', 4)->count(),
                'commandes_retournees' => $commandes->where('statut', 6)->count(),
                'commandes_annulees' => $commandes->where('statut', 5)->count(),
                'montant_total_depense' => $commandes
                    ->whereNotIn('statut', [5]) // Exclure les annulées
                    ->sum('montant_total'),
                'derniere_commande' => $commandes
                    ->latest()
                    ->first()
                    ?->created_at
                    ?->format('Y-m-d H:i:s'),
                'moyenne_commandes' => $commandes
                    ->whereNotIn('statut', [5])
                    ->avg('montant_total'),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur chargement statistiques: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Formater une adresse pour l'affichage
     */
    private function formatAdresse($adresse)
    {
        if (!$adresse) return '';
        
        $parts = [];
        
        if (!empty($adresse->numero_rue)) {
            $parts[] = $adresse->numero_rue;
        }
        
        if (!empty($adresse->nom_rue)) {
            $parts[] = $adresse->nom_rue;
        }
        
        $rue = implode(' ', $parts);
        
        if ($adresse->commune) {
            $cp = $adresse->commune->code_postal ?? '';
            $ville = $adresse->commune->nom_commune ?? '';
            
            if (!empty($cp) && !empty($ville)) {
                return trim($rue . ', ' . $cp . ' ' . $ville);
            } elseif (!empty($ville)) {
                return trim($rue . ', ' . $ville);
            }
        }
        
        return trim($rue);
    }
}