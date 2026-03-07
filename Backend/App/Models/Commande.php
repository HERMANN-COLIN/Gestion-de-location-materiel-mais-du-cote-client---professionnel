<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'numero_commande', 
        'date_debut', 
        'date_fin', 
        'date_commande',
        'statut', 
        'mode_livraison', 
        'mode_retour', 
        
        // 🔥 ADRESSES - Les deux champs !
        'adresse_livraison_id',      // ID de la relation
        'adresse_livraison',         // ✅ AJOUTÉ - Texte figé
        
        'montant_total', 
        'frais_livraison',
        'frais_retour',
        'code_reduction_id',         // Si vous utilisez les codes promo
        
        'notes',
        'jour_livraison',
        'distance_livraison',
        'jour_retour',
        'distance_retour'
    ];

    protected $casts = [
        'date_commande' => 'datetime',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant_total' => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'frais_retour' => 'decimal:2',
        'distance_livraison' => 'decimal:2',
        'distance_retour' => 'decimal:2',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adresseLivraison()
    {
        return $this->belongsTo(Adresse::class, 'adresse_livraison_id');
    }

    public function statutCommande()
    {
        return $this->belongsTo(Statut::class, 'statut');
    }

    /**
     * Relation avec le mode de livraison
     */
    public function modeLivraison()
    {
        return $this->belongsTo(ModeLivraison::class, 'mode_livraison');
    }

    /**
     * Relation avec le mode de retour
     */
    public function modeRetour()
    {
        return $this->belongsTo(ModeRetour::class, 'mode_retour');
    }

    public function codeReduction()
    {
        return $this->belongsTo(CodeReduction::class, 'code_reduction_id');
    }

    /**
     * Relation many-to-many avec les matériels
     * Utilise la table pivot 'commande_materiel' avec les nouvelles colonnes
     */
    public function materiels()
    {
        return $this->belongsToMany(Materiel::class, 'commande_materiel')
            ->withPivot([
                'quantite', 
                'prix_unitaire_ht', 
                'taux_tva', 
                'prix_unitaire_ttc',
                'sous_total_ht', 
                'sous_total_ttc', 
                'montant_tva'
            ])
            ->withTimestamps();
    }

    // Accesseurs pour les totaux
    public function getTotalHtAttribute()
    {
        return $this->materiels->sum(function($materiel) {
            return $materiel->pivot->sous_total_ht ?? 0;
        });
    }

    public function getTotalTvaAttribute()
    {
        return $this->materiels->sum(function($materiel) {
            return $materiel->pivot->montant_tva ?? 0;
        });
    }

    public function getTotalTtcAttribute()
    {
        return $this->materiels->sum(function($materiel) {
            return $materiel->pivot->sous_total_ttc ?? 0;
        });
    }

    /**
     * Calculer le montant total de la commande (articles + frais - remise)
     */
    public function calculerMontantFinal()
    {
        $totalArticles = $this->total_ttc;
        $totalFrais = $this->frais_livraison + ($this->frais_retour ?? 0);
        
        return $totalArticles + $totalFrais;
    }
}