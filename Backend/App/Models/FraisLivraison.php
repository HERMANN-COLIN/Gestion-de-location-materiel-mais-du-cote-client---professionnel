<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraisLivraison extends Model
{
    use HasFactory;

    protected $fillable = ['jour_semaine', 'distance_max', 'montant'];
}
