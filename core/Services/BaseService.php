<?php

namespace Core\Services;

use Core\Http\{Request, Response};
use Core\Contracts\BaseServiceInterface;
use Core\Traits\DataTransformer;

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
     * @return Response
     */
    protected function messageResponse(Response $response, string $message, int $statusCode = 200): Response
    {
        return $response->json([
            'status' => 'success',
            'message' => $message
        ], $statusCode);
    }
    
    /**
     * Crea una respuesta de error
     * 
     * @param Response $response Objeto de respuesta
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     * @return Response
     */
    protected function errorResponse(Response $response, string $message, int $statusCode = 400): Response
    {
        return $response->json([
            'status' => 'error',
            'message' => $message
        ], $statusCode);
    }
    
    /**
     * Crea una respuesta para recurso no encontrado
     * 
     * @param Response $response Objeto de respuesta
     * @param string $resource Nombre del recurso
     * @return Response
     */
    protected function notFoundResponse(Response $response, string $resource = 'Recurso'): Response
    {
        return $response->json([
            'status' => 'error',
            'message' => "$resource no encontrado"
        ], 404);
    }
}