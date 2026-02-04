<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategorieMateriel;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function index(Request $request)
    {
        $categories = CategorieMateriel::orderBy('nom')->get();
        
        return response()->json($categories);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);
        
        $categorie = CategorieMateriel::create($request->all());
        
        return response()->json($categorie, 201);
    }
    
    public function update(Request $request, $id)
    {
        $categorie = CategorieMateriel::findOrFail($id);
        
        $request->validate([
            'nom' => 'string|max:100',
            'description' => 'nullable|string',
        ]);
        
        $categorie->update($request->all());
        
        return response()->json($categorie);
    }
    
    public function destroy($id)
    {
        $categorie = CategorieMateriel::findOrFail($id);
        $categorie->delete();
        
        return response()->json(null, 204);
    }
}