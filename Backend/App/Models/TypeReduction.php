<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CodeReduction;

class TypeReduction extends Model
{
        use HasFactory;
    
        protected $fillable = ['reduction'];
    
        public function codeReductions()
        {
            return $this->hasMany(CodeReduction::class);
        }
}
