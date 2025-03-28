<?php

namespace Core\Exceptions;

class AppException extends \Exception
{
    protected $errorCode;
    
    /**
     * Constructor de la excepción
     * 
     * @param string $message Mensaje de error
     * @param int $code Código HTTP
     * @param string $errorCode Código de error interno
     */
    public function __construct(string $message, int $code = 500, string $errorCode = 'GENERAL_ERROR')
    {
        parent::__construct($message, $code);
        $this->errorCode = $errorCode;
    }
    
    /**
     * Obtiene el código de error
     * 
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
    
    /**
     * Crea una excepción de recurso no encontrado
     * 
     * @param string $resource Nombre del recurso
     * @return static
     */
    public static function notFound(string $resource): self
    {
        return new static("$resource no encontrado", 404, 'RESOURCE_NOT_FOUND');
    }
    
    /**
     * Crea una excepción de operación inválida
     * 
     * @param string $message Mensaje de error
     * @return static
     */
    public static function invalidOperation(string $message): self
    {
        return new static($message, 400, 'INVALID_OPERATION');
    }
    
    /**
     * Crea una excepción de autorización
     * 
     * @param string $message Mensaje de error
     * @return static
     */
    public static function unauthorized(string $message = 'No autorizado'): self
    {
        return new static($message, 401, 'UNAUTHORIZED');
    }
}