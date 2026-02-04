<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fonction extends Model
{
    protected $fillable = ['fonction']; // 

    public function contacts()
    {
        // Une fonction peut être liée à plusieurs contacts [cite: 82]
        return $this->hasMany(ContactPro::class, 'fonction');
    }
}
