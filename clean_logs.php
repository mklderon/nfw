<?php

require __DIR__ . '/vendor/autoload.php';

use Core\System\Container;
use Core\Logging\Logger;

$container = new Container();
$container->instance('basePath', __DIR__);
$container->singleton(Logger::class, function ($container) {
    $basePath = $container->get('basePath');
    return new Logger($basePath);
});

$logger = $container->make(Logger::class);
$logger->cleanOldLogs(7); // Limpia logs mayores a 7 días

echo "Logs antiguos limpiados.\n";