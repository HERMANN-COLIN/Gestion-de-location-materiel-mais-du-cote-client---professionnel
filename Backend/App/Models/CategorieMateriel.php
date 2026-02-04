<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieMateriel extends Model
{
    protected $table = 'categorie_materiel'; // 

    protected $fillable = ['nom', 'description']; // [cite: 55]

    public function materiels()
    {
        // Une catégorie possède plusieurs matériels [cite: 83]
        return $this->hasMany(Materiel::class, 'categorie_id');
    }
}
