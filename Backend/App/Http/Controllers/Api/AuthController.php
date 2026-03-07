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
        \Log::info('=== DÉBUT REGISTER ===');
        \Log::info('Données reçues:', $request->all());

        try {
            $request->validate([
                'email'     => 'required|email|unique:users',
                'password'  => 'required|min:8|confirmed',
                'type_id'   => 'required|in:1,2',
                'langue_id' => 'required|exists:langues,id',
            ]);

            if ($request->type_id == 1) {
                $request->validate([
                    'nom'         => 'required|string|max:255',
                    'prenom'      => 'required|string|max:255',
                    'nom_rue'     => 'required|string|max:255',
                    'numero_rue'  => 'required|string|max:10',
                    'nom_commune' => 'required|string|max:255',
                    'code_postal' => 'required|string|max:10',
                ]);
            } else {
                $request->validate([
                    'nom_societe'         => 'required|string|max:255',
                    'heure_ouverture'     => 'required|date_format:H:i',
                    'heure_fermeture'     => 'required|date_format:H:i',
                    'nom_rue_siege'       => 'required|string|max:255',
                    'numero_rue_siege'    => 'required|string|max:10',
                    'nom_commune_siege'   => 'required|string|max:255',
                    'code_postal_siege'   => 'required|string|max:10',
                    'contact_nom'         => 'required|string|max:100',
                    'contact_prenom'      => 'required|string|max:100',
                    'contact_email'       => 'required|email|max:255',
                    'contact_telephone'   => 'required|string|max:20',
                    'contact_fonction_id' => 'required|exists:fonctions,id',
                ]);

                if ($request->has('has_different_delivery_address') && $request->has_different_delivery_address) {
                    $request->validate([
                        'nom_rue_livraison'     => 'required|string|max:255',
                        'numero_rue_livraison'  => 'required|string|max:10',
                        'nom_commune_livraison' => 'required|string|max:255',
                        'code_postal_livraison' => 'required|string|max:10',
                    ]);
                }
            }

            DB::beginTransaction();

            try {
                $user = User::create([
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'type_id'  => $request->type_id,
                ]);

                if ($request->type_id == 1) {
                    $this->createParticulier($user, $request);
                } else {
                    $this->createProfessionnel($user, $request);
                }

                DB::commit();

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'token'   => $token,
                    'data'    => $this->buildProfileData($user),
                    'message' => 'Inscription réussie'
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Erreur transaction register', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return response()->json(['success' => false, 'message' => "Erreur lors de l'inscription", 'error' => $e->getMessage()], 500);
            }

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // ✅ buildProfileData() charge les relations en interne
        //    Structure plate { data: { type, email, nom/nom_societe, ... } }
        //    attendue par le store Pinia (auth.js)
        return response()->json([
            'success' => true,
            'token'   => $token,
            'data'    => $this->buildProfileData($user),
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

    /**
     * GET /api/user
     *
     * AVANT : renvoyait { success, user: { particulier: { nom, prenom } } }
     *         ProfileView lisait response.data.data.nom → undefined
     *
     * APRÈS : renvoie  { success, data: { type, email, nom, prenom, adresse, ... } }
     *         ProfileView lit response.data.data.nom → ✅
     */
    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur non authentifié'], 401);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->buildProfileData($user),
        ]);
    }

    // =========================================================================
    // buildProfileData — structure plate attendue par ProfileView.vue
    // =========================================================================

    /**
     * Retourne un tableau plat avec toutes les infos du profil.
     *
     * Particulier  → { id, email, type, nom, prenom, adresse, langue_id, langue }
     * Professionnel→ { id, email, type, nom_societe, telephone, heure_ouverture,
     *                  heure_fermeture, adresse_siege, adresse_livraison_defaut,
     *                  langue_id, langue, contact:{nom,prenom,telephone,email} }
     */
    private function buildProfileData(User $user): array
    {
        $user->load('type');

        $data = [
            'id'         => $user->id,
            'email'      => $user->email,
            'type'       => $user->type?->type ?? 'particulier',
            'type_id'    => $user->type_id,
            'created_at' => $user->created_at,
        ];

        if (($user->type?->type ?? 'particulier') === 'particulier') {

            $particulier = Particulier::with(['langue', 'adresseLivraison.commune'])
                ->where('user_id', $user->id)
                ->first();

            if ($particulier) {
                $data['nom']       = $particulier->nom       ?? '';
                $data['prenom']    = $particulier->prenom    ?? '';
                $data['langue_id'] = $particulier->langue_id ?? '';
                $data['langue']    = $particulier->langue;
                $data['adresse']   = $this->formatAdresse($particulier->adresseLivraison);
            }

        } else {

            $professionnel = Professionnel::with([
                'langue',
                'adresseSiege.commune',
                'adresseLivraison.commune',
                'contactPro.fonction',
            ])->where('user_id', $user->id)->first();

            if ($professionnel) {
                $data['nom_societe']              = $professionnel->nom_societe     ?? '';
                $data['telephone']                = $professionnel->telephone       ?? '';
                $data['heure_ouverture']          = $professionnel->heure_ouverture ?? '';
                $data['heure_fermeture']          = $professionnel->heure_fermeture ?? '';
                $data['langue_id']                = $professionnel->langue_id       ?? '';
                $data['langue']                   = $professionnel->langue;
                $data['adresse_siege']            = $this->formatAdresse($professionnel->adresseSiege);
                $data['adresse_livraison_defaut'] = $this->formatAdresse($professionnel->adresseLivraison);

                if ($professionnel->contactPro) {
                    $data['contact'] = [
                        'nom'       => $professionnel->contactPro->nom,
                        'prenom'    => $professionnel->contactPro->prenom,
                        'telephone' => $professionnel->contactPro->telephone,
                        'email'     => $professionnel->contactPro->email,
                    ];
                }
            }
        }

        return $data;
    }

    private function formatAdresse($adresse): string
    {
        if (!$adresse) return '';
        $rue = trim(($adresse->numero_rue ?? '') . ' ' . ($adresse->nom_rue ?? ''));
        if ($adresse->commune) {
            $cp    = $adresse->commune->code_postal ?? '';
            $ville = $adresse->commune->nom_commune ?? '';
            if ($cp && $ville) return trim("$rue, $cp $ville");
            if ($ville)        return trim("$rue, $ville");
        }
        return $rue;
    }

    // =========================================================================
    // Création des profils (logique inchangée)
    // =========================================================================

    private function createParticulier(User $user, Request $request)
    {
        \Log::info('Création particulier', ['user_id' => $user->id]);

        $commune = Commune::firstOrCreate([
            'nom_commune' => $request->nom_commune,
            'code_postal' => $request->code_postal,
        ]);

        $adresse = Adresse::create([
            'nom_rue'    => $request->nom_rue,
            'numero_rue' => $request->numero_rue,
            'commune_id' => $commune->id,
        ]);

        $particulier = Particulier::create([
            'user_id'              => $user->id,
            'nom'                  => $request->nom,
            'prenom'               => $request->prenom,
            'langue_id'            => $request->langue_id,
            'adresse_livraison_id' => $adresse->id,
        ]);

        \Log::info('Particulier créé', ['id' => $particulier->id]);
        return $particulier;
    }

    private function createProfessionnel(User $user, Request $request)
    {
        \Log::info('Création professionnel', ['user_id' => $user->id]);

        $communeSiege = Commune::firstOrCreate([
            'nom_commune' => $request->nom_commune_siege,
            'code_postal' => $request->code_postal_siege,
        ]);

        $adresseSiege = Adresse::create([
            'nom_rue'    => $request->nom_rue_siege,
            'numero_rue' => $request->numero_rue_siege,
            'commune_id' => $communeSiege->id,
        ]);

        $adresseLivraison = $adresseSiege;

        if ($request->has('has_different_delivery_address') &&
            $request->has_different_delivery_address &&
            !empty($request->nom_rue_livraison)) {

            $communeLivraison = Commune::firstOrCreate([
                'nom_commune' => $request->nom_commune_livraison,
                'code_postal' => $request->code_postal_livraison,
            ]);

            $adresseLivraison = Adresse::create([
                'nom_rue'    => $request->nom_rue_livraison,
                'numero_rue' => $request->numero_rue_livraison,
                'commune_id' => $communeLivraison->id,
            ]);
        }

        $professionnel = Professionnel::create([
            'user_id'              => $user->id,
            'nom_societe'          => $request->nom_societe,
            'adresse_siege_id'     => $adresseSiege->id,
            'adresse_livraison_id' => $adresseLivraison->id,
            'heure_ouverture'      => $request->heure_ouverture,
            'heure_fermeture'      => $request->heure_fermeture,
            'langue_id'            => $request->langue_id,
        ]);

        ContactPro::create([
            'professionnel_id' => $professionnel->id,
            'nom'              => $request->contact_nom,
            'prenom'           => $request->contact_prenom,
            'email'            => $request->contact_email,
            'telephone'        => $request->contact_telephone,
            'fonction_id'      => $request->contact_fonction_id,
        ]);

        \Log::info('Professionnel créé', ['id' => $professionnel->id]);
        return $professionnel;
    }

    /**
     * PUT /api/user/profile
     * Met à jour le profil de l'utilisateur connecté.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $user->load('type');
        $type = $user->type?->type ?? 'particulier';

        DB::beginTransaction();
        try {
            // ── Mise à jour email / mot de passe ────────────────────────────
            if ($request->filled('email') && $request->email !== $user->email) {
                $request->validate(['email' => 'required|email|unique:users,email,' . $user->id]);
                $user->email = $request->email;
            }

            if ($request->filled('password')) {
                $request->validate(['password' => 'required|min:8|confirmed']);
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // ── Mise à jour profil selon le type ────────────────────────────
            if ($type === 'particulier') {
                $particulier = Particulier::where('user_id', $user->id)->first();
                if ($particulier) {
                    if ($request->filled('nom'))       $particulier->nom       = $request->nom;
                    if ($request->filled('prenom'))    $particulier->prenom    = $request->prenom;
                    if ($request->filled('langue_id')) $particulier->langue_id = $request->langue_id;
                    $particulier->save();

                    // Mise à jour adresse
                    if ($request->filled('nom_rue') || $request->filled('numero_rue') ||
                        $request->filled('nom_commune') || $request->filled('code_postal')) {

                        $adresse = Adresse::find($particulier->adresse_livraison_id);
                        if ($adresse) {
                            if ($request->filled('nom_rue'))    $adresse->nom_rue    = $request->nom_rue;
                            if ($request->filled('numero_rue')) $adresse->numero_rue = $request->numero_rue;

                            if ($request->filled('nom_commune') || $request->filled('code_postal')) {
                                $commune = Commune::firstOrCreate([
                                    'nom_commune' => $request->nom_commune ?? $adresse->commune->nom_commune ?? '',
                                    'code_postal' => $request->code_postal ?? $adresse->commune->code_postal ?? '',
                                ]);
                                $adresse->commune_id = $commune->id;
                            }
                            $adresse->save();
                        }
                    }
                }
            } else {
                $professionnel = Professionnel::where('user_id', $user->id)->first();
                if ($professionnel) {
                    if ($request->filled('nom_societe'))    $professionnel->nom_societe    = $request->nom_societe;
                    if ($request->filled('telephone'))      $professionnel->telephone      = $request->telephone;
                    if ($request->filled('heure_ouverture'))$professionnel->heure_ouverture= $request->heure_ouverture;
                    if ($request->filled('heure_fermeture'))$professionnel->heure_fermeture= $request->heure_fermeture;
                    if ($request->filled('langue_id'))      $professionnel->langue_id      = $request->langue_id;
                    $professionnel->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data'    => $this->buildProfileData($user),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur updateProfile', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

}