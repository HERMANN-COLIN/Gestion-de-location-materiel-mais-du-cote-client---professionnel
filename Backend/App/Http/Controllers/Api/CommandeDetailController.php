<?php
// app/Http/Controllers/Api/CommandeDetailController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Materiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CommandeDetailController extends Controller
{
    /**
     * Afficher les détails d'une commande
     */
    public function show($id)
    {
        try {
            $user = request()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Charger la commande avec toutes ses relations
            $commande = Commande::with([
                'user',
                'user.particulier',
                'user.professionnel',
                'user.professionnel.contactPro',
                'user.type', // ✅ Important pour le type de client
                'materiels',
                'adresseLivraison',
                'adresseLivraison.commune',
                'modeLivraison',
                'modeRetour',
                'codeReduction',
                'statutCommande'
            ])->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            // Vérifier que l'utilisateur a le droit de voir cette commande
            if ($user->id !== $commande->user_id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Formater les données pour le frontend
            $commandeFormatee = $this->formatCommandeDetails($commande);

            return response()->json([
                'success' => true,
                'data' => $commandeFormatee
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur chargement détail commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des détails de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater les détails de la commande
     */
   private function formatCommandeDetails($commande)
{
    // ✅ 1. INFORMATIONS CLIENT (corrigé)
    $client = [
        'id' => $commande->user->id,
        'email' => $commande->user->email,
        'type' => 'particulier', // Valeur par défaut
        'nom_complet' => $commande->user->email,
        'telephone' => null
    ];

    // Vérifier si l'utilisateur a un type
    if ($commande->user->type) {
        $client['type'] = $commande->user->type->type ?? 'particulier';
    }

    // Formatage selon le type de client
    if ($client['type'] === 'particulier' && $commande->user->particulier) {
        $particulier = $commande->user->particulier;
        $client['nom'] = $particulier->nom ?? '';
        $client['prenom'] = $particulier->prenom ?? '';
        
        // Construire le nom complet
        $prenom = $particulier->prenom ?? '';
        $nom = $particulier->nom ?? '';
        $nomComplet = trim($prenom . ' ' . $nom);
        
        if (!empty($nomComplet)) {
            $client['nom_complet'] = $nomComplet;
        }
        
        // Récupérer le téléphone si disponible
        $client['telephone'] = $particulier->telephone ?? null;
    } 
    elseif ($client['type'] === 'professionnel' && $commande->user->professionnel) {
        $pro = $commande->user->professionnel;
        $client['nom_societe'] = $pro->nom_societe ?? '';
        
        if (!empty($pro->nom_societe)) {
            $client['nom_complet'] = $pro->nom_societe;
        }
        
        // Téléphone du professionnel
        $client['telephone'] = $pro->telephone ?? null;
        
        // Informations de contact
        if ($pro->contactPro) {
            $client['contact_nom'] = trim(($pro->contactPro->prenom ?? '') . ' ' . ($pro->contactPro->nom ?? ''));
            $client['contact_email'] = $pro->contactPro->email ?? '';
            $client['contact_telephone'] = $pro->contactPro->telephone ?? '';
        }
    }

    // ✅ 2. ADRESSE DE LIVRAISON
    $adresseLivraison = [
        'adresse_complete' => $commande->adresse_livraison ?? 'Retrait sur place'
    ];

    if ($commande->adresseLivraison && $commande->adresseLivraison->commune) {
        $rue = trim(($commande->adresseLivraison->numero_rue ?? '') . ' ' . ($commande->adresseLivraison->nom_rue ?? ''));
        $codePostal = $commande->adresseLivraison->commune->code_postal ?? '';
        $ville = $commande->adresseLivraison->commune->nom_commune ?? '';
        
        if (!empty($rue) && !empty($codePostal) && !empty($ville)) {
            $adresseComplete = $rue . ', ' . $codePostal . ' ' . $ville;
            $adresseLivraison = [
                'adresse_complete' => $adresseComplete,
                'rue' => $rue,
                'code_postal' => $codePostal,
                'ville' => $ville
            ];
        }
    }

    // ✅ 3. ARTICLES COMMANDÉS
    $articles = [];
    $totalHT = 0;
    $totalTVA = 0;
    $totalTTC = 0;

    foreach ($commande->materiels as $materiel) {
        // Récupérer la photo principale
        $photo = null;
        if ($materiel->photos && $materiel->photos->isNotEmpty()) {
            $photo = $this->formatImageUrl($materiel->photos->first()->url_photo);
        }
        
        $articleTotalHT = floatval($materiel->pivot->sous_total_ht ?? 0);
        $articleTotalTVA = floatval($materiel->pivot->montant_tva ?? 0);
        $articleTotalTTC = floatval($materiel->pivot->sous_total_ttc ?? 0);
        
        $articles[] = [
            'id' => $materiel->id,
            'nom' => $materiel->nom,
            'description' => $materiel->description,
            'photo' => $photo,
            'photos' => $materiel->photos,
            'quantite' => $materiel->pivot->quantite ?? 0,
            'prix_unitaire_ht' => floatval($materiel->pivot->prix_unitaire_ht ?? 0),
            'taux_tva' => floatval($materiel->pivot->taux_tva ?? 21.00),
            'prix_unitaire_ttc' => floatval($materiel->pivot->prix_unitaire_ttc ?? 0),
            'sous_total_ht' => $articleTotalHT,
            'sous_total_ttc' => $articleTotalTTC,
            'montant_tva' => $articleTotalTVA,
        ];

        $totalHT += $articleTotalHT;
        $totalTVA += $articleTotalTVA;
        $totalTTC += $articleTotalTTC;
    }

    // ✅ 4. LIBELLÉS DES MODES
    $modeLivraisonLibelle = 'Sur place';
    $modeRetourLibelle = 'Sur place';
    
    if ($commande->modeLivraison) {
        $modeLivraisonLibelle = $commande->modeLivraison->livraison ?? 'Sur place';
    }
    
    if ($commande->modeRetour) {
        $modeRetourLibelle = $commande->modeRetour->retour ?? 'Sur place';
    }

    // ✅ 5. LIBELLÉ DU STATUT
    $statutLibelle = 'Inconnu';
    if ($commande->statutCommande) {
        $statutLibelle = $commande->statutCommande->libelle ?? 'Inconnu';
    }

    // ✅ 6. CALCUL DE LA DURÉE
    $duree = 0;
    $dateDebutFormatted = '';
    $dateFinFormatted = '';
    
    if ($commande->date_debut && $commande->date_fin) {
        $debut = Carbon::parse($commande->date_debut);
        $fin = Carbon::parse($commande->date_fin);
        $duree = $debut->diffInDays($fin) + 1;
        $dateDebutFormatted = $debut->format('d/m/Y');
        $dateFinFormatted = $fin->format('d/m/Y');
    }

    // ✅ 7. FRAIS DE LIVRAISON ET RETOUR
    $fraisLivraison = floatval($commande->frais_livraison ?? 0);
    $fraisRetour = floatval($commande->frais_retour ?? 0);

    // ✅ 8. CODE RÉDUCTION
    $remise = 0;
    if ($commande->codeReduction) {
        if ($commande->codeReduction->type_reduction == 1) {
            // Montant fixe
            $remise = floatval($commande->codeReduction->montant);
        } else {
            // Pourcentage
            $pourcentage = floatval($commande->codeReduction->montant);
            $remise = ($totalTTC * $pourcentage) / 100;
        }
    }

    // ✅ 9. TOTAL TTC FINAL
    $totalTTCFinal = $totalTTC + $fraisLivraison + $fraisRetour - $remise;
    
    // Mettre à jour le montant_total si différent
    if (abs($commande->montant_total - $totalTTCFinal) > 0.01) {
        \Log::warning('Différence de total détectée', [
            'commande_id' => $commande->id,
            'montant_bdd' => $commande->montant_total,
            'montant_calcule' => $totalTTCFinal
        ]);
    }

    \Log::info('Détails commande formatés', [
        'commande_id' => $commande->id,
        'total_ht' => $totalHT,
        'total_tva' => $totalTVA,
        'total_ttc_articles' => $totalTTC,
        'frais_livraison' => $fraisLivraison,
        'frais_retour' => $fraisRetour,
        'remise' => $remise,
        'total_final' => $totalTTCFinal,
        'client_type' => $client['type'],
        'client_nom' => $client['nom_complet']
    ]);

    return [
        'id' => $commande->id,
        'numero_commande' => $commande->numero_commande,
        'date_commande' => $commande->date_commande ? $commande->date_commande->format('d/m/Y H:i') : '',
        'date_commande_raw' => $commande->date_commande ? $commande->date_commande->toISOString() : null,
        'date_debut' => $dateDebutFormatted,
        'date_debut_raw' => $commande->date_debut ? $commande->date_debut->format('Y-m-d') : null,
        'date_fin' => $dateFinFormatted,
        'date_fin_raw' => $commande->date_fin ? $commande->date_fin->format('Y-m-d') : null,
        'duree' => $duree . ' jour' . ($duree > 1 ? 's' : ''),
        'duree_nombre' => $duree,
        'statut' => $commande->statut,
        'statut_libelle' => $statutLibelle,
        'statut_couleur' => $this->getStatutCouleur($commande->statut),
        'client' => $client,
        'adresse_livraison' => $adresseLivraison,
        'mode_livraison' => $commande->mode_livraison,
        'mode_livraison_libelle' => $modeLivraisonLibelle,
        'mode_retour' => $commande->mode_retour,
        'mode_retour_libelle' => $modeRetourLibelle,
        'frais_livraison' => $fraisLivraison,
        'frais_retour' => $fraisRetour,
        'details_livraison' => $commande->mode_livraison == 2 ? [
            'jour' => $commande->jour_livraison,
            'distance' => $commande->distance_livraison,
            'frais' => $fraisLivraison
        ] : null,
        'details_retour' => $commande->mode_retour == 2 ? [
            'jour' => $commande->jour_retour,
            'distance' => $commande->distance_retour,
            'frais' => $fraisRetour
        ] : null,
        'articles' => $articles,
        'totaux' => [
            'sous_total_ht' => $totalHT,
            'total_tva' => $totalTVA,
            'sous_total_ttc' => $totalTTC,
            'frais_livraison' => $fraisLivraison,
            'frais_retour' => $fraisRetour,
            'remise' => $remise,
            'total_ttc' => $totalTTCFinal
        ],
        'montant_total' => $totalTTCFinal,
        'code_reduction' => $commande->codeReduction ? [
            'code' => $commande->codeReduction->code,
            'montant' => floatval($commande->codeReduction->montant),
            'type' => $commande->codeReduction->type_reduction,
            'type_libelle' => $commande->codeReduction->type_reduction == 2 ? '%' : '€'
        ] : null,
        'notes' => $commande->notes,
        'pdf_disponible' => true
    ];
}

    /**
     * Obtenir la couleur du statut
     */
    private function getStatutCouleur($statut)
    {
        $couleurs = [
            1 => 'orange',
            2 => 'blue',
            3 => 'green',
            4 => 'purple',
            5 => 'red',
            6 => 'gray'
        ];

        return $couleurs[$statut] ?? 'gray';
    }

    /**
     * Télécharger la facture PDF
     */
    public function telechargerFacture($id)
    {
        try {
            $user = request()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Charger la commande avec toutes ses relations
            $commande = Commande::with([
                'user',
                'user.particulier',
                'user.professionnel',
                'user.professionnel.contactPro',
                'user.type',
                'materiels',
                'adresseLivraison',
                'adresseLivraison.commune',
                'modeLivraison',
                'modeRetour',
                'codeReduction',
                'statutCommande'
            ])->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if ($user->id !== $commande->user_id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Formater les données pour la facture
            $commandeFormatee = $this->formatCommandeDetails($commande);

            // Informations de l'entreprise
            $entreprise = [
                'nom' => 'TerraSana Location',
                'siret' => '123 456 789 00012',
                'tva_intra' => 'BE12 345678901',
                'adresse' => '123 Rue du Commerce, 1000 Bruxelles',
                'code_postal' => '1000',
                'ville' => 'Bruxelles',
                'telephone' => '01 23 45 67 89',
                'email' => 'contact@terrasana-location.be',
                'site' => 'www.terrasana-location.be',
                'logo' => public_path('images/logo.png')
            ];

            // Générer le PDF
            $pdf = Pdf::loadView('pdfs.facture', [
                'commande' => $commandeFormatee,
                'entreprise' => $entreprise,
                'date_generation' => now()->format('d/m/Y H:i')
            ]);

            // Configurer le PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true
            ]);

            // Télécharger le PDF
            return $pdf->download('facture_' . $commande->numero_commande . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Erreur génération facture', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoyer la facture par email
     */
    public function envoyerFactureEmail($id)
    {
        try {
            $user = request()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $commande = Commande::with([
                'user',
                'user.particulier',
                'user.professionnel',
                'materiels'
            ])->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if ($user->id !== $commande->user_id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Formater les données
            $commandeFormatee = $this->formatCommandeDetails($commande);

            // Informations de l'entreprise
            $entreprise = [
                'nom' => 'TerraSana Location',
                'siret' => '123 456 789 00012',
                'tva_intra' => 'BE12 345678901',
                'adresse' => '123 Rue du Commerce, 1000 Bruxelles',
                'email' => 'contact@terrasana-location.be'
            ];

            // Générer le PDF
            $pdf = Pdf::loadView('pdfs.facture', [
                'commande' => $commandeFormatee,
                'entreprise' => $entreprise,
                'date_generation' => now()->format('d/m/Y H:i')
            ]);

            // TODO: Implémenter l'envoi d'email
            // Mail::to($user->email)->send(new FactureCommande($commandeFormatee, $pdf));

            return response()->json([
                'success' => true,
                'message' => 'Facture envoyée par email avec succès'
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur envoi email facture', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatte une URL d'image
     */
    private function formatImageUrl($url)
    {
        if (!$url) {
            return null;
        }

        // Si c'est déjà une URL complète
        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) {
            return $url;
        }

        // Ajouter le slash si nécessaire
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        // Pour les images dans le dossier public
        if (str_starts_with($url, '/materiels/') || str_starts_with($url, '/images/')) {
            return asset($url);
        }

        // Pour les images dans storage
        if (str_starts_with($url, '/storage/')) {
            return asset($url);
        }

        return asset($url);
    }
}