<?php

use App\Controllers\AuthController;
use App\Controllers\ProtectedController;
use App\Middleware\JwtMiddleware;

$router->post('/api/auth/login', [AuthController::class, 'login']);

/** Área Administrativa */

// Ruta GET para obtener todos los usuarios
$router->get('/api/usuarios', [App\Controllers\UsuariosController::class, 'readAll'], [JwtMiddleware::class]);
// Ruta POST para crear un usuario
$router->post('/api/usuarios', [App\Controllers\UsuariosController::class, 'create'], [JwtMiddleware::class]);
// Ruta GET para obtener un usuario por ID
$router->get('/api/usuarios/{id}', [App\Controllers\UsuariosController::class, 'read'], [JwtMiddleware::class]);
// Ruta UPDATE para actualizar un usuario por ID
$router->put('/api/usuarios/{id}', [App\Controllers\UsuariosController::class, 'update'], [JwtMiddleware::class]);
$router->patch('/api/usuarios/{id}', [App\Controllers\UsuariosController::class, 'update'], [JwtMiddleware::class]);
// Añadir esta línea después de las rutas existentes para usuarios
$router->delete('/api/usuarios/{id}', [App\Controllers\UsuariosController::class, 'delete'], [JwtMiddleware::class]);

// Clientes
// Ruta GET para obtener todos los clientes
$router->get('/api/clientes', [App\Controllers\ClientesController::class, 'readAll'], [JwtMiddleware::class]);
// Ruta POST para crear un cliente
$router->post('/api/clientes', [App\Controllers\ClientesController::class, 'create'], [JwtMiddleware::class]);
// Ruta GET para obtener un cliente por los criterios ( id - nombre - apellidos - cedula - telefono )
$router->get('/api/clientes/buscar', [App\Controllers\ClientesController::class, 'search'], [JwtMiddleware::class]);
// Ruta GET para obtener un cliente por ID
$router->get('/api/clientes/{id}', [\App\Controllers\ClientesController::class, 'read'], [JwtMiddleware::class]);
// Ruta DELETE para eliminar un cliente por ID
$router->delete('/api/clientes/{id}', [\App\Controllers\ClientesController::class, 'delete'], [JwtMiddleware::class]);
// Ruta PUT para actualizar un cliente por ID
$router->put('/api/clientes/{id}', [\App\Controllers\ClientesController::class, 'update'], [JwtMiddleware::class]);
// Ruta PATCH para actualizar un cliente por ID
$router->patch('/api/clientes/{id}', [\App\Controllers\ClientesController::class, 'update'], [JwtMiddleware::class]);

// Ventas
// Ruta GET para obtener todas las ventas
$router->get('/api/ventas', [App\Controllers\VentasController::class,'readAll'], [JwtMiddleware::class]);
// Ruta GET para obtener una venta por ID
$router->get('/api/ventas/{id}', [\App\Controllers\VentasController::class,'read'], [JwtMiddleware::class]);
// Ruta para acutalizar el estado de una venta
$router->put('/api/ventas/{id}', [\App\Controllers\VentasController::class, 'update'], [JwtMiddleware::class]);



// // Ruta protegida con middleware de autenticación
// // $router->get( '/api/protected', [ App\Controllers\ProtectedController::class, 'index' ], [
// //     App\Middleware\AuthMiddleware::class
// // ] );

// // Ruta para login
// $router->post( '/api/login', [ AuthController::class, 'login' ] );

// // Ruta protegida con JWT
// $router->get( '/api/protected', [ ProtectedController::class, 'index' ], [ JwtMiddleware::class ] );
