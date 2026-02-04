<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Professionnel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom_societe',
        'adresse_siege_id',
        'adresse_livraison_id',
        'heure_ouverture',
        'heure_fermeture',
        'langue_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function langue()
    {
        return $this->belongsTo(Langue::class);
    }

    public function adresseSiege()
    {
        return $this->belongsTo(Adresse::class, 'adresse_siege_id');
    }

    public function adresseLivraison()
    {
        return $this->belongsTo(Adresse::class, 'adresse_livraison_id');
    }
}
