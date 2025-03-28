<?php

require __DIR__ . '/vendor/autoload.php';

// Cargar las variables de entorno desde el archivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_ENV['APP_ENV'] === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

use Core\System\Container;
use Core\Http\Router;
use Core\Database\Db;
use Core\Services\JwtService;
use Core\Factories\ServiceFactory;
use Core\Cache\FileCache;
use Core\Logging\Logger;

// Configuración de la base de datos
$dbConfig = [
    'host' => $_ENV['HOST'] ?? 'localhost',
    'database' => $_ENV['DATABASE'] ?? 'your_db',
    'port' => $_ENV['PORT'] ?? '3306',
    'charset' => $_ENV['CHARSET'] ?? 'utf8mb4',
    'username' => $_ENV['USERNAME'] ?? 'root',
    'password' => $_ENV['PASSWORD'] ?? '',
];

// Crear una instancia del contenedor
$container = new Container();
$container->instance('basePath', $_ENV['BASE_PATH']);
$container->instance('relativePath', __DIR__);

// Registrar cualquier servicio necesario en el contenedor

$container->singleton(FileCache::class, function ($container) {
    $basePath = $container->get('relativePath');
    return new FileCache($basePath);
});

$container->singleton(Logger::class, function ($container) {
    $basePath = $container->get('relativePath');
    return new Logger($basePath);
});

// Registrar DB como singleton
$container->singleton(Db::class, function () use ($dbConfig) {
    return new Db($dbConfig);
});

// Registrar JwtService como singleton
$container->singleton(JwtService::class, function () {
    $secretKey = $_ENV['JWT_SECRET'] ?? 'default_secret_key'; // Clave por defecto para pruebas
    return new JwtService($secretKey);
});

// Cargar la configuración de servicios
$serviceConfig = require __DIR__ . '/src/config/services.php';

// Registrar ServiceFactory como singleton con la configuración
$container->singleton(ServiceFactory::class, function ($container) use ($serviceConfig) {
    return new ServiceFactory($container, $serviceConfig);
});

// Crear una instancia del enrutador
$router = new Router($container);

// Agregar middleware global de manejo de errores (debe ser el primero)
$router->addGlobalMiddleware(Core\Middlewares\ErrorHandlerMiddleware::class);

// Agregar middleware global de CORS
$router->addGlobalMiddleware(App\Middleware\CorsMiddleware::class);

// Cargar las rutas desde el archivo routes.php
require __DIR__ . '/src/routes/routes.php';

// Manejar la solicitud entrante
$router->handle();