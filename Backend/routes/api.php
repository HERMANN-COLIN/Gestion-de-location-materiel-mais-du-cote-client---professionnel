<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MaterielController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\CommuneController;
use App\Http\Controllers\Api\LangueController;
use App\Http\Controllers\Api\FonctionController;
use App\Http\Controllers\Api\ContactProController;

// ==================== ROUTES PUBLIQUES ====================

// Authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Catalogue public
Route::get('/materiels', [MaterielController::class, 'index']);
Route::get('/materiels/{id}', [MaterielController::class, 'show']);

// Catégories (devrait être public)
Route::get('/categories', [CategorieController::class, 'index']);

// Communes (pour le formulaire d'inscription)
Route::get('/communes', [CommuneController::class, 'index']);

// Langues (pour le formulaire d'inscription)
Route::get('/langues', [LangueController::class, 'index']);

// Types d'utilisateurs (pour le formulaire d'inscription)
Route::get('/types', function() {
    return \App\Models\Type::all();
});

// Routes pour les fonctions (publiques pour la lecture)
Route::get('/fonctions', [FonctionController::class, 'index']);
Route::get('/fonctions/{id}', [FonctionController::class, 'show']);

// ==================== ROUTES PROTÉGÉES ====================

Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Matériels (admin seulement)
    Route::post('/materiels', [MaterielController::class, 'store']);
    Route::put('/materiels/{id}', [MaterielController::class, 'update']);
    Route::delete('/materiels/{id}', [MaterielController::class, 'destroy']);
    
    // Commandes
    Route::get('/commandes', [CommandeController::class, 'index']);
    Route::post('/commandes', [CommandeController::class, 'store']);
    Route::get('/commandes/{id}', [CommandeController::class, 'show']);
    
    // Panier temporaire
    Route::post('/panier/ajouter', [CommandeController::class, 'ajouterAuPanier']);
    Route::get('/panier', [CommandeController::class, 'getPanier']);
    Route::delete('/panier/{itemId}', [CommandeController::class, 'supprimerDuPanier']);
    
    // Routes pour les contacts professionnels
    Route::get('/contacts', [ContactProController::class, 'index']);
    Route::post('/contacts', [ContactProController::class, 'store']);
    Route::get('/contacts/{id}', [ContactProController::class, 'show']);
    Route::put('/contacts/{id}', [ContactProController::class, 'update']);
    Route::delete('/contacts/{id}', [ContactProController::class, 'destroy']);
    Route::post('/contacts/{id}/set-primary', [ContactProController::class, 'setAsPrimary']);
    
    // Routes admin (optionnelles)
    Route::middleware('admin')->group(function () {
        // Catégories admin
        Route::post('/categories', [CategorieController::class, 'store']);
        Route::put('/categories/{id}', [CategorieController::class, 'update']);
        Route::delete('/categories/{id}', [CategorieController::class, 'destroy']);
        
        // Fonctions admin (compléments)
        Route::post('/fonctions', [FonctionController::class, 'store']);
        Route::put('/fonctions/{id}', [FonctionController::class, 'update']);
        Route::delete('/fonctions/{id}', [FonctionController::class, 'destroy']);
        Route::get('/fonctions/search', [FonctionController::class, 'search']);
        Route::get('/fonctions/stats/with-count', [FonctionController::class, 'withStats']);
    });
});