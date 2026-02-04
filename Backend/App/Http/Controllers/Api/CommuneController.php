<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commune;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function index(Request $request)
    {
        $communes = Commune::orderBy('nom_commune')->get();
        
        return response()->json($communes);
    }
}