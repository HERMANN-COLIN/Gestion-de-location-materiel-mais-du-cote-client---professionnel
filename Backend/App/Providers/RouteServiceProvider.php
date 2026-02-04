<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Le chemin vers la page "home" après authentification.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Définition des routes de l'application.
     */
    public function boot(): void
    {
        $this->routes(function () {

            // ===== ROUTES API =====
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // ===== ROUTES WEB =====
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
