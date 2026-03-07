<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Commande;

class ModeLivraison extends Model
{
    use HasFactory;

    protected $table = 'modes_livraison';  // 👈 AJOUTER CETTE LIGNE
    
    protected $fillable = ['livraison'];

    public function commandes()
    {
        return $this->hasMany(Commande::class, 'mode_livraison');
    }
}