<?php

namespace Core\Http;

class Request {
    protected $method;
    protected $uri;
    protected $queryParams;
    protected $postParams;
    protected $jsonParams;
    protected $headers;
    protected $files;
    protected $server;
    protected $attributes = [];

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->headers = $this->getHeaders();
        $this->jsonParams = $this->parseJsonInput();
    }

    protected function getHeaders() {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    protected function parseJsonInput() {
        $contentType = $this->header('Content-Type', '');
        $rawInput = file_get_contents('php://input');
    
        // Intentar decodificar si hay datos en php://input y parece JSON
        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE) { // Verificar que sea JSON válido
                return $decoded ?? [];
            }
        }
    
        return [];
    }    

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($key, $default = null) {
        return $this->attributes[$key] ?? $default;
    }

    public function method() {
        return $this->method;
    }

    public function uri() {
        return $this->uri;
    }

    public function query($key = null, $default = null) {
        return $this->getParams($key, $default, $this->queryParams, true);
    }

    public function post($key = null, $default = null) {
        return $this->getParams($key, $default, $this->postParams, true);
    }

    public function json($key = null, $default = null) {
        return $this->getParams($key, $default, $this->jsonParams, true);
    }

    public function input($key = null, $default = null) {
        $allParams = array_merge($this->postParams, $this->jsonParams);
        return $this->getParams($key, $default, $allParams, true);
    }

    // Método para obtener datos sin sanitizar (en caso de ser necesario)
    public function rawInput($key = null, $default = null) {
        $allParams = array_merge($this->postParams, $this->jsonParams);
        return $this->getParams($key, $default, $allParams, false);
    }

    public function file($key) {
        return $this->files[$key] ?? null;
    }

    public function header($key, $default = null) {
        $key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($key))));
        return $this->headers[$key] ?? $default;
    }
    
    public function getRawJson($key = null, $default = null) {
        return $this->getParams($key, $default, $this->jsonParams, 'raw');
    }
    
    public function getHtmlSafeInput($key = null, $default = null) {
        $allParams = array_merge($this->postParams, $this->jsonParams);
        return $this->getParams($key, $default, $allParams, 'html');
    }
    
    public function getSqlSafeInput($key = null, $default = null) {
        $allParams = array_merge($this->postParams, $this->jsonParams);
        return $this->getParams($key, $default, $allParams, 'sql');
    }

    protected function getParams($key, $default, $source, $sanitizeContext = 'default') {
        if ($key === null) {
            return $sanitizeContext !== false ? $this->sanitize($source, $sanitizeContext) : $source;
        }
        
        $value = $source[$key] ?? $default;
        return $sanitizeContext !== false ? $this->sanitize($value, $sanitizeContext) : $value;
    }

    private function sanitize($data, $context = 'default') {
        if (is_null($data)) {
            return null;
        }
        
        if (!is_array($data)) {
            if (is_string($data)) {
                switch ($context) {
                    case 'html':
                        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
                    case 'sql':
                        return trim(str_replace(['\\', "\0", "'", '"'], ['\\\\', '', "\'", '\"'], $data));
                    case 'raw':
                        return $data; // Sin sanitización
                    default:
                        return trim($data);
                }
            }
            return $data;
        }
    
        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitized[$key] = $this->sanitize($value, $context);
        }
        return $sanitized;
    }
}