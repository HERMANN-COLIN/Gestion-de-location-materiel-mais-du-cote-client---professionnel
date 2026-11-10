<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materiel;
use App\Models\PhotoMateriel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_materiels' => Materiel::count(),
                    'stock_faible'    => Materiel::where('stock_disponible', '<', 10)
                                            ->where('stock_disponible', '>', 0)->count(),
                    'rupture'         => Materiel::where('stock_disponible', 0)->count(),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->erreur('Erreur lors du chargement des statistiques', $e);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = Materiel::with(['categorie', 'photos']);

            if ($request->filled('categorie_id')) {
                $query->where('categorie_id', $request->categorie_id);
            }

            if ($request->filled('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('filter_stock')) {
                if ($request->filter_stock === 'low') {
                    $query->where('stock_disponible', '<', 10)->where('stock_disponible', '>', 0);
                } elseif ($request->filter_stock === 'out') {
                    $query->where('stock_disponible', 0);
                }
            }

            $perPage   = min((int) $request->get('per_page', 15), 100);
            $materiels = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $materiels->getCollection()->each(fn ($m) => $this->ajouterUrlsPhotos($m));

            return response()->json([
                'success'      => true,
                'data'         => $materiels->items(),
                'current_page' => $materiels->currentPage(),
                'last_page'    => $materiels->lastPage(),
                'total'        => $materiels->total(),
            ]);
        } catch (\Throwable $e) {
            return $this->erreur('Erreur lors du chargement des matériels', $e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), $this->regles());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $materiel = Materiel::create($this->donnees($request));
            $this->enregistrerPhotos($request, $materiel);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Matériel créé avec succès',
                'data'    => $this->recharger($materiel)
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->erreur('Erreur lors de la création du matériel', $e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $materiel = Materiel::find($id);

            if (!$materiel) {
                return response()->json(['success' => false, 'message' => 'Matériel non trouvé'], 404);
            }

            $validator = Validator::make($request->all(), $this->regles());

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $materiel->update($this->donnees($request));
            $this->enregistrerPhotos($request, $materiel);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Matériel mis à jour avec succès',
                'data'    => $this->recharger($materiel)
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->erreur('Erreur lors de la mise à jour du matériel', $e);
        }
    }

    public function destroy($id)
    {
        try {
            $materiel = Materiel::with('photos')->find($id);

            if (!$materiel) {
                return response()->json(['success' => false, 'message' => 'Matériel non trouvé'], 404);
            }

            DB::beginTransaction();

            foreach ($materiel->photos as $photo) {
                $this->supprimerFichier($photo->url_photo);
                $photo->delete();
            }

            $materiel->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Matériel supprimé avec succès']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->erreur('Erreur lors de la suppression du matériel', $e);
        }
    }

    public function updateStock(Request $request, $id)
    {
        try {
            $materiel = Materiel::find($id);

            if (!$materiel) {
                return response()->json(['success' => false, 'message' => 'Matériel non trouvé'], 404);
            }

            $validator = Validator::make($request->all(), [
                'stock_disponible' => 'required|integer|min:0|max:' . (int) $materiel->stock_total
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le stock disponible ne peut pas dépasser le stock total',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $materiel->stock_disponible = $request->stock_disponible;
            $materiel->save();

            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour',
                'data'    => $materiel
            ]);

        } catch (\Throwable $e) {
            return $this->erreur('Erreur lors de la mise à jour du stock', $e);
        }
    }

    // ==================== MÉTHODES PRIVÉES ====================

    private function regles(): array
    {
        return [
            'nom'              => 'required|string|max:150',
            'categorie_id'     => 'required|exists:categories_materiel,id',
            'prix_journalier'  => 'required|numeric|min:0',
            'dimensions'       => 'nullable|string|max:100',
            'stock_total'      => 'required|integer|min:0',
            'stock_disponible' => 'required|integer|min:0|lte:stock_total',
            'description'      => 'nullable|string',
            'photos.*'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    private function donnees(Request $request): array
    {
        // ⚠️ CORRECTION ICI : On mappe 'prix_journalier' du formulaire vers 'prix_journalier_ht' de la BDD
        return [
            'nom'                => $request->nom,
            'categorie_id'       => $request->categorie_id,
            'prix_journalier_ht' => $request->prix_journalier, 
            'dimensions'         => $request->dimensions,
            'stock_total'        => $request->stock_total,
            'stock_disponible'   => $request->stock_disponible,
            'description'        => $request->description,
        ];
    }

    private function enregistrerPhotos(Request $request, Materiel $materiel): void
    {
        if (!$request->hasFile('photos')) {
            return;
        }

        foreach ($request->file('photos') as $photo) {
            $path = $photo->store('photos/materiels', 'public');

            PhotoMateriel::create([
                'materiel_id' => $materiel->id,
                'url_photo'   => '/storage/' . $path,
            ]);
        }
    }

    private function recharger(Materiel $materiel): Materiel
    {
        $materiel->load(['categorie', 'photos']);
        $this->ajouterUrlsPhotos($materiel);

        return $materiel;
    }

    private function ajouterUrlsPhotos(Materiel $materiel): void
    {
        foreach ($materiel->photos as $photo) {
            // ⚠️ CORRECTION ICI : On écrase la valeur pour que le frontend reçoive l'URL absolue
            $photo->url_photo = $this->urlPhoto($photo->url_photo);
        }
    }

    private function urlPhoto(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = trim($url);

        if (preg_match('#^(https?:)?//#i', $url)) {
            $parsed = parse_url(str_starts_with($url, '//') ? 'http:' . $url : $url);
            $host   = $parsed['host'] ?? '';

            if (!in_array($host, ['localhost', '127.0.0.1'], true)) {
                return $url;
            }

            $url = $parsed['path'] ?? '';
        }

        $path = ltrim($url, '/');

        if (!str_starts_with($path, 'storage/') && Storage::disk('public')->exists($path)) {
            $path = 'storage/' . $path;
        }

        return asset($path);
    }

    private function supprimerFichier(?string $url): void
    {
        if (!$url) {
            return;
        }

        $path = ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');

        if (str_starts_with($path, 'storage/')) {
            $relatif = substr($path, strlen('storage/'));

            if (Storage::disk('public')->exists($relatif)) {
                Storage::disk('public')->delete($relatif);
            }
        }
    }

    private function erreur(string $message, \Throwable $e)
    {
        Log::error($message, [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'error'   => $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
        ], 500);
    }
}