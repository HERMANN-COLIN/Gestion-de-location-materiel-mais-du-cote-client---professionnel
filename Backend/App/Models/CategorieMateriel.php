<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Materiel;

class CategorieMateriel extends Model
{
    use HasFactory;

    protected $table = 'categorie_materiel';

    protected $fillable = [
        'nom',
        'description'
    ];

    public function materiels()
    {
        return $this->hasMany(Materiel::class, 'categorie_id');
    }
}
