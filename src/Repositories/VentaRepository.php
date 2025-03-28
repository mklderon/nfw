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
                $data['id_cliente'] = $id; // Ajustamos a id_cliente

                // Filtrar datos para incluir solo campos válidos
                $allowedFields = ['cedula', 'nombre', 'apellidos', 'direccion', 'barrio', 'telefono', 'email', 'estado', 'id_cliente'];
                $updateData = array_intersect_key($data, array_flip($allowedFields));

                if (empty($updateData)) {
                    return 0; // Nada que actualizar
                }

                return $this->db->updateItem($this->table, 'id_cliente', $updateData);
            },
            'Error al actualizar el cliente'
        );
    }
}