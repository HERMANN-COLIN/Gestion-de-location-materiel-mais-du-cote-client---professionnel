<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TypeController extends Controller
{
    /**
     * Liste tous les types d'utilisateurs avec leurs statistiques et photos
     */
 public function index()
{
    try {
        // On passe $this à la closure avec 'use ($this)'
        $types = Cache::remember('types.with.stats', 3600, function () {
            return Type::withCount(['users', 'commandes'])
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'type' => $type->type,
                        'libelle' => $type->type === 'particulier' ? 'Particulier' : 'Professionnel',
                        'icon' => $type->type === 'particulier' ? '👤' : '🏢',
                        'description' => $this->getTypeDescription($type->type),
                        'photo' => $this->getTypePhoto($type->type),
                        'photo_thumbnail' => $this->getTypePhoto($type->type, true),
                        'photo_url' => $this->formatImageUrl($this->getTypePhoto($type->type)),
                        'users_count' => $type->users_count,
                        'commandes_count' => $type->commandes_count,
                        'created_at' => $type->created_at?->format('d/m/Y'),
                        'updated_at' => $type->updated_at?->format('d/m/Y')
                    ];
                });
        });

        return response()->json([
            'success' => true,
            'data' => $types,
            'meta' => [
                'total' => count($types),
                'timestamp' => now()->toIso8601String()
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Erreur API Types: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

    /**
     * Affiche un type spécifique avec ses détails
     */
    public function show($id)
    {
        try {
            $type = Type::withCount(['users', 'commandes'])
                ->with(['users' => function ($query) {
                    $query->latest()->limit(5);
                }])
                ->find($id);

            if (!$type) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type non trouvé'
                ], 404);
            }

            // Formater les données
            $data = [
                'id' => $type->id,
                'type' => $type->type,
                'libelle' => $type->type === 'particulier' ? 'Particulier' : 'Professionnel',
                'icon' => $type->type === 'particulier' ? '👤' : '🏢',
                // ✅ AJOUT DES PHOTOS DANS LE DÉTAIL
                'photo' => $this->getTypePhoto($type->type),
                'photo_thumbnail' => $this->getTypePhoto($type->type, true),
                'photo_url' => $this->formatImageUrl($this->getTypePhoto($type->type)),
                'description' => $this->getTypeDescription($type->type),
                'stats' => [
                    'total_users' => $type->users_count,
                    'total_commandes' => $type->commandes_count,
                    'pourcentage_commandes' => $this->calculatePercentage($type->commandes_count)
                ],
                'avantages' => $this->getTypeAvantages($type->type),
                'documents_requis' => $this->getRequiredDocuments($type->type),
                'users_recents' => $type->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'email' => $user->email,
                        'created_at' => $user->created_at?->format('d/m/Y'),
                        'commandes_count' => $user->commandes()->count()
                    ];
                }),
                'tva_applicable' => $type->type === 'professionnel',
                'taux_tva_defaut' => $type->type === 'professionnel' ? 21.00 : null,
                'created_at' => $type->created_at?->format('d/m/Y H:i'),
                'updated_at' => $type->updated_at?->format('d/m/Y H:i')
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur chargement type', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retourne les types avec leurs photos associées (méthode dédiée)
     */
    public function avecPhotos()
    {
        try {
            $types = Type::orderBy('id', 'asc')->get()->map(function ($type) {
                return [
                    'id' => $type->id,
                    'type' => $type->type,
                    'libelle' => $type->type === 'particulier' ? 'Particulier' : 'Professionnel',
                    'icon' => $type->type === 'particulier' ? '👤' : '🏢',
                    'photo' => $this->formatImageUrl($this->getTypePhoto($type->type)),
                    'photo_thumbnail' => $this->formatImageUrl($this->getTypePhoto($type->type, true))
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des types avec photos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des types
     */
    public function statistiques()
    {
        try {
            $stats = [
                'total_types' => Type::count(),
                'repartition' => Type::withCount('users')
                    ->get()
                    ->mapWithKeys(function ($type) {
                        return [$type->type => $type->users_count];
                    }),
                'evolution' => [
                    'particuliers' => $this->getEvolutionByType('particulier'),
                    'professionnels' => $this->getEvolutionByType('professionnel')
                ],
                'tva_moyenne' => [
                    'particuliers' => 21.00,
                    'professionnels' => 21.00
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère l'évolution des inscriptions par type
     */
    private function getEvolutionByType($type)
    {
        $months = collect(range(5, 0))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        });

        return $months->map(function ($month) use ($type) {
            $count = Type::where('type', $type)
                ->withCount(['users' => function ($query) use ($month) {
                    $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
                }])
                ->first()
                ->users_count ?? 0;

            return [
                'mois' => $month,
                'count' => $count
            ];
        });
    }

    /**
     * Calcule le pourcentage
     */
    private function calculatePercentage($count)
    {
        $total = Type::withCount('commandes')->get()->sum('commandes_count');
        if ($total === 0) return 0;
        return round(($count / $total) * 100, 2);
    }

    /**
     * Retourne la description du type
     */
    private function getTypeDescription($type)
    {
        return $type === 'particulier' 
            ? 'Compte personnel pour les locations ponctuelles'
            : 'Compte professionnel pour les entreprises et associations';
    }

    /**
     * Retourne les avantages du type
     */
    private function getTypeAvantages($type)
    {
        if ($type === 'particulier') {
            return [
                'Location sans justificatif professionnel',
                'Accès à tous les matériels',
                'Support client dédié',
                'Paiement sécurisé'
            ];
        } else {
            return [
                'Facturation HT/TVA',
                'Devis personnalisés',
                'Tarifs préférentiels sur les locations longues durées',
                'Gestion multi-contacts',
                'Livraison prioritaire'
            ];
        }
    }

    /**
     * Retourne les documents requis
     */
    private function getRequiredDocuments($type)
    {
        if ($type === 'particulier') {
            return [
                'Pièce d\'identité',
                'Justificatif de domicile'
            ];
        } else {
            return [
                'Extrait Kbis',
                'Pièce d\'identité du représentant',
                'RIB professionnel',
                'Attestation de TVA intracommunautaire'
            ];
        }
    }

    /**
     * Retourne la photo du type
     */
    private function getTypePhoto($type, $thumbnail = false)
    {
        $photos = [
            'particulier' => [
                'full' => '/images/types/particulier.jpg',
                'thumb' => '/images/types/particulier-thumb.jpg'
            ],
            'professionnel' => [
                'full' => '/images/types/professionnel.jpg',
                'thumb' => '/images/types/professionnel-thumb.jpg'
            ]
        ];

        $photo = $photos[$type] ?? $photos['particulier'];
        return $thumbnail ? $photo['thumb'] : $photo['full'];
    }

    /**
     * Formate l'URL de l'image
     */
   // Dans PanierController.php
private function formatImageUrl(?string $url): ?string
{
    if (!$url) return null;

    // Si c'est déjà une URL complète
    if (str_starts_with($url, 'http')) return $url;

    // Nettoyer le slash au début pour éviter les doubles slashes
    $clean = ltrim($url, '/');

    // On utilise simplement url() qui pointe vers le dossier 'public'
    return url($clean); 
}

    /**
     * Rafraîchir le cache
     */
    public function refreshCache()
    {
        try {
            Cache::forget('types.with.stats');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache rafraîchi avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement du cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}