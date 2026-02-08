<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieMateriel extends Model
{
    use HasFactory;

    protected $table = 'categories_materiel';
    
    protected $fillable = [
        'nom',
        'description'
    ];

    /**
     * Relation avec les matériels
     */
    public function materiels()
    {
        return $this->hasMany(Materiel::class, 'categorie_id');
    }

    /**
     * Scope pour les catégories actives
     */
    public function scopeActives($query)
    {
        return $query->whereHas('materiels', function($q) {
            $q->where('actif', true);
        });
    }
}