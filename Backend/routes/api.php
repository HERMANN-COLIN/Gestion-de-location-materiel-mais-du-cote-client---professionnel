<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategorieMaterielController;
use App\Http\Controllers\Api\MaterielController;
use App\Http\Controllers\Api\PhotoMaterielController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\CommuneController;
use App\Http\Controllers\Api\LangueController;
use App\Http\Controllers\Api\FonctionController;
use App\Http\Controllers\Api\ContactProController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\PanierController;
use App\Http\Controllers\Api\CommandeDetailController;

// ==================== ROUTES PUBLIQUES ====================

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ⚠️ ORDRE CRITIQUE : les routes statiques AVANT les routes dynamiques {id}
// '/materiels/populaires' doit être déclaré AVANT '/materiels/{id}'
// sinon Laravel interprète "populaires" comme un {id}
Route::get('/materiels/populaires',           [MaterielController::class, 'populaires']);
Route::get('/materiels/search',               [MaterielController::class, 'search']);
Route::get('/materiels/recents',              [MaterielController::class, 'recents']);
Route::get('/materiels',                      [MaterielController::class, 'index']);
Route::get('/materiels/{id}',                 [MaterielController::class, 'show']);
Route::get('/materiels/{id}/disponibilite',   [MaterielController::class, 'disponibilite']);

// ⚠️ Même chose pour les catégories
Route::get('/categories-materiel/search',     [CategorieMaterielController::class, 'search']);
Route::get('/categories-materiel',            [CategorieMaterielController::class, 'index']);
Route::get('/categories-materiel/{id}',       [CategorieMaterielController::class, 'show']);

Route::get('/photos-materiel/{materielId}',   [PhotoMaterielController::class, 'getByMateriel']);
Route::get('/communes',                       [CommuneController::class, 'index']);
Route::get('/langues',                        [LangueController::class, 'index']);
Route::get('/types',                          [TypeController::class, 'index']);
Route::get('/fonctions',                      [FonctionController::class, 'index']);
Route::get('/fonctions/{id}',                 [FonctionController::class, 'show']);

// ==================== ROUTES PROTÉGÉES ====================

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get ('/user',            [AuthController::class, 'user']);
    Route::put ('/user/profile',    [AuthController::class, 'updateProfile']);

    // ── Admin ──────────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::post  ('/materiels',              [MaterielController::class, 'store']);
        Route::put   ('/materiels/{id}',         [MaterielController::class, 'update']);
        Route::delete('/materiels/{id}',         [MaterielController::class, 'destroy']);
        Route::post  ('/materiels/{id}/stock',   [MaterielController::class, 'updateStock']);

        Route::post  ('/categories-materiel',         [CategorieMaterielController::class, 'store']);
        Route::put   ('/categories-materiel/{id}',    [CategorieMaterielController::class, 'update']);
        Route::delete('/categories-materiel/{id}',    [CategorieMaterielController::class, 'destroy']);

        Route::post  ('/photos-materiel',       [PhotoMaterielController::class, 'store']);
        Route::put   ('/photos-materiel/{id}',  [PhotoMaterielController::class, 'update']);
        Route::delete('/photos-materiel/{id}',  [PhotoMaterielController::class, 'destroy']);

        Route::post  ('/fonctions',                    [FonctionController::class, 'store']);
        Route::put   ('/fonctions/{id}',               [FonctionController::class, 'update']);
        Route::delete('/fonctions/{id}',               [FonctionController::class, 'destroy']);
        Route::get   ('/fonctions/search',             [FonctionController::class, 'search']);
        Route::get   ('/fonctions/stats/with-count',   [FonctionController::class, 'withStats']);
    });

    // ── Commandes ──────────────────────────────────────────
    // ⚠️ ORDRE CRITIQUE : routes statiques AVANT les routes dynamiques {id}
    Route::prefix('commandes')->group(function () {
        Route::get ('/',               [CommandeController::class, 'index']);
        Route::post('/',               [CommandeController::class, 'store']);
        Route::get ('/adresses',       [CommandeController::class, 'getAdressesLivraison']); // GET  /api/commandes/adresses
        Route::post('/frais',          [CommandeController::class, 'calculerFrais']);         // POST /api/commandes/frais
        Route::get ('/{id}',           [CommandeController::class, 'show']);                  // APRÈS les routes statiques
        Route::put ('/{id}/annuler',   [CommandeController::class, 'annuler']);
        Route::get ('/{id}/facture',       [CommandeDetailController::class, 'telechargerFacture']);  // GET  /api/commandes/{id}/facture
        Route::post('/{id}/facture/email', [CommandeDetailController::class, 'envoyerFactureEmail']); // POST /api/commandes/{id}/facture/email
    });

    // ── Panier ─────────────────────────────────────────────
    // ⚠️ PROBLÈME ORIGINAL : Route::prefix('panier') + Route::get('/panier')
    //    produisait l'URL /api/panier/panier → 404
    //
    // ✅ CORRECTION : avec prefix('panier'), les chemins internes
    //    commencent par '/' seul (pas '/panier')
    //
    // ⚠️ ORDRE : 'count' AVANT '{id}' sinon "count" est capturé comme {id}
    Route::prefix('panier')->group(function () {
        Route::get   ('/compter', [PanierController::class, 'compter']);   // GET  /api/panier/compter
        Route::get   ('/',      [PanierController::class, 'index']);       // GET  /api/panier
        Route::post  ('/',      [PanierController::class, 'ajouter']);     // POST /api/panier
        Route::put   ('/{id}',  [PanierController::class, 'mettreAJour']); // PUT  /api/panier/{id}
        Route::delete('/{id}',  [PanierController::class, 'supprimer']);   // DELETE /api/panier/{id}
        Route::delete('/',      [PanierController::class, 'vider']);       // DELETE /api/panier
    });

    // ── Contacts professionnels ────────────────────────────
    Route::prefix('contacts')->group(function () {
        Route::get   ('/',             [ContactProController::class, 'index']);
        Route::post  ('/',             [ContactProController::class, 'store']);
        Route::get   ('/{id}',         [ContactProController::class, 'show']);
        Route::put   ('/{id}',         [ContactProController::class, 'update']);
        Route::delete('/{id}',         [ContactProController::class, 'destroy']);
        Route::post  ('/{id}/set-primary', [ContactProController::class, 'setAsPrimary']);
    });
});