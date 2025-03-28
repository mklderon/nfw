<?php

namespace Core\Middlewares;

use Core\Http\{Request, Response};

class ApiSecurityMiddleware
{
    public function handle(Request $request, Response $response, callable $next)
    {
        // 1. Limitar tamaño del payload
        $contentLength = (int) $request->header('Content-Length', 0);
        if ($contentLength > 1048576) { // 1MB
            return $response->json(['error' => 'Payload too large'], 413);
        }
        
        // 2. Validar Content-Type para solicitudes POST/PUT/PATCH
        $method = $request->method();
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->header('Content-Type', '');
            if (strpos($contentType, 'application/json') === false) {
                return $response->json(['error' => 'Content-Type must be application/json'], 415);
            }
        }
        
        // 3. Verificar que los tokens de API no estén en URL
        $url = $request->uri();
        if (preg_match('/token|api_key|jwt/i', $url)) {
            return $response->json(['error' => 'Security credentials should not be in URL'], 400);
        }
        
        return $next();
    }
}