<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeReduction extends Model
{
        use HasFactory;
    
        protected $fillable = ['reduction'];
    
        public function codeReductions()
        {
            return $this->hasMany(CodeReduction::class);
        }
}
