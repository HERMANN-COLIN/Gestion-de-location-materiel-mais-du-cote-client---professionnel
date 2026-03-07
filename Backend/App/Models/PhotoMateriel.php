<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Materiel;

class PhotoMateriel extends Model
{
    use HasFactory;

    protected $table = 'photos_materiel';

    protected $fillable = [
        'materiel_id',
        'url_photo',
        'ordre_affichage', // pour trier les images
        'est_principale'   // pour définir la photo principale
    ];

    protected $casts = [
        'est_principale' => 'boolean',
    ];

    /**
     * Relation : Une photo appartient à un matériel
     */
    public function materiel()
    {
        return $this->belongsTo(Materiel::class);
    }
}
