<?php
// App\Middleware\CorsMiddleware.php

namespace App\Middleware;

use Core\Http\{Request, Response};

class CorsMiddleware {
    public function handle(Request $request, Response $response, $next) {
        // Configuración básica de CORS
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");
        
        // Si es una solicitud OPTIONS, responde directamente
        if ($request->method() === 'OPTIONS') {
            $response->setStatusCode(200);
            $response->send();
            return;
        }
        
        // Llama al siguiente middleware o controlador
        return $next();
    }
}