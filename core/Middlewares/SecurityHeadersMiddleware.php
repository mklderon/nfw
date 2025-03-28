<?php

namespace Core\Middlewares;

use Core\Http\{Request, Response};

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Response $response, callable $next)
    {
        // Protección contra XSS
        header('X-XSS-Protection: 1; mode=block');

        // Prevenir que los navegadores detecten el tipo MIME incorrectamente
        header('X-Content-Type-Options: nosniff');

        // Control de iframes para prevenir clickjacking
        header('X-Frame-Options: DENY');

        // Política de seguridad de contenido
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self';");

        // Strict-Transport-Security para HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Prevenir exposición de información sensible
        header('X-Permitted-Cross-Domain-Policies: none');
        
        // Cache control para respuestas de API
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        // Continuar con la cadena de middleware
        return $next();
    }
}
