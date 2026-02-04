<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Adresse extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_rue',
        'numero_rue',
        'commune_id',
    ];

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function professionnelsSiege()
    {
        return $this->hasMany(Professionnel::class, 'adresse_siege_id');
    }

    public function professionnelsLivraison()
    {
        return $this->hasMany(Professionnel::class, 'adresse_livraison_id');
    }
}
