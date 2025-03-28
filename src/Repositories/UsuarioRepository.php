<?php

namespace App\Repositories;

class UsuarioRepository extends BaseRepository
{
    protected $table = 'usuarios';

    // Método específico para buscar por email
    public function findByEmail(string $email)
    {
        return $this->executeSafely(
            function () use ($email) {
                return $this->db->queryRow("SELECT * FROM {$this->table} WHERE email = ?", [$email]);
            },
            'Error al buscar el usuario por email'
        );
    }

    public function getAll()
    {
        return $this->db->query("SELECT * FROM {$this->table}")->fetchAll();
    }

    // Sobrescribimos getById para usar id_usuario en lugar de id
    public function getById($id)
    {
        return $this->executeSafely(
            function () use ($id) {
                return $this->db->queryRow("SELECT * FROM {$this->table} WHERE id_usuario = ?", [$id]);
            },
            'Error al buscar el usuario'
        );
    }

    public function create(array $data)
    {
        return $this->executeSafely(
            function () use ($data) {
                // Encriptar la contraseña si está presente
                if (isset($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }

                // Insertar en la base de datos
                return $this->db->save($this->table, $data);
            },
            'Error al crear el usuario'
        );
    }

    public function update($id, array $data)
    {
        return $this->executeSafely(
            function () use ($id, $data) {
                $data['id_usuario'] = $id; // Ajustamos a id_usuario en lugar de id

                // Encriptar la contraseña si está presente
                if (isset($data['password'])) {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }                

                return $this->db->updateItem($this->table, 'id_usuario', $data);
            },
            'Error al actualizar el usuario'
        );
    }
}