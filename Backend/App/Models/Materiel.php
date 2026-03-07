<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Materiel extends Model
{
    use HasFactory;

    protected $table = 'materiels';

    protected $fillable = [
        'categorie_id',
        'nom',
        'description',
        'prix_journalier_ht',
        'taux_tva',
        'dimensions',
        'stock_total',
        'stock_disponible',
    ];

    // Regroupez vos appends ici pour éviter les conflits
    protected $appends = ['prix_ttc', 'prix_formate'];

    /**
     * Relation : Un matériel appartient à une catégorie
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieMateriel::class, 'categorie_id');
    }

    /**
     * Relation : Un matériel possède plusieurs photos
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PhotoMateriel::class, 'materiel_id');
    }

    /**
     * Relation : Un matériel possède une photo principale
     */
    public function photoPrincipale(): HasOne
    {
        return $this->hasOne(PhotoMateriel::class, 'materiel_id')
                    ->where('est_principale', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors (Calculés dynamiquement)
    |--------------------------------------------------------------------------
    */

    /**
     * Calcule le prix TTC (TVA à 21% par défaut via la base de données)
     */
    public function getPrixTtcAttribute()
    {
        // Utilisation de round pour éviter les problèmes de précision décimale
        return round($this->prix_journalier_ht * (1 + ($this->taux_tva / 100)), 2);
    }

    /**
     * Accessor pour le prix HT formaté pour l'affichage
     */
    public function getPrixFormateAttribute()
    {
        // CORRECTION : Utilisation de prix_journalier_ht au lieu de prix_journalier
        return number_format($this->prix_journalier_ht, 2, ',', ' ') . ' € HT/jour';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes & Logique Métier
    |--------------------------------------------------------------------------
    */

    /**
     * Vérifie si le matériel est disponible
     */
    public function estDisponible(): bool
    {
        return $this->stock_disponible > 0;
    }

    /**
     * Scope pour filtrer uniquement les matériels disponibles en stock
     */
    public function scopeDisponibles($query)
    {
        return $query->where('stock_disponible', '>', 0);
    }
}