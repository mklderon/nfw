<?php

namespace App\Middleware;

use Core\Http\{Request, Response};
use Core\Services\JwtService;
use Core\Logging\Logger;

class JwtMiddleware
{
    public function __construct(
        private JwtService $jwtService,
        private Logger $logger
    ) {}

    public function handle(Request $request, Response $response, callable $next) {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $response->json(['error' => 'Token no suministrado'], 401);
        }

        $token = $matches[1];

        try {
            $payload = $this->jwtService->verifyToken($token);
            $request->setAttribute('usuario', $payload); // Agregar el payload al request para usarlo en el controlador
            return $next();
        } catch (\Exception $e) {
            if ($e->getCode() === 401) {
                return $response->json([
                    'error' => 'Token inválido o expirado',
                    'message' => 'Por favor, usa el refresh token para obtener un nuevo access token'
                ], 401);
            }
            return $response->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}