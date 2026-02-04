<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatutPaiement extends Model
{
      use HasFactory;

    protected $fillable = ['statut'];

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
