<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CategorieMateriel;
use App\Models\PhotoMateriel;

class Materiel extends Model
{
    use HasFactory;

    protected $fillable = [
        'categorie_id',
        'nom',
        'description',
        'prix_journalier',
        'dimensions',
        'stock_total',
        'stock_disponible',
    ];

    public function categorie()
    {
        return $this->belongsTo(CategorieMateriel::class, 'categorie_id');
    }

    public function photos()
    {
        return $this->hasMany(PhotoMateriel::class); // ou PhotosMateriel::class selon votre choix
    }
    
    /**
     * Accessor pour le prix formaté
     */
    public function getPrixFormateAttribute()
    {
        return number_format($this->prix_journalier, 2, ',', ' ') . ' €/jour';
    }
    
    /**
     * Vérifie si le matériel est disponible
     */
    public function estDisponible()
    {
        return $this->stock_disponible > 0;
    }
    
    /**
     * Scope pour les matériels disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('stock_disponible', '>', 0);
    }
}