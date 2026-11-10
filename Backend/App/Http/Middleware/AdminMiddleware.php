<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Vérifiez si l'utilisateur est authentifié et est un admin
        // Adaptez cette logique selon votre structure
        if (!$user || $user->type_id !== 3) { // Supposons que type_id = 3 est admin
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Admin seulement.'
            ], 403);
        }

        return $next($request);
    }
}