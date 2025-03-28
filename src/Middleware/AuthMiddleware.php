<?php

namespace App\Middleware;

use Core\Http\{Request, Response};

class AuthMiddleware {
    public function handle(Request $request, Response $response, $next) {
        // Verificar si el usuario está autenticado
        $token = $request->header('Authorization');
        if (!$token || $token !== 'valid-token') {
            return $response->json(['error' => 'Unauthorized'], 401);
        }

        // Si está autenticado, continuar con la siguiente middleware o controlador
        return $next();
    }
}