<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force Accept: application/json sur toutes les routes API.
 *
 * Pourquoi : sans ce middleware, Laravel peut retourner du HTML
 * en cas d'erreur si le header Accept n'est pas présent.
 * Critique pour la sécurité — ne jamais exposer de stack trace HTML.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
