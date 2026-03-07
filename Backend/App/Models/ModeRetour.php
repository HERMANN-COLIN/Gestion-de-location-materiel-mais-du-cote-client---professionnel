<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Commande;

class ModeRetour extends Model
{
    use HasFactory;

    protected $table = 'modes_retour';  // 👈 AJOUTER CETTE LIGNE
    
    protected $fillable = ['retour'];

    public function commandes()
    {
        return $this->hasMany(Commande::class, 'mode_retour');
    }
}