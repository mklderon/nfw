<?php

namespace Core\Cache;

class FileCache
{
    private string $cacheDir;

    public function __construct(string $basePath)
    {
        $this->cacheDir = $basePath . '/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }
    
        $data = unserialize(file_get_contents($file));
        if ($data['expires_at'] < time()) {
            unlink($file); // Eliminar si está expirado
            return null;
        }
    
        return isset($data['encrypted']) && $data['encrypted'] 
            ? $this->decrypt($data['value']) 
            : $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl, bool $encrypt = false): void
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $encrypt ? $this->encrypt($value) : $value,
            'encrypted' => $encrypt,
            'expires_at' => time() + $ttl,
        ];
        file_put_contents($file, serialize($data));
    }

    private function getFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return $this->cacheDir . '/' . $safeKey . '.cache';
    }

    private function encrypt($data)
    {
        $encryptionKey = $_ENV['CACHE_ENCRYPTION_KEY'] ?? 'default_encryption_key';
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt(
            serialize($data),
            'AES-256-CBC',
            $encryptionKey,
            0,
            $iv
        );
        return ['iv' => base64_encode($iv), 'data' => $encrypted];
    }
    
    private function decrypt($data)
    {
        $encryptionKey = $_ENV['CACHE_ENCRYPTION_KEY'] ?? 'default_encryption_key';
        $iv = base64_decode($data['iv']);
        $decrypted = openssl_decrypt(
            $data['data'],
            'AES-256-CBC',
            $encryptionKey,
            0,
            $iv
        );
        return unserialize($decrypted);
    }
}