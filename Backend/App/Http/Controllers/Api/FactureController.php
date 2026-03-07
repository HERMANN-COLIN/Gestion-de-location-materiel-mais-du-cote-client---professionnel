<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Illuminate\Support\Facades\Auth;

class FactureController extends Controller
{
    public function show($commandeId)
    {
        return Facture::where('commande_id', $commandeId)
            ->firstOrFail();
    }
}
