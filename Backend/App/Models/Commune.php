<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Commune extends Model
{
    use HasFactory;

    protected $fillable = ['nom_commune', 'numero_commune'];

    public function adresses()
    {
        return $this->hasMany(Adresse::class);
    }
}
