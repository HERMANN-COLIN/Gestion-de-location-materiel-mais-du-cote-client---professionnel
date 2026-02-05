<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Facture;

class StatutPaiement extends Model
{
      use HasFactory;

    protected $fillable = ['statut'];

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
