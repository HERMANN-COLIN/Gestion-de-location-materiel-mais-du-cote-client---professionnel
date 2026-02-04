<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materiel;
use Illuminate\Http\Request;

class MaterielController extends Controller
{
    public function index(Request $request)
    {
        $query = Materiel::with(['categorie', 'photos']);
        
        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }
        
        if ($request->has('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }
        
        // Filtre par disponibilité
        if ($request->boolean('disponible', true)) {
            $query->where('stock_disponible', '>', 0);
        }
        
        $materiels = $query->paginate(12);
        
        return response()->json($materiels);
    }

    public function show($id)
    {
        $materiel = Materiel::with(['categorie', 'photos'])->findOrFail($id);
        
        return response()->json($materiel);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'categorie_id' => 'required|exists:categorie_materiel,id',
            'nom' => 'required|string|max:150',
            'description' => 'required|string',
            'prix_journalier' => 'required|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'stock_total' => 'required|integer|min:0',
            'stock_disponible' => 'required|integer|min:0|lte:stock_total',
        ]);

        $materiel = Materiel::create($validated);
        
        return response()->json($materiel, 201);
    }

    public function update(Request $request, $id)
    {
        $materiel = Materiel::findOrFail($id);
        
        $validated = $request->validate([
            'categorie_id' => 'exists:categorie_materiel,id',
            'nom' => 'string|max:150',
            'description' => 'string',
            'prix_journalier' => 'numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'stock_total' => 'integer|min:0',
            'stock_disponible' => 'integer|min:0|lte:stock_total',
        ]);

        $materiel->update($validated);
        
        return response()->json($materiel);
    }

    public function destroy($id)
    {
        $materiel = Materiel::findOrFail($id);
        $materiel->delete();
        
        return response()->json(null, 204);
    }
}