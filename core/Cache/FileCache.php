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

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];
        file_put_contents($file, serialize($data));
    }

    private function getFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return $this->cacheDir . '/' . $safeKey . '.cache';
    }
}