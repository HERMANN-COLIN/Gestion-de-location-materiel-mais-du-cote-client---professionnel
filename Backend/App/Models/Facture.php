<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 
use App\Models\Commande;
use App\Models\TypeDocument;
use App\Models\StatutPaiement;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'numero_facture',
        'type_id',
        'date_emission',
        'date_echeance',
        'montant_ht',
        'montant_tva',
        'montant_ttc',
        'statut_paiement_id',
        'url_pdf',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function type()
    {
        return $this->belongsTo(TypeDocument::class, 'type_id');
    }

    public function statutPaiement()
    {
        return $this->belongsTo(StatutPaiement::class);
    }
}