<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Type;
use App\Models\Particulier;
use App\Models\Professionnel;
use App\Models\Commande;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'type_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function particulier()
    {
        return $this->hasOne(Particulier::class);
    }

    public function professionnel()
    {
        return $this->hasOne(Professionnel::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}