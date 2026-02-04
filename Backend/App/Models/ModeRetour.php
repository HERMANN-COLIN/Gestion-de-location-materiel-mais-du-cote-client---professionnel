<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeRetour extends Model
{
     use HasFactory;

    protected $fillable = ['retour'];

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}
