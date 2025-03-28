<?php
namespace Core\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtService {
    private $secretKey;
    private $algorithm = 'HS256';

    public function __construct($secretKey) {
        $this->secretKey = $secretKey;
    }

    public function generateToken($payload) {
        $issuedAt = time();
        $expiration = $_ENV['EXPIRATION'];
        $expireAt = $issuedAt + $expiration;

        $tokenData = [
            'iat'  => $issuedAt,
            'exp'  => $expireAt,
            'data' => $payload
        ];

        return JWT::encode($tokenData, $this->secretKey, $this->algorithm);
    }

    public function generateRefreshToken($payload, $expiration = 604800) { // Nuevo método para Refresh Token
        return $this->generateToken($payload, $expiration); // 7 días por defecto
    }

    public function verifyToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded->data;
        } catch (Exception $e) {
            throw new Exception('Invalid or expired token', 401);
        }
    }
}