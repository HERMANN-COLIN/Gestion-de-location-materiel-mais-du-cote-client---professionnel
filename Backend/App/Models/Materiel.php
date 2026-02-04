<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materiel extends Model
{
    use HasFactory;

    protected $fillable = [
        'categorie_id',
        'nom',
        'description',
        'prix_journalier',
        'dimensions',
        'stock_total',
        'stock_disponible',
    ];

    public function categorie()
    {
        return $this->belongsTo(CategorieMateriel::class, 'categorie_id');
    }

    public function photos()
    {
        return $this->hasMany(PhotoMateriel::class);
    }
}