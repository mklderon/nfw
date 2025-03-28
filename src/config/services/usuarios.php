<?php

return [
    'service' => \App\Services\UsuarioService::class,
    'repository' => \App\Repositories\UsuarioRepository::class,
    'validation' => [
        'readAll' => [],
        'read' => [
            'id_usuario' => ['required', 'numeric'],
        ],
        'create' => [
            'cedula' => ['required', 'numeric'],
            'nombre' => ['required', 'min:3', 'max:50'],
            'apellidos' => ['required', 'min:3', 'max:50'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6', 'max:50'],
            'role' => ['required'],
            'status' => ['required'],
        ],
        'update' => [
            'id_usuario' => ['required', 'numeric'],
            'cedula' => ['numeric'],
            'email' => ['email'],
            'nombre' => ['min:3', 'max:50'],
            'apellidos' => ['min:3', 'max:50'],
            'password' => ['min:6', 'max:50'],
            'role' => [],
            'status' => [],
        ],
        'delete' => [
            'id_usuario' => ['required', 'numeric'],
        ],
    ],
];