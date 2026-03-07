<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Panier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'materiel_id',
        'quantite',
        'prix_unitaire_ht',
        'taux_tva'
    ];

    protected $casts = [
        'prix_unitaire_ht' => 'decimal:2',
        'taux_tva' => 'decimal:2'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }

    // Accesseurs
    public function getPrixTtcAttribute()
    {
        return $this->prix_unitaire_ht * (1 + ($this->taux_tva / 100));
    }

    public function getTotalHtAttribute()
    {
        return $this->prix_unitaire_ht * $this->quantite;
    }

    public function getTotalTtcAttribute()
    {
        return $this->total_ht * (1 + ($this->taux_tva / 100));
    }
}