<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Langue;
use Illuminate\Http\Request;

class LangueController extends Controller
{
    public function index(Request $request)
    {
        $langues = Langue::orderBy('langue')->get();
        
         return response()->json($langues);
    }
}