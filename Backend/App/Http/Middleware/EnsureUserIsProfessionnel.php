<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsProfessionnel
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->type_id !== 2) {
            return response()->json(['message' => 'Accès réservé aux professionnels'], 403);
        }

        return $next($request);
    }
}