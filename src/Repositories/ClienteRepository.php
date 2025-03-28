<?php

namespace App\Repositories;

class ClienteRepository extends BaseRepository
{
    protected $table = 'clientes';

    public function all()
    {
        return $this->db->query("SELECT * FROM {$this->getTableName()}")->fetchAll();
    }

    public function create(array $data)
    {
        return $this->executeSafely(
            function () use ($data) {
                return $this->db->save($this->getTableName(), $data);
            },
            'Error al crear el cliente'
        );
    }

    public function find($id)
    {
        return $this->executeSafely(
            function () use ($id) {
                $result = $this->db->queryRow("SELECT * FROM {$this->getTableName()} WHERE id_cliente = ?", [$id]);
                return $result ?: null;
            },
            'Error al buscar el cliente'
        );
    }

    public function findByCedula(string $cedula)
    {
        return $this->executeSafely(
            function () use ($cedula) {
                return $this->db->queryRow("SELECT * FROM {$this->getTableName()} WHERE cedula = ?", [$cedula]);
            },
            'Error al buscar el cliente por cédula'
        );
    }

    public function search(array $criteria): array
    {
        return $this->executeSafely(
            function () use ($criteria) {
                $query = "SELECT * FROM " . $this->getTableName() . " WHERE 1=1";
                $params = [];

                if (!empty($criteria['id'])) {
                    $query .= ' AND id_cliente = ?';
                    $params[] = $criteria['id'];
                }
                if (!empty($criteria['nombre'])) {
                    $query .= ' AND nombre LIKE ?';
                    $params[] = "%" . trim($criteria['nombre']) . "%";
                }
                if (!empty($criteria['apellidos'])) {
                    $query .= ' AND apellidos LIKE ?';
                    $params[] = "%" . trim($criteria['apellidos']) . "%";
                }
                if (!empty($criteria['cedula'])) {
                    $query .= ' AND cedula LIKE ?';
                    $params[] = "%" . trim($criteria['cedula']) . "%";
                }
                if (!empty($criteria['telefono'])) {
                    $query .= ' AND telefono LIKE ?';
                    $params[] = "%" . trim($criteria['telefono']) . "%";
                }

                $stmt = $this->db->query($query, $params);
                return $stmt->fetchAll();
            },
            'Error al buscar clientes'
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

                return $this->db->updateItem($this->getTableName(), 'id_cliente', $updateData);
            },
            'Error al actualizar el cliente'
        );
    }
}