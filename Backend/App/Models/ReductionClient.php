<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReductionClient extends Model
{
        use HasFactory;
    
        protected $fillable = [
            'user_id',
            'code_reduction_id',
            'date_attribution',
            'date_expiration',
            'montant_fixe',
            'pourcentage'
        ];
    
        public function user()
        {
            return $this->belongsTo(User::class);
        }
    
        public function codeReduction()
        {
            return $this->belongsTo(CodeReduction::class);
        }
}
