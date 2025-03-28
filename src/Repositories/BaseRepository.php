<?php

namespace App\Repositories;

use Core\Database\Db;
use Core\Exceptions\DatabaseException;

abstract class BaseRepository {
    protected $db;
    protected $table;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    /**
     * Ejecuta una operación de base de datos de forma segura y maneja excepciones.
     *
     * @param callable $operation Función que realiza la operación en la base de datos
     * @param string $errorMessage Mensaje personalizado para el error
     * @return mixed Resultado de la operación
     * @throws DatabaseException
     */
    protected function executeSafely(callable $operation, string $errorMessage = 'Operación de base de datos fallida')
    {
        try {
            return $operation();
        } catch (\PDOException $e) {
            throw new DatabaseException(
                message: $errorMessage,
                dbErrorMessage: $e->getMessage(),
                query: '', // Podrías pasar la consulta si la tienes disponible
                code: 500,
                previous: $e
            );
        }
    }

    public function all() {
        return $this->executeSafely(
            function () {
                return $this->db->query("SELECT * FROM {$this->table}")->fetchAll();
            },
            'Error al obtener todos los registros'
        );
    }

    public function find($id) {
        return $this->executeSafely(
            function () use ($id) {
                return $this->db->queryRow("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
            },
            'Error al buscar el registro'
        );
    }

    public function create(array $data) {
        return $this->executeSafely(
            function () use ($data) {
                return $this->db->save($this->table, $data);
            },
            'Error al crear el registro'
        );
    }

    public function update($id, array $data) {
        return $this->executeSafely(
            function () use ($id, $data) {
                $data['id'] = $id;
                return $this->db->updateItem($this->table, 'id', $data);
            },
            'Error al actualizar el registro'
        );
    }

    public function delete($id) {
        return $this->executeSafely(
            function () use ($id) {
                return $this->db->update("DELETE FROM {$this->table} WHERE id = ?", [$id]);
            },
            'Error al eliminar el registro'
        );
    }
}