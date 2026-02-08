<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Materiel;

class PhotoMateriel extends Model  // Notez le singulier "Photo"
{
    use HasFactory;

    protected $table = 'photos_materiel';

    protected $fillable = [
        'materiel_id',
        'url_photo'
    ];

    // Relation avec le matériel
    public function materiel()
    {
        return $this->belongsTo(Materiel::class, 'materiel_id');
    }
}