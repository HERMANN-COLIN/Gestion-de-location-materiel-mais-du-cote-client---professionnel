<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'materiel_id',
        'quantite',
        'prix_unitaire',
        'sous_total',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }
}