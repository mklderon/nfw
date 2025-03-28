<?php

namespace App\Repositories;

class VentaRepository extends BaseRepository
{
    protected $table = 'ventas';
    protected $primaryKey = 'id_venta';

    // Definir los campos permitidos para operaciones de escritura
    protected $allowedFields = [
        'id_venta', 'id_sucursal', 'descuento', 'total', 'status',
        'nota', 'metodo_pago', 'estado', 'id_cliente', 'id_usuario',
        'cajero', 'vendedor', 'created_at', 'updated_at'
    ];

    // Definir campos para búsqueda
    protected $searchableFields = [
        'cajero', 'vendedor', 'metodo_pago', 'nota'
    ];

    /**
     * Obtiene todas las ventas
     */
    public function getAll()
    {
        return $this->all();
    }

    /**
     * Obtiene una venta por su ID
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * Obtiene ventas con filtros específicos
     */
    public function getVentas(array $filters = [], array $options = [])
    {
        // Construir condiciones basadas en filtros
        $conditions = [];

        if (!empty($filters['status'])) {
            $conditions['status'] = $filters['status'];
        }

        if (!empty($filters['id_cliente'])) {
            $conditions['id_cliente'] = $filters['id_cliente'];
        }

        if (!empty($filters['id_sucursal'])) {
            $conditions['id_sucursal'] = $filters['id_sucursal'];
        }

        if (!empty($filters['fecha_desde'])) {
            $conditions['created_at'] = [
                'operator' => '>=',
                'value' => $filters['fecha_desde']
            ];
        }

        if (!empty($filters['fecha_hasta'])) {
            $conditions['created_at'] = [
                'operator' => '<=',
                'value' => $filters['fecha_hasta']
            ];
        }

        // Configurar opciones de ordenamiento y paginación
        $queryOptions = [
            'orderBy' => $options['orderBy'] ?? 'created_at',
            'order' => $options['order'] ?? 'DESC'
        ];

        if (isset($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
        }

        if (isset($options['offset'])) {
            $queryOptions['offset'] = $options['offset'];
        }

        // Usar el método where heredado de BaseRepository
        return $this->where($conditions, $queryOptions);
    }

    /**
     * Obtiene los pagos asociados a una venta
     */
    public function getPagosByVentaId($ventaId)
    {
        return $this->executeSafely(
            function () use ($ventaId) {
                return $this->db->query(
                    'SELECT * FROM pagos WHERE id_venta = ?',
                    [$ventaId]
                )->fetchAll();
            },
            'Error al obtener los pagos de la venta'
        );
    }

    /**
     * Obtiene estadísticas de ventas por período
     */
    public function getEstadisticasPorPeriodo($fechaInicio, $fechaFin)
    {
        return $this->executeSafely(
            function () use ($fechaInicio, $fechaFin) {
                return $this->db->queryRow(
                    "SELECT 
                        COUNT(*) as total_ventas,
                        SUM(total) as monto_total,
                        AVG(total) as promedio_venta,
                        MIN(total) as venta_minima,
                        MAX(total) as venta_maxima
                    FROM {$this->getTableName()}
                    WHERE created_at BETWEEN ? AND ?",
                    [$fechaInicio, $fechaFin]
                );
            },
            'Error al obtener estadísticas de ventas'
        );
    }

    /**
     * Busca ventas por cliente
     */
    public function findByCliente($clienteId)
    {
        return $this->where(['id_cliente' => $clienteId], ['orderBy' => 'created_at', 'order' => 'DESC']);
    }

    /**
     * Cambia el estado de una venta
     */
    public function cambiarEstado($id, $nuevoEstado)
    {
        return $this->update($id, ['status' => $nuevoEstado]);
    }

    /**
     * Obtiene las ventas realizadas por un vendedor específico
     */
    public function getVentasPorVendedor($vendedor, $fechaInicio = null, $fechaFin = null)
    {
        $conditions = ['vendedor' => $vendedor];

        if ($fechaInicio) {
            $conditions['created_at'] = [
                'operator' => '>=',
                'value' => $fechaInicio
            ];
        }

        if ($fechaFin) {
            $conditions['created_at'] = [
                'operator' => '<=',
                'value' => $fechaFin
            ];
        }

        return $this->where($conditions, ['orderBy' => 'created_at', 'order' => 'DESC']);
    }

    /**
     * Obtiene ventas con detalles (utilizando relaciones)
     */
    public function getVentasConDetalle()
    {
        return $this->withRelations([
            'detalles' => [
                'table' => 'detalle_venta',
                'foreignKey' => 'id_venta'
            ],
            'cliente' => [
                'table' => 'clientes',
                'foreignKey' => 'id_cliente',
                'localKey' => 'id_cliente'
            ]
        ]);
    }

    /**
     * Obtiene las ventas con sus pagos
     */
    public function getVentasConPagos($ventaId = null)
    {
        $relations = [
            'pagos' => [
                'table' => 'pagos',
                'foreignKey' => 'id_venta'
            ]
        ];

        $conditions = [];
        if ($ventaId !== null) {
            $conditions[$this->getPrimaryKey()] = $ventaId;
        }

        return $this->withRelations($relations, $conditions);
    }

    /**
     * Crea una venta completa con sus detalles en una transacción
     */
    public function crearVentaCompleta(array $ventaData, array $detalles)
    {
        return $this->transaction(function ($repo) use ($ventaData, $detalles) {
            // Crear la venta
            $idVenta = $repo->create($ventaData);

            // Insertar detalles
            $db = $repo->db;
            foreach ($detalles as $detalle) {
                $detalle['id_venta'] = $idVenta;
                $db->save('detalle_venta', $detalle);
            }

            return $idVenta;
        });
    }
}
