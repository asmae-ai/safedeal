<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers — OWASP Recommendations
 *
 * X-Content-Type-Options : empêche le MIME sniffing (XSS via fichiers)
 * X-Frame-Options : empêche le clickjacking
 * X-XSS-Protection : protection XSS navigateurs anciens
 * Strict-Transport-Security : force HTTPS (HSTS)
 * Content-Security-Policy : limite les sources de contenu autorisées
 * Referrer-Policy : contrôle les infos envoyées dans le header Referer
 * Permissions-Policy : désactive les APIs navigateur non nécessaires
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; frame-ancestors 'none'"
        );

        // Supprimer les headers qui exposent la stack technique
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}