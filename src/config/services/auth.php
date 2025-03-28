<?php

return [
    'service' => \App\Services\AuthService::class,
    'repository' => \App\Repositories\UsuarioRepository::class,
    'validation' => [
        'login' => [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:3', 'max:50'],
        ]
    ],
    'dependencies' => [
        'jwtService' => \Core\Services\JwtService::class,
        'cache' => \Core\Cache\FileCache::class,
        'logger' => \Core\Logging\Logger::class,
    ],
];