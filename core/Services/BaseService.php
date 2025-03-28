<?php

namespace Core\Services;

use Core\Http\{Request, Response};
use Core\Contracts\BaseServiceInterface;
use Core\Traits\DataTransformer;
use Core\Exceptions\{ValidationException, NotFoundException, AppException, DatabaseException};

abstract class BaseService implements BaseServiceInterface
{
    use DataTransformer;
    
    /**
     * Crea una respuesta exitosa con datos
     * 
     * @param Response $response Objeto de respuesta
     * @param mixed $data Datos a incluir en la respuesta
     * @param int $statusCode Código de estado HTTP
     * @return Response
     */
    protected function successResponse(Response $response, $data, int $statusCode = 200): Response
    {
        return $response->json([
            'status' => 'success',
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Crea una respuesta exitosa con mensaje
     * 
     * @param Response $response Objeto de respuesta
     * @param string $message Mensaje a incluir en la respuesta
     * @param int $statusCode Código de estado HTTP
     * @param array $additionalData Datos adicionales para incluir en la respuesta
     * @return Response
     */
    protected function messageResponse(Response $response, string $message, int $statusCode = 200, array $additionalData = []): Response
    {
        $responseData = [
            'status' => 'success',
            'message' => $message
        ];
        
        if (!empty($additionalData)) {
            $responseData = array_merge($responseData, $additionalData);
        }
        
        return $response->json($responseData, $statusCode);
    }
    
    /**
     * Crea una respuesta de error
     * 
     * @param Response $response Objeto de respuesta
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     * @param array $details Detalles adicionales del error
     * @return Response
     */
    protected function errorResponse(Response $response, string $message, int $statusCode = 400, array $details = []): Response
    {
        $responseData = [
            'status' => 'error',
            'message' => $message
        ];
        
        if (!empty($details)) {
            $responseData['details'] = $details;
        }
        
        return $response->json($responseData, $statusCode);
    }
    
    /**
     * Maneja las excepciones comunes y devuelve una respuesta apropiada
     * 
     * @param Response $response Objeto de respuesta
     * @param \Exception $exception La excepción capturada
     * @return Response
     */
    protected function handleException(Response $response, \Exception $exception): Response
    {
        if ($exception instanceof ValidationException) {
            return $this->errorResponse(
                $response,
                $exception->getMessage(),
                400,
                $exception->getErrors()
            );
        } else if ($exception instanceof NotFoundException) {
            return $this->errorResponse(
                $response,
                $exception->getMessage(),
                404
            );
        } else if ($exception instanceof DatabaseException) {
            // En producción no devolver detalles de errores de base de datos
            $details = $_ENV['APP_ENV'] === 'production' ? [] : $exception->getDetails();
            return $this->errorResponse(
                $response,
                $exception->getMessage(),
                $exception->getCode() ?: 500,
                $details
            );
        } else if ($exception instanceof AppException) {
            return $this->errorResponse(
                $response,
                $exception->getMessage(),
                $exception->getCode() ?: 400,
                ['error_code' => $exception->getErrorCode()]
            );
        } else {
            // Error genérico, posiblemente interno
            $message = $_ENV['APP_ENV'] === 'production' 
                ? 'Error interno del servidor' 
                : $exception->getMessage();
            
            // Registrar el error en el log del sistema
            error_log(sprintf(
                "[ERROR] %s: %s en %s:%d\n%s",
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ));
            
            return $this->errorResponse(
                $response,
                $message,
                500
            );
        }
    }
    
    /**
     * Valida que un ID sea numérico
     * 
     * @param mixed $id El ID a validar
     * @param string $paramName Nombre del parámetro para el mensaje de error
     * @throws ValidationException Si el ID no es numérico
     */
    protected function validarIdNumerico($id, string $paramName = 'id'): void
    {
        if (!is_numeric($id)) {
            throw new ValidationException([
                $paramName => ["El {$paramName} debe ser numérico"]
            ]);
        }
    }
    
    /**
     * Valida la existencia de campos requeridos en los datos
     * 
     * @param array $data Los datos a validar
     * @param array $camposRequeridos Lista de campos requeridos
     * @throws ValidationException Si algún campo requerido falta o está vacío
     */
    protected function validarCamposRequeridos(array $data, array $camposRequeridos): void
    {
        $errors = [];
        
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || $data[$campo] === '') {
                $errors[$campo] = ["El campo {$campo} es requerido"];
            }
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
    
    /**
     * Verifica que una entidad exista por su ID
     * 
     * @param mixed $id ID de la entidad
     * @param callable $finderFunction Función para buscar la entidad
     * @param string $entityName Nombre de la entidad para el mensaje de error
     * @throws NotFoundException Si la entidad no existe
     * @return mixed La entidad encontrada
     */
    protected function verificarExistencia($id, callable $finderFunction, string $entityName): mixed
    {
        $entity = $finderFunction($id);
        
        if (empty($entity)) {
            throw new NotFoundException("{$entityName} no encontrado con ID: {$id}");
        }
        
        return $entity;
    }
    
    /**
     * Procesa datos antes de enviarlos a la base de datos (sanitizar, transformar, etc)
     * 
     * @param array $data Datos a procesar
     * @param array $allowedFields Campos permitidos (opcional)
     * @return array Datos procesados
     */
    protected function procesarDatos(array $data, array $allowedFields = []): array
    {
        // Filtrar campos no permitidos si se especifica
        if (!empty($allowedFields)) {
            $data = array_intersect_key($data, array_flip($allowedFields));
        }
        
        // Puedes extender este método para hacer más procesamientos
        // como sanitizar valores, convertir tipos, etc.
        
        return $data;
    }
    
    /**
     * Verifica si un valor está en una lista de valores válidos
     * 
     * @param mixed $value Valor a verificar
     * @param array $validValues Lista de valores válidos
     * @param string $fieldName Nombre del campo para el mensaje de error
     * @throws ValidationException Si el valor no está en la lista de valores válidos
     */
    protected function validarValorEnLista($value, array $validValues, string $fieldName): void
    {
        if (!in_array($value, $validValues)) {
            throw new ValidationException([
                $fieldName => [
                    "Valor inválido. Valores permitidos: " . implode(', ', $validValues)
                ]
            ]);
        }
    }
    
    /**
     * Obtiene el nombre del recurso actual (inferido del nombre de la clase)
     * 
     * @return string
     */
    protected function getNombreRecurso(): string
    {
        $nombreClase = (new \ReflectionClass($this))->getShortName();
        return str_replace('Service', '', $nombreClase);
    }
    
    /**
     * Valida un formato de fecha
     * 
     * @param string $date Fecha a validar
     * @param string $format Formato esperado
     * @return bool True si la fecha tiene el formato correcto
     */
    protected function validarFormato($date, $format = 'Y-m-d'): bool
    {
        $datetime = \DateTime::createFromFormat($format, $date);
        return $datetime && $datetime->format($format) === $date;
    }
    
    /**
     * Método utilitario para procesar una respuesta paginada
     * 
     * @param Response $response Objeto de respuesta
     * @param array $items Los elementos de la página actual
     * @param int $total Total de elementos
     * @param int $page Página actual
     * @param int $perPage Elementos por página
     * @return Response
     */
    protected function paginatedResponse(Response $response, array $items, int $total, int $page, int $perPage): Response
    {
        $lastPage = ceil($total / $perPage);
        
        return $response->json([
            'status' => 'success',
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }
}