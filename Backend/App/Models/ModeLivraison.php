<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeLivraison extends Model
{
     use HasFactory;

    protected $fillable = ['livraison'];

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}
