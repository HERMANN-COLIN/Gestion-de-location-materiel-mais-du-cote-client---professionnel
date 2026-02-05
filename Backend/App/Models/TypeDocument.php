<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Facture;
class TypeDocument extends Model
{
    use HasFactory;

    protected $fillable = ['document'];

    public function f()
    {
        return $this->hasMany(Facture::class);
    }
}
