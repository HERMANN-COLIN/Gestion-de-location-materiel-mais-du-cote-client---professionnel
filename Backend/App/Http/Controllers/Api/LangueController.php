<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Langue;
use Illuminate\Http\Request;

class LangueController extends Controller
{
    public function index()
    {
        return response()->json(
            Langue::orderBy('langue', 'asc')->get()
        );
    }
}
