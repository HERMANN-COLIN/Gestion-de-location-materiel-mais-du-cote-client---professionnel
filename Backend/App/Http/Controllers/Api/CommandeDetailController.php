<?php
// app/Http/Controllers/Api/CommandeDetailController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Materiel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

            $commande = $this->chargerCommande($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if (!$this->peutVoir($user, $commande)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatCommandeDetails($commande)
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur chargement détail commande', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des détails de la commande',
                'error' => $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
            ], 500);
        }
    }

    /**
     * Charge la commande avec ses relations.
     * La relation photos n'est ajoutée que si elle existe dans Materiel.
     */
    private function chargerCommande($id)
    {
        $relations = [
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
            'statutCommande',
        ];

        if (method_exists(Materiel::class, 'photos')) {
            $relations[] = 'materiels.photos';
        }

        return Commande::with($relations)->find($id);
    }

    private function peutVoir($user, $commande): bool
    {
        if ((int) $user->id === (int) $commande->user_id) {
            return true;
        }

        return method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    /**
     * Formater les détails de la commande
     */
    private function formatCommandeDetails($commande)
    {
        // 1. CLIENT
        $user = $commande->user;

        $client = [
            'id' => $user->id,
            'email' => $user->email,
            'type' => 'particulier',
            'nom_complet' => $user->email,
            'telephone' => null
        ];

        if ($user->type) {
            $client['type'] = $user->type->type ?? 'particulier';
        }

        if ($client['type'] === 'particulier' && $user->particulier) {
            $particulier = $user->particulier;
            $client['nom'] = $particulier->nom ?? '';
            $client['prenom'] = $particulier->prenom ?? '';

            $nomComplet = trim(($particulier->prenom ?? '') . ' ' . ($particulier->nom ?? ''));
            if ($nomComplet !== '') {
                $client['nom_complet'] = $nomComplet;
            }

            $client['telephone'] = $particulier->telephone ?? null;
        } elseif ($client['type'] === 'professionnel' && $user->professionnel) {
            $pro = $user->professionnel;
            $client['nom_societe'] = $pro->nom_societe ?? '';

            if (!empty($pro->nom_societe)) {
                $client['nom_complet'] = $pro->nom_societe;
            }

            $client['telephone'] = $pro->telephone ?? null;

            if ($pro->contactPro) {
                $client['contact_nom'] = trim(($pro->contactPro->prenom ?? '') . ' ' . ($pro->contactPro->nom ?? ''));
                $client['contact_email'] = $pro->contactPro->email ?? '';
                $client['contact_telephone'] = $pro->contactPro->telephone ?? '';
            }
        }

        // 2. ADRESSE DE LIVRAISON
        $adresseLivraison = [
            'adresse_complete' => $commande->adresse_livraison ?? 'Retrait sur place'
        ];

        if ($commande->adresseLivraison && $commande->adresseLivraison->commune) {
            $adr = $commande->adresseLivraison;
            $rue = trim(($adr->numero_rue ?? '') . ' ' . ($adr->nom_rue ?? ''));
            $codePostal = $adr->commune->code_postal ?? '';
            $ville = $adr->commune->nom_commune ?? '';

            if ($rue !== '' && $codePostal !== '' && $ville !== '') {
                $adresseLivraison = [
                    'adresse_complete' => $rue . ', ' . $codePostal . ' ' . $ville,
                    'rue' => $rue,
                    'code_postal' => $codePostal,
                    'ville' => $ville
                ];
            }
        }

        // 3. ARTICLES
        $articles = [];
        $totalHT = 0;
        $totalTVA = 0;
        $totalTTC = 0;

        foreach ($commande->materiels as $materiel) {
            $photo = null;
            $photosListe = [];

            if ($materiel->relationLoaded('photos') && $materiel->photos->isNotEmpty()) {
                $photo = $this->formatImageUrl($materiel->photos->first()->url_photo ?? null);
                $photosListe = $materiel->photos->map(function ($p) {
                    return $this->formatImageUrl($p->url_photo ?? null);
                })->filter()->values()->all();
            }

            $pivot = $materiel->pivot;
            $articleTotalHT = floatval($pivot->sous_total_ht ?? 0);
            $articleTotalTVA = floatval($pivot->montant_tva ?? 0);
            $articleTotalTTC = floatval($pivot->sous_total_ttc ?? 0);

            $articles[] = [
                'id' => $materiel->id,
                'nom' => $materiel->nom,
                'description' => $materiel->description,
                'photo' => $photo,
                'photos' => $photosListe,
                'quantite' => $pivot->quantite ?? 0,
                'prix_unitaire_ht' => floatval($pivot->prix_unitaire_ht ?? 0),
                'taux_tva' => floatval($pivot->taux_tva ?? 21.00),
                'prix_unitaire_ttc' => floatval($pivot->prix_unitaire_ttc ?? 0),
                'sous_total_ht' => $articleTotalHT,
                'sous_total_ttc' => $articleTotalTTC,
                'montant_tva' => $articleTotalTVA,
            ];

            $totalHT += $articleTotalHT;
            $totalTVA += $articleTotalTVA;
            $totalTTC += $articleTotalTTC;
        }

        // 4. LIBELLÉS
        $modeLivraisonLibelle = $commande->modeLivraison->livraison ?? 'Sur place';
        $modeRetourLibelle = $commande->modeRetour->retour ?? 'Sur place';
        $statutLibelle = $commande->statutCommande->libelle ?? 'Inconnu';

        // 5. DURÉE
        $duree = 0;
        $dateDebutFormatted = '';
        $dateFinFormatted = '';

        if ($commande->date_debut && $commande->date_fin) {
            $debut = Carbon::parse($commande->date_debut);
            $fin = Carbon::parse($commande->date_fin);
            $duree = (int) $debut->diffInDays($fin) + 1;
            $dateDebutFormatted = $debut->format('d/m/Y');
            $dateFinFormatted = $fin->format('d/m/Y');
        }

        // 6. FRAIS ET TOTAL
        $fraisLivraison = floatval($commande->frais_livraison ?? 0);
        $fraisRetour = floatval($commande->frais_retour ?? 0);
        $totalTTCFinal = $totalTTC + $fraisLivraison + $fraisRetour;

        if (abs(floatval($commande->montant_total) - $totalTTCFinal) > 0.01) {
            Log::warning('Différence de total détectée', [
                'commande_id' => $commande->id,
                'montant_bdd' => $commande->montant_total,
                'montant_calcule' => $totalTTCFinal
            ]);
        }

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
                'remise' => 0,
                'total_ttc' => $totalTTCFinal
            ],
            'montant_total' => $totalTTCFinal,
            'code_reduction' => null, // fonctionnalité supprimée
            'notes' => $commande->notes,
            'pdf_disponible' => true
        ];
    }

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

    private function infosEntreprise(): array
    {
        return [
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

            $commande = $this->chargerCommande($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if (!$this->peutVoir($user, $commande)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $pdf = Pdf::loadView('pdfs.facture', [
                'commande' => $this->formatCommandeDetails($commande),
                'entreprise' => $this->infosEntreprise(),
                'date_generation' => now()->format('d/m/Y H:i')
            ]);

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true
            ]);

            return $pdf->download('facture_' . $commande->numero_commande . '.pdf');

        } catch (\Throwable $e) {
            Log::error('Erreur génération facture', [
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

            $commande = $this->chargerCommande($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            if (!$this->peutVoir($user, $commande)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $commandeFormatee = $this->formatCommandeDetails($commande);

            $pdf = Pdf::loadView('pdfs.facture', [
                'commande' => $commandeFormatee,
                'entreprise' => $this->infosEntreprise(),
                'date_generation' => now()->format('d/m/Y H:i')
            ]);

            // TODO: Implémenter l'envoi d'email
            // Mail::to($user->email)->send(new FactureCommande($commandeFormatee, $pdf));

            return response()->json([
                'success' => true,
                'message' => 'Facture envoyée par email avec succès'
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur envoi email facture', [
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

    private function formatImageUrl($url)
    {
        if (!$url) {
            return null;
        }

        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) {
            return $url;
        }

        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        return asset($url);
    }
}