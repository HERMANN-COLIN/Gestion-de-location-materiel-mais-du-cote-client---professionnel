<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Particulier;
use App\Models\Professionnel;
use App\Models\ContactPro;
use App\Models\Adresse;
use App\Models\Commune;
use App\Models\Langue;
use App\Models\Fonction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Log pour débogage
        \Log::info('=== DÉBUT REGISTER ===');
        \Log::info('Données reçues:', $request->all());

        try {
            // Validation de base commune
            $request->validate([
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|confirmed',
                'type_id' => 'required|in:1,2', // 1 = particulier, 2 = professionnel
                'langue_id' => 'required|exists:langues,id',
            ]);

            \Log::info('Validation de base passée');

            // Validation conditionnelle selon le type
            if ($request->type_id == 1) {
                // PARTICULIER
                $request->validate([
                    'nom' => 'required|string|max:255',
                    'prenom' => 'required|string|max:255',
                    'adresse' => 'required|string|max:500',
                ]);
                \Log::info('Validation particulier passée');
            } else {
                // PROFESSIONNEL
                $request->validate([
                    'nom_societe' => 'required|string|max:255',
                    'heure_ouverture' => 'required|date_format:H:i',
                    'heure_fermeture' => 'required|date_format:H:i',
                    
                    // Adresse du siège
                    'nom_rue_siege' => 'required|string|max:255',
                    'numero_rue_siege' => 'required|string|max:10',
                    'nom_commune_siege' => 'required|string|max:255',
                    'numero_commune_siege' => 'required|string|max:10',
                    
                    // Contact professionnel
                    'contact_nom' => 'required|string|max:100',
                    'contact_prenom' => 'required|string|max:100',
                    'contact_email' => 'required|email|max:255',
                    'contact_telephone' => 'required|string|max:20',
                    'contact_fonction_id' => 'required|exists:fonctions,id',
                ]);

                \Log::info('Validation professionnel passée');

                // Validation optionnelle pour l'adresse de livraison
                if ($request->has('has_different_delivery_address') && $request->has_different_delivery_address) {
                    $request->validate([
                        'nom_rue_livraison' => 'required|string|max:255',
                        'numero_rue_livraison' => 'required|string|max:10',
                        'nom_commune_livraison' => 'required|string|max:255',
                        'numero_commune_livraison' => 'required|string|max:10',
                    ]);
                    \Log::info('Validation adresse livraison passée');
                }
            }

            DB::beginTransaction();
            
            try {
                // Création de l'utilisateur
                $user = User::create([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'type_id' => $request->type_id,
                ]);

                \Log::info('Utilisateur créé', ['id' => $user->id, 'email' => $user->email]);

                // Traitement selon le type d'utilisateur
                if ($request->type_id == 1) {
                    $this->createParticulier($user, $request);
                } else {
                    $this->createProfessionnel($user, $request);
                }

                DB::commit();
                \Log::info('Transaction commitée');

                // Génération du token
                $token = $user->createToken('auth_token')->plainTextToken;

                // Chargement des relations
                $user->load(['type', 'particulier', 'professionnel.contactPro']);

                \Log::info('=== INSCRIPTION RÉUSSIE ===', ['user_id' => $user->id]);

                return response()->json([
                    'success' => true,
                    'token' => $token,
                    'user' => $user,
                    'message' => 'Inscription réussie'
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Erreur transaction', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'inscription',
                    'error' => $e->getMessage()
                ], 500);
            }

        } catch (ValidationException $e) {
            \Log::error('Erreur validation', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur générale', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée un profil particulier
     */
    private function createParticulier(User $user, Request $request)
    {
        \Log::info('Création particulier', ['user_id' => $user->id]);
        
        $particulier = Particulier::create([
            'user_id' => $user->id,
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'adresse' => $request->adresse,
            'langue_id' => $request->langue_id,
        ]);

        \Log::info('Particulier créé', ['id' => $particulier->id]);
        
        return $particulier;
    }

    /**
     * Crée un profil professionnel avec contact
     */
    private function createProfessionnel(User $user, Request $request)
    {
        \Log::info('Création professionnel', ['user_id' => $user->id]);
        
        // 1. Créer ou récupérer la commune du siège
        \Log::info('Recherche/creation commune siège', [
            'nom' => $request->nom_commune_siege,
            'numero' => $request->numero_commune_siege
        ]);
        
        $communeSiege = Commune::firstOrCreate([
            'nom_commune' => $request->nom_commune_siege,
            'numero_commune' => $request->numero_commune_siege,
        ]);

        \Log::info('Commune siège', ['id' => $communeSiege->id]);

        // 2. Créer l'adresse du siège
        $adresseSiege = Adresse::create([
            'nom_rue' => $request->nom_rue_siege,
            'numero_rue' => $request->numero_rue_siege,
            'commune_id' => $communeSiege->id,
        ]);

        \Log::info('Adresse siège créée', ['id' => $adresseSiege->id]);

        // 3. Déterminer l'adresse de livraison
        $adresseLivraison = $adresseSiege; // Par défaut
        
        if ($request->has('has_different_delivery_address') && 
            $request->has_different_delivery_address &&
            !empty($request->nom_rue_livraison) && 
            !empty($request->numero_rue_livraison) &&
            !empty($request->nom_commune_livraison) &&
            !empty($request->numero_commune_livraison)) {
            
            \Log::info('Adresse livraison différente détectée');
            
            // Créer ou récupérer la commune de livraison
            $communeLivraison = Commune::firstOrCreate([
                'nom_commune' => $request->nom_commune_livraison,
                'numero_commune' => $request->numero_commune_livraison,
            ]);
            
            // Créer une adresse de livraison distincte
            $adresseLivraison = Adresse::create([
                'nom_rue' => $request->nom_rue_livraison,
                'numero_rue' => $request->numero_rue_livraison,
                'commune_id' => $communeLivraison->id,
            ]);
            
            \Log::info('Adresse livraison créée', ['id' => $adresseLivraison->id]);
        } else {
            \Log::info('Même adresse pour livraison');
        }

        // 4. Créer le professionnel
        $professionnel = Professionnel::create([
            'user_id' => $user->id,
            'nom_societe' => $request->nom_societe,
            'adresse_siege_id' => $adresseSiege->id,
            'adresse_livraison_id' => $adresseLivraison->id,
            'heure_ouverture' => $request->heure_ouverture,
            'heure_fermeture' => $request->heure_fermeture,
            'langue_id' => $request->langue_id,
        ]);

        \Log::info('Professionnel créé', ['id' => $professionnel->id]);

        // 5. Créer le contact professionnel
        $contact = ContactPro::create([
            'professionnel_id' => $professionnel->id,
            'nom' => $request->contact_nom,
            'prenom' => $request->contact_prenom,
            'email' => $request->contact_email,
            'telephone' => $request->contact_telephone,
            'fonction_id' => $request->contact_fonction_id,
        ]);

        \Log::info('Contact pro créé', ['id' => $contact->id]);
        
        return $professionnel;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Charger les relations selon le type
        if ($user->type_id == 1) {
            $user->load(['type', 'particulier.langue']);
        } else {
            $user->load(['type', 'professionnel' => function($query) {
                $query->with(['adresseSiege.commune', 'adresseLivraison.commune', 'langue', 'contactPro.fonction']);
            }]);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }
        
        // Charger les relations selon le type
        if ($user->type_id == 1) {
            $user->load(['type', 'particulier.langue']);
        } else {
            $user->load(['type', 'professionnel' => function($query) {
                $query->with(['adresseSiege.commune', 'adresseLivraison.commune', 'langue', 'contactPro.fonction']);
            }]);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Route de test simple
     */
    public function test(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Route register fonctionne!',
            'data' => $request->all(),
            'timestamp' => now()
        ], 200);
    }
}