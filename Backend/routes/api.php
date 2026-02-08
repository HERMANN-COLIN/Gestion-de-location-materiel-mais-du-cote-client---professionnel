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

// ==================== ROUTES PUBLIQUES ====================

// Authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Catalogue public - Matériels
Route::get('/materiels', [MaterielController::class, 'index']);
Route::get('/materiels/populaires', [MaterielController::class, 'populaires']);
Route::get('/materiels/recents', [MaterielController::class, 'recents']); // NOUVEAU
Route::get('/materiels/{id}', [MaterielController::class, 'show']);
Route::get('/materiels/{id}/disponibilite', [MaterielController::class, 'disponibilite']);
Route::get('/materiels/search', [MaterielController::class, 'search']);

// Catégories de matériels (publiques) - AJOUT DES NOUVELLES ROUTES
Route::get('/categories-materiel', [CategorieMaterielController::class, 'index']);
Route::get('/categories-materiel/avec-vedette', [CategorieMaterielController::class, 'avecVedette']); // NOUVEAU
Route::get('/categories-materiel/{id}', [CategorieMaterielController::class, 'show']);
Route::get('/categories-materiel/{id}/materiels', [CategorieMaterielController::class, 'materiels']); // IMPORTANT: Route pour les matériels par catégorie
Route::get('/categories-materiel/search', [CategorieMaterielController::class, 'search']);

// Photos de matériels (publiques)
Route::get('/photos-materiel/{materielId}', [PhotoMaterielController::class, 'getByMateriel']);

// Communes (pour le formulaire d'inscription)
Route::get('/communes', [CommuneController::class, 'index']);

// Langues (pour le formulaire d'inscription)
Route::get('/langues', [LangueController::class, 'index']);

// Types d'utilisateurs (pour le formulaire d'inscription)
Route::get('/types', [TypeController::class, 'index']);

// Fonctions (publiques pour la lecture)
Route::get('/fonctions', [FonctionController::class, 'index']);
Route::get('/fonctions/{id}', [FonctionController::class, 'show']);

// ==================== ROUTES PROTÉGÉES ====================

Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    
    // ============ GESTION DES MATÉRIELS (ADMIN) ============
    Route::middleware('admin')->group(function () {
        // Matériels
        Route::post('/materiels', [MaterielController::class, 'store']);
        Route::put('/materiels/{id}', [MaterielController::class, 'update']);
        Route::delete('/materiels/{id}', [MaterielController::class, 'destroy']);
        Route::post('/materiels/{id}/stock', [MaterielController::class, 'updateStock']);
        
        // Catégories de matériels
        Route::post('/categories-materiel', [CategorieMaterielController::class, 'store']);
        Route::put('/categories-materiel/{id}', [CategorieMaterielController::class, 'update']);
        Route::delete('/categories-materiel/{id}', [CategorieMaterielController::class, 'destroy']);
        
        // Photos de matériels
        Route::post('/photos-materiel', [PhotoMaterielController::class, 'store']);
        Route::put('/photos-materiel/{id}', [PhotoMaterielController::class, 'update']);
        Route::delete('/photos-materiel/{id}', [PhotoMaterielController::class, 'destroy']);
        
        // Fonctions admin (compléments)
        Route::post('/fonctions', [FonctionController::class, 'store']);
        Route::put('/fonctions/{id}', [FonctionController::class, 'update']);
        Route::delete('/fonctions/{id}', [FonctionController::class, 'destroy']);
        Route::get('/fonctions/search', [FonctionController::class, 'search']);
        Route::get('/fonctions/stats/with-count', [FonctionController::class, 'withStats']);
    });
    
    // ============ COMMANDES ET PANIER (CLIENTS) ============
    Route::prefix('commandes')->group(function () {
        Route::get('/', [CommandeController::class, 'index']);
        Route::post('/', [CommandeController::class, 'store']);
        Route::get('/{id}', [CommandeController::class, 'show']);
        Route::put('/{id}/annuler', [CommandeController::class, 'annuler']);
        Route::get('/{id}/facture', [CommandeController::class, 'genererFacture']);
    });
    
    // Panier
    Route::prefix('panier')->group(function () {
        Route::get('/', [CommandeController::class, 'getPanier']);
        Route::post('/ajouter', [CommandeController::class, 'ajouterAuPanier']);
        Route::put('/mise-a-jour/{itemId}', [CommandeController::class, 'mettreAJourPanier']);
        Route::delete('/supprimer/{itemId}', [CommandeController::class, 'supprimerDuPanier']);
        Route::delete('/vider', [CommandeController::class, 'viderPanier']);
        Route::post('/valider', [CommandeController::class, 'validerPanier']);
    });
    
    // ============ CONTACTS PROFESSIONNELS ============
    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactProController::class, 'index']);
        Route::post('/', [ContactProController::class, 'store']);
        Route::get('/{id}', [ContactProController::class, 'show']);
        Route::put('/{id}', [ContactProController::class, 'update']);
        Route::delete('/{id}', [ContactProController::class, 'destroy']);
        Route::post('/{id}/set-primary', [ContactProController::class, 'setAsPrimary']);
    });
});