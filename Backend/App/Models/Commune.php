<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Adresse;
class Commune extends Model
{
    use HasFactory;

    protected $fillable = ['nom_commune', 'code_postal'];

    public function adresses()
    {
        return $this->hasMany(Adresse::class);
    }
}
