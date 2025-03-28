<?php

namespace Core\Middlewares;

use Core\Http\{Request, Response};
use Core\Exceptions\{DatabaseException, ValidationException, NotFoundException};

class ErrorHandlerMiddleware
{
    /**
     * Maneja la solicitud y captura cualquier excepción
     * 
     * @param Request $request Solicitud HTTP
     * @param Response $response Respuesta HTTP
     * @param callable $next Siguiente middleware en la cadena
     * @return Response
     */
    public function handle(Request $request, Response $response, callable $next)
    {
        try {
            // Ejecutar el siguiente middleware/controlador en la cadena
            return $next($request, $response);
        } catch (ValidationException $e) {
            return $response->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 400);
        } catch (DatabaseException $e) {
            return $response->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'details' => $e->getDetails()
            ], $e->getCode() ?: 500);
        } catch (NotFoundException $e) {
            return $response->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Determinar si estamos en modo debug
            $debug = $_ENV['APP_ENV'] !== 'production';
            
            $response_data = [
                'status' => 'error',
                'message' => $debug ? $e->getMessage() : 'Internal server error'
            ];
            
            // Incluir detalles adicionales solo en modo debug
            if ($debug) {
                $response_data['details'] = $e->getMessage();
                $response_data['file'] = $e->getFile();
                $response_data['line'] = $e->getLine();
            }
            
            return $response->json($response_data, $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }
}