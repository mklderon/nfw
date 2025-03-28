<?php

namespace App\Repositories;

class VentaRepository extends BaseRepository
{
    protected $table = 'ventas';

    public function getAll()
    {
        return $this->db->query("SELECT * FROM {$this->table}")->fetchAll();
    }

    public function getById($id)
    {
        return $this->executeSafely(
            function () use ($id) {
                return $this->db->queryRow("SELECT * FROM {$this->table} WHERE id_venta = ?", [$id]);
            },
            'Error al buscar el venta'
        );
    }

    public function update($id, array $data)
    {
        return $this->executeSafely(
            function () use ($id, $data) {
                $data['id_venta'] = $id; // Ajustamos a id_venta

                // Filtrar datos para incluir solo campos válidos
                $allowedFields = ['id_venta', 'id_sucursal', 'descuento', 'total', 'status', 'nota', 'mediopago', 'metodo_pago', 'estado', 'id_cliente', 'id_usuario', 'cajero', 'vendedor', 'created_at', 'updated_at'];
                $updateData = array_intersect_key($data, array_flip($allowedFields));

                if (empty($updateData)) {
                    return 0; // Nada que actualizar
                }

                return $this->db->updateItem($this->table, 'id_venta', $updateData);
            },
            'Error al actualizar el venta'
        );
    }
}