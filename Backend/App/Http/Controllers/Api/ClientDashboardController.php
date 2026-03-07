<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Commande;
use App\Models\Facture;

class ClientDashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'commandes' => Commande::where('user_id', Auth::id())->count(),
            'factures' => Facture::whereHas('commande', function ($q) {
                $q->where('user_id', Auth::id());
            })->count()
        ]);
    }
}
