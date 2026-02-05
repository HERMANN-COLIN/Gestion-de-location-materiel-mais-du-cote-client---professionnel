<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Professionnel;
use App\Models\Fonction;

class ContactPro extends Model
{
    use HasFactory;

    // Le nom de la table est correct
    protected $table = 'contact_pro';

    // Les colonnes fillable doivent correspondre à la structure de la table
    protected $fillable = [
        'professionnel_id',
        'nom',
        'prenom',
        'email',
        'telephone',
        'fonction_id' // Corriger: dans la migration c'est "fonction_id", pas "fonction"
    ];

    // Relations
    public function professionnel()
    {
        return $this->belongsTo(Professionnel::class, 'professionnel_id');
    }

    // Renommer la méthode pour plus de clarté et corriger la clé étrangère
    public function fonction()
    {
        return $this->belongsTo(Fonction::class, 'fonction_id');
    }
}