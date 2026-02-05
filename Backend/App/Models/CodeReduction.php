<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TypeReduction;

class CodeReduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type_reduction',
        'montant',
        'hors_tva',
        'date_debut',
        'date_fin',
        'utilisations_max',
        'utilisations_actuelles'
    ];

    public function typeReduction()
    {
        return $this->belongsTo(TypeReduction::class, 'type_reduction');
    }
}
