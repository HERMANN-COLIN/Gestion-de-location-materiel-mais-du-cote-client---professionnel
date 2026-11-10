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
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminCommandeController;
use App\Http\Controllers\Api\AdminAnalyticsController;

// ==================== ROUTES PUBLIQUES ====================

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Catalogue public
Route::get('/materiels/populaires',           [MaterielController::class, 'populaires']);
Route::get('/materiels/search',               [MaterielController::class, 'search']);
Route::get('/materiels/recents',              [MaterielController::class, 'recents']);
Route::get('/materiels',                      [MaterielController::class, 'index']);
Route::get('/materiels/{id}',                 [MaterielController::class, 'show']);
Route::get('/materiels/{id}/disponibilite',   [MaterielController::class, 'disponibilite']);

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

    // ── Auth ───────────────────────────────────────────────
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get ('/user',            [AuthController::class, 'user']);
    Route::put ('/user/profile',    [AuthController::class, 'updateProfile']);

    // ── Commandes (client) ─────────────────────────────────
    Route::prefix('commandes')->group(function () {
        Route::get ('/',               [CommandeController::class, 'index']);
        Route::post('/',               [CommandeController::class, 'store']);
        Route::get ('/adresses',       [CommandeController::class, 'getAdressesLivraison']);
        Route::post('/frais',          [CommandeController::class, 'calculerFrais']);
        Route::get ('/{id}',           [CommandeController::class, 'show']);
        Route::put ('/{id}/annuler',   [CommandeController::class, 'annuler']);
        Route::get ('/{id}/facture',       [CommandeDetailController::class, 'telechargerFacture']);
        Route::post('/{id}/facture/email', [CommandeDetailController::class, 'envoyerFactureEmail']);
    });

    // ── Panier ─────────────────────────────────────────────
    Route::prefix('panier')->group(function () {
        Route::get   ('/compter', [PanierController::class, 'compter']);
        Route::get   ('/',        [PanierController::class, 'index']);
        Route::post  ('/',        [PanierController::class, 'ajouter']);
        Route::put   ('/{id}',    [PanierController::class, 'mettreAJour']);
        Route::delete('/{id}',    [PanierController::class, 'supprimer']);
        Route::delete('/',        [PanierController::class, 'vider']);
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

    // ==================== ADMIN ====================
    Route::middleware('admin')->prefix('admin')->group(function () {

        // ── Dashboard ──────────────────────────────────────
        Route::get('/stats',     [AdminDashboardController::class, 'stats']);
        Route::get('/analytics', [AdminAnalyticsController::class, 'analytics']);

        // ── Matériels (CRUD admin) ─────────────────────────
        Route::get   ('/materiels',              [AdminDashboardController::class, 'index']);
        Route::post  ('/materiels',              [AdminDashboardController::class, 'store']);
        Route::match(['post', 'put'], '/materiels/{id}', [AdminDashboardController::class, 'update']);
        Route::delete('/materiels/{id}',         [AdminDashboardController::class, 'destroy']);
        Route::post  ('/materiels/{id}/stock',   [AdminDashboardController::class, 'updateStock']);

        // ── Catégories ─────────────────────────────────────
        Route::post  ('/categories-materiel',         [CategorieMaterielController::class, 'store']);
        Route::put   ('/categories-materiel/{id}',    [CategorieMaterielController::class, 'update']);
        Route::delete('/categories-materiel/{id}',    [CategorieMaterielController::class, 'destroy']);

        // ── Photos ─────────────────────────────────────────
        Route::post  ('/photos-materiel',       [PhotoMaterielController::class, 'store']);
        Route::put   ('/photos-materiel/{id}',  [PhotoMaterielController::class, 'update']);
        Route::delete('/photos-materiel/{id}',  [PhotoMaterielController::class, 'destroy']);

        // ── Fonctions ──────────────────────────────────────
        Route::post  ('/fonctions',                    [FonctionController::class, 'store']);
        Route::put   ('/fonctions/{id}',               [FonctionController::class, 'update']);
        Route::delete('/fonctions/{id}',               [FonctionController::class, 'destroy']);
        Route::get   ('/fonctions/search',             [FonctionController::class, 'search']);
        Route::get   ('/fonctions/stats/with-count',   [FonctionController::class, 'withStats']);

        // ── Commandes (admin) ──────────────────────────────
        Route::get('/commandes',                 [AdminCommandeController::class, 'index']);
        Route::get('/commandes/{id}',            [AdminCommandeController::class, 'show']);
        Route::put('/commandes/{id}/statut',     [AdminCommandeController::class, 'updateStatut']);
    });
});