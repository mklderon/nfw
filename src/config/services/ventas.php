<?php

return [
    'service' => \App\Services\VentaService::class,
    'repository' => \App\Repositories\VentaRepository::class,
    'validation' => [
       'readAll' => [],
        'create' => [],
        'read' => [
            'id_venta' => ['required', 'numeric']
        ],
        'update' => [
            'id_venta' => ['required', 'numeric'],
            'status' => ['required', 'numeric'],
            'id_sucursal' => ['required', 'numeric'],
            'descuento' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
            'metodo_pago' => ['required'],
            'nota' => [],
            'cajero' => ['required'],
            'vendedor' => ['required']
        ],
    ],
];