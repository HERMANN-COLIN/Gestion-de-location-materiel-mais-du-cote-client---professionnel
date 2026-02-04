<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeDocument extends Model
{
    use HasFactory;

    protected $fillable = ['document'];

    public function f()
    {
        return $this->hasMany(Facture::class);
    }
}
