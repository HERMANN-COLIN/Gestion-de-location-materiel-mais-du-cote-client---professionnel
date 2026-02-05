<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ContactPro;

class Fonction extends Model
{
    protected $fillable = ['fonction']; // 

    public function contacts()
    {
        // Une fonction peut être liée à plusieurs contacts [cite: 82]
        return $this->hasMany(ContactPro::class, 'fonction');
    }
}
