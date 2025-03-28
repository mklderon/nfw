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

    public function generateToken($payload, $expiration = null) {
        $issuedAt = time();
        $defaultExpiration = isset($_ENV['JWT_EXPIRATION']) ? (int)$_ENV['JWT_EXPIRATION'] : 3600; // Por ejemplo, 1 hora por defecto
        $expireAt = $expiration !== null ? $issuedAt + $expiration : $issuedAt + $defaultExpiration;
    
        $tokenData = [
            'iat'  => $issuedAt,
            'exp'  => $expireAt,
            'data' => $payload
        ];
    
        return JWT::encode($tokenData, $this->secretKey, $this->algorithm);
    }    

    public function generateRefreshToken($payload, $expiration = 86400) { // 24 horas por defecto
        return $this->generateToken($payload, $expiration);
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