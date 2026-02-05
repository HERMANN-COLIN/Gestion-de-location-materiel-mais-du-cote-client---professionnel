<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Particulier;
use App\Models\Professionnel;

class Langue extends Model
{
    use HasFactory;

    protected $fillable = ['langue'];

    public function particuliers()
    {
        return $this->hasMany(Particulier::class);
    }

    public function professionnels()
    {
        return $this->hasMany(Professionnel::class);
    }
}