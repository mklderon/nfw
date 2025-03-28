<?php

namespace Core\Http;

use Core\System\Container;
use Core\Exceptions\ValidationException;
use Core\Exceptions\NotFoundException;

class Router {
    protected $container;
    protected $routes = [];
    protected $globalMiddleware = [];

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function get($uri, $action, array $middleware = []) {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post($uri, $action, array $middleware = []) {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function put($uri, $action, array $middleware = []) {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }
    
    public function patch($uri, $action, array $middleware = []) {
        $this->addRoute('PATCH', $uri, $action, $middleware);
    }
    
    public function delete($uri, $action, array $middleware = []) {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }
    
    public function options($uri, $action, array $middleware = []) {
        $this->addRoute('OPTIONS', $uri, $action, $middleware);
    }

    protected function addRoute($method, $uri, $action, $middleware) {
        [$uriRegex, $parameters] = $this->createUriRegex($uri);
        $this->routes[] = [
            'method' => $method,
            'uri_regex' => $uriRegex,
            'parameters' => $parameters,
            'middleware' => $middleware,
            'action' => $action,
        ];
    }

    protected function createUriRegex($uri) {
        // Normalize URI
        $uri = trim($uri, '/');
        
        // Extract parameter names
        $parameters = [];
        if (preg_match_all('/{(\w+)}/', $uri, $matches)) {
            $parameters = $matches[1];
        }
        
        // Replace parameter patterns with regex capture groups
        $regex = preg_replace('/{(\w+)}/', '([^/]+)', $uri);
        
        // Escape special regex characters (but not our capture groups)
        $regex = str_replace(['/', '.'], ['\/', '\.'], $regex);
        
        // Build final regex
        $finalRegex = '/^\/?' . $regex . '\/?$/i';
        
        return [$finalRegex, $parameters];
    }

    public function addGlobalMiddleware($middlewareClass) {
        $this->globalMiddleware[] = $middlewareClass;
    }

    public function handle() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    
        // Obtener la ruta base desde el contenedor
        $basePath = $this->container->make('basePath');
        $basePath = trim($basePath, '/'); // Normalizar la ruta base
    
        // Eliminar el prefijo base del requestUri
        if ($basePath && strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        $requestUri = trim($requestUri, '/');
    
        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }
    
            if (preg_match($route['uri_regex'], $requestUri, $matches)) {
                $parameters = [];
                foreach ($route['parameters'] as $index => $paramName) {
                    $parameters[$paramName] = $matches[$index + 1];
                }
    
                $request = new Request();
                $response = new Response();
    
                try {
                    // Obtener middleware de la ruta
                    $middlewareInstances = [];
                    foreach ($route['middleware'] as $middlewareClass) {
                        $middlewareInstances[] = $this->container->make($middlewareClass);
                    }
    
                    // Obtener middleware global
                    $globalMiddlewareInstances = [];
                    foreach ($this->globalMiddleware as $globalMiddlewareClass) {
                        $globalMiddlewareInstances[] = $this->container->make($globalMiddlewareClass);
                    }
    
                    $handler = function () use ($route, $request, $response, $parameters) {
                        list($controllerClass, $method) = $route['action'];
                        $controller = $this->container->make($controllerClass);
                        return $controller->$method($request, $response, $parameters);
                    };
    
                    // Agregar middleware de la ruta
                    foreach ($middlewareInstances as $middleware) {
                        $handler = function () use ($middleware, $request, $response, $handler) {
                            return $middleware->handle($request, $response, $handler);
                        };
                    }
    
                    // Agregar middleware global
                    foreach ($globalMiddlewareInstances as $globalMiddleware) {
                        $handler = function () use ($globalMiddleware, $request, $response, $handler) {
                            return $globalMiddleware->handle($request, $response, $handler);
                        };
                    }
    
                    $responseObject = $handler();
                    if ($responseObject instanceof Response) {
                        $responseObject->send();
                    }
                } catch (ValidationException $e) {
                    $response->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'errors' => $e->getErrors()
                    ], 400)->send();
                } catch (NotFoundException $e) {
                    $response->json([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ], 404)->send();
                } catch (\Exception $e) {
                    $statusCode = $e->getCode() ?: 500; // Usar el código de la excepción si existe
                    $response->json([
                        'status' => 'error',
                        'message' => 'Internal server error',
                        'details' => $e->getMessage() // Quitar en producción
                    ], $statusCode)->send();
                }
                
                return; // Solo retornamos si encontramos una ruta que coincida
            }
        }
    
        // Si llegamos aquí, ninguna ruta coincidió, mostramos el error 404
        $response = new Response();
        $response->json(['error' => 'Not Found'], 404)->send();
    }
}