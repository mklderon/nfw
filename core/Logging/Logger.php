<?php

namespace Core\Logging;

use Exception;

class Logger
{
    private string $logDir;
    private string $logFile;

    public function __construct(string $basePath)
    {
        $this->logDir = $basePath . '/logs';
        $this->logFile = $this->logDir . '/app_' . date('Y-m-d') . '.log';

        if (!is_dir($this->logDir)) {
            if (!mkdir($this->logDir, 0777, true) && !is_dir($this->logDir)) {
                throw new Exception("No se pudo crear el directorio de logs: {$this->logDir}");
            }
        }
        if (!is_writable($this->logDir)) {
            throw new Exception("El directorio de logs no es escribible: {$this->logDir}");
        }
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }

    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
    }

    public function cleanOldLogs(int $days = 7): void
    {
        foreach (glob($this->logDir . '/app_*.log') as $file) {
            if (filemtime($file) < time() - ($days * 86400)) {
                unlink($file);
            }
        }
    }

    private function log(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        if (file_put_contents($this->logFile, $logEntry, FILE_APPEND) === false) {
            error_log("No se pudo escribir en el archivo de log: {$this->logFile}");
        }
    }
}