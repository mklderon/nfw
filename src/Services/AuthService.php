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

        // Implementar limitación de intentos (rate limiting)
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $rateLimitKey = "login_attempts:{$ipAddress}";
        $attempts = (int)$this->cache->get($rateLimitKey) ?? 0;
        
        if ($attempts >= 5) {
            $this->logger->warning("Demasiados intentos fallidos desde {$ipAddress}");
            return $response->json([
                'error' => 'Demasiados intentos de inicio de sesión. Intente nuevamente más tarde'
            ], 429); // Too Many Requests
        }

        // Guardar en caché
        $cacheKey = "user:email:{$dataLogin['email']}";
        $usuario = $this->cache->get($cacheKey);

        if ($usuario === null) {
            $usuario = $this->repository->findByEmail($dataLogin['email']);
            if (!empty($usuario)) {
                try {
                    $this->cache->set($cacheKey, $usuario, 3600, true);
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
            // Incrementar contador de intentos fallidos
            $this->cache->set($rateLimitKey, $attempts + 1, 3600, true); // 1 hora y el true activa el cifrado
            $this->logger->error("Credenciales inválidas para email {$dataLogin['email']}");
            return $response->json(['error' => 'Credenciales inválidas'], 401); // Unauthorized
        }

        if ($usuario['status'] !== 'activo') {
            $this->logger->warning("Usuario {$dataLogin['email']} no está activo");
            return $response->json(['error' => 'El usuario no está activo'], 404);
        }
    
        // Resetear contador de intentos en login exitoso
        $this->cache->set($rateLimitKey, 0, 3600);

        $payload = $this->formatUsuario($usuario);
        $accessToken = $this->jwtService->generateToken($payload);
        $refreshToken = $this->jwtService->generateRefreshToken($payload, $_ENV['JWT_LIFESPAN']); // 2 dias
        $this->logger->info("Login exitoso para {$dataLogin['email']}");

        return $response->json([
            'message' => 'Login exitoso', 
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $_ENV['JWT_EXPIRATION']
        ]);
    }

    public function refreshToken(Request $request, Response $response) {
        $data = $request->json();
        $refreshToken = $data['refresh_token'] ?? '';

        if (!$refreshToken) {
            return $response->json(['error' => 'Refresh token no proporcionado'], 400);
        }

        $jwtService = new JwtService($_ENV['SECRET_KEY']);
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