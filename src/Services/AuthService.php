<?php

namespace App\Services;

use Core\Http\{Request, Response};
use Core\Validation\Validator;
use Core\Exceptions\{ValidationException, NotFoundException};
use Core\Services\JwtService;
use Core\Cache\FileCache;
use Core\Logging\Logger;
use App\Contracts\AuthServiceInterface;
use App\Repositories\UsuarioRepository;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UsuarioRepository $repository,
        private Validator $validator,
        private JwtService $jwtService,
        private FileCache $cache,
        private Logger $logger
    ) {}

    public function login(Request $request, Response $response): Response
    {
        $dataLogin = $request->json();
        
        $this->validator->validate($dataLogin);

        // Guardar en caché
        $cacheKey = "user:email:{$dataLogin['email']}";
        $usuario = $this->cache->get($cacheKey);

        if ($usuario === null) {
            $usuario = $this->repository->findByEmail($dataLogin['email']);
            if (!empty($usuario)) {
                try {
                    $this->cache->set($cacheKey, $usuario, 3600);
                    $this->logger->info("Caché actualizado para {$dataLogin['email']}");
                } catch (Exception $e) {
                    $this->logger->error("Error al guardar en caché para {$dataLogin['email']}: " . $e->getMessage());
                }
            } else {
                $this->logger->warning("Usuario no encontrado para email {$dataLogin['email']}");
            }
        } else {
            $this->logger->info("Usuario {$dataLogin['email']} obtenido desde caché");
        }

        if (empty($usuario) || !password_verify($dataLogin['password'], $usuario['password'])) {
            $this->logger->error("Credenciales inválidas para email {$dataLogin['email']}");
            return $response->json(['error' => 'Credenciales inválidas'], 404);
        }

        if ($usuario['status'] !== 'activo') {
            $this->logger->warning("Usuario {$dataLogin['email']} no está activo");
            return $response->json(['error' => 'El usuario no está activo'], 404);
        }

        $payload = $this->formatUsuario($usuario);
        $accessToken = $this->jwtService->generateToken($payload);
        $refreshToken = $this->jwtService->generateRefreshToken($payload, 604800); // 7 dias
        $this->logger->info("Login exitoso para {$dataLogin['email']}");

        return $response->json([
            'message' => 'Login exitoso', 
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ]);
    }

    public function refreshToken(Request $request, Response $response) {
        $data = $request->json();
        $refreshToken = $data['refresh_token'] ?? '';

        if (!$refreshToken) {
            return $response->json(['error' => 'Refresh token no proporcionado'], 400);
        }

        $jwtService = new JwtService('tu_clave_secreta_aqui');
        try {
            $payload = $jwtService->verifyToken($refreshToken); // Verifica el Refresh Token
            $newAccessToken = $jwtService->generateToken($payload, 3600); // Nuevo Access Token

            return $response->json([
                'access_token' => $newAccessToken,
                'expires_in' => 3600
            ]);
        } catch (Exception $e) {
            return $response->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    private function formatUsuario(array $usuario): array
    {
        return [
            'id' => $usuario['id_usuario']
        ];
    }
}