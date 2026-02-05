<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Langue;

class Particulier extends Model
{
     protected $fillable = [
        'user_id','nom','prenom','adresse','langue_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function langue()
    {
        return $this->belongsTo(Langue::class);
    }
}
