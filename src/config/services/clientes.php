<?php

return [
    'service' => \App\Services\ClienteService::class,
    'repository' => \App\Repositories\ClienteRepository::class,
    'validation' => [
        'readAll' => [],
        'create' => [
            'cedula' => ['required', 'numeric'],
            'nombre' => ['required', 'min:3', 'max:50'],
            'apellidos' => ['required', 'min:3', 'max:50'],
            'direccion' => ['required'],
            'barrio' => ['required'],
            'telefono' => ['required', 'numeric'],
            'email' => ['required', 'email'],
            'estado' => ['required'],
        ],
        'search' => [
            'nombre' => ['min:3', 'max:50'],
            'apellidos' => ['min:3', 'max:50'],
            'cedula' => ['numeric'],
            'telefono' => ['numeric'],
        ],
        'read' => [
            'id' => ['required', 'numeric'],
        ],
        'update' => [
            'id' => ['required', 'numeric'],
            'cedula' => ['numeric'],
            'nombre' => ['min:3', 'max:50'],
            'apellidos' => ['min:3', 'max:50'],
            'direccion' => [],
            'barrio' => [],
            'telefono' => ['numeric'],
            'email' => ['email'],
            'estado' => [],
        ],
        'delete' => [
            'id' => ['required', 'numeric'],
        ],
    ],
    'dependencies' => [
        'jwtService' => \Core\Services\JwtService::class,
        'cache' => \Core\Cache\FileCache::class,
        'logger' => \Core\Logging\Logger::class,
    ],
];
