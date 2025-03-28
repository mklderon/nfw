<?php

namespace Core\Http;

class Response {
    protected $statusCode = 200;
    protected $headers = [];
    protected $content;
    protected $contentType = 'text/html';

    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode() {
        return $this->statusCode;
    }

    public function header($key, $value) {
        $this->headers[$key] = $value;
        return $this;
    }

    public function headers(array $headers) {
        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }
        return $this;
    }

    public function json($data, $statusCode = 200) {
        $this->contentType = 'application/json';
        $this->content = json_encode($data);
        $this->statusCode = $statusCode;
        return $this;
    }

    public function html($content, $statusCode = 200) {
        $this->contentType = 'text/html';
        $this->content = $content;
        $this->statusCode = $statusCode;
        return $this;
    }

    public function text($content, $statusCode = 200) {
        $this->contentType = 'text/plain';
        $this->content = $content;
        $this->statusCode = $statusCode;
        return $this;
    }

    public function send() {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        
        header("Content-Type: {$this->contentType}");
        
        echo $this->content;
        
        exit;
    }
}