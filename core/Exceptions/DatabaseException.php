<?php

namespace Core\Exceptions;

use Exception;

class DatabaseException extends Exception {
    private $dbErrorMessage;
    private $query;

    public function __construct(string $message = 'Database error occurred', string $dbErrorMessage = '', string $query = '', int $code = 500, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->dbErrorMessage = $dbErrorMessage; // Mensaje original de la base de datos (ej. de PDO)
        $this->query = $query; // Consulta SQL que causó el error (opcional, útil para depuración)
    }

    // Obtener el mensaje de error original de la base de datos
    public function getDbErrorMessage(): string {
        return $this->dbErrorMessage;
    }

    // Obtener la consulta SQL que falló (si se proporcionó)
    public function getQuery(): string {
        return $this->query;
    }

    // Método para obtener todos los detalles en un formato útil (por ejemplo, para logs o respuestas JSON)
    public function getDetails(): array {
        return [
            'message' => $this->getMessage(),
            'db_error' => $this->dbErrorMessage,
            'query' => $this->query,
            'code' => $this->getCode()
        ];
    }
}