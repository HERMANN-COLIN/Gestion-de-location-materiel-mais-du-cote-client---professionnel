<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\DetailCommande;

class Commande extends Model
{
    protected $fillable = [
        'user_id', 'numero_commande', 'date_debut', 'date_fin', 
        'statut', 'mode_livraison', 'mode_retour', 
        'adresse_livraison', 'montant_total', 'frais_livraison'
    ];

    public function items() {
        return $this->hasMany(DetailCommande::class, 'commande_id'); // [cite: 83]
    }

    public function client() {
        return $this->belongsTo(User::class, 'user_id'); // [cite: 81]
    }
}
