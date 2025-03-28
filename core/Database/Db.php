<?php

namespace Core\Database;

use PDO;
use PDOException;

class Db {
    private $pdo;

    public function __construct($config) {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};port={$config['port']};charset={$config['charset']}";
        $options = [
            PDO::ATTR_PERSISTENT         => true,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function query($sql, $params = null) {
        // Registrar la consulta SQL y los parámetros para depuración
        error_log("SQL: " . $sql);
        error_log("Params: " . print_r($params, true));
        
        $stmt = $this->pdo->prepare($sql);
        
        // Verificar si $params es null
        if ($params === null) {
            $stmt->execute();
        } 
        // Si no es un array, convertirlo en uno o ejecutar sin parámetros
        else if (!is_array($params)) {
            error_log("Warning: params is not an array, it's a " . gettype($params));
            $stmt->execute();
        }
        // Si es un array vacío
        else if (empty($params)) {
            $stmt->execute();
        }
        // Si es un array, ejecutarlo normalmente
        else {
            $stmt->execute($params);
        }
        
        return $stmt;
    }

    public function queryRow($sql, $params = null) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function queryValue($sql, $params = null) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    public function update($sql, $params = null, $returnId = false) {
        $stmt = $this->query($sql, $params);
        return $returnId ? $this->pdo->lastInsertId() : $stmt->rowCount();
    }

    public function save($table, $data) {
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$keys}) VALUES ({$placeholders})";
        return $this->update($sql, array_values($data), true);
    }

    public function updateItem($table, $idField, $data) {
        $sql = "UPDATE {$table} SET ";
        $params = [];
        foreach ($data as $key => $value) {
            if ($key !== $idField) {
                $sql .= "{$key} = ?, ";
                $params[] = $value;
            }
        }
        $sql = rtrim($sql, ', ');
        $sql .= " WHERE {$idField} = ?";
        $params[] = $data[$idField];
        return $this->update($sql, $params);
    }

    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }

    public function commit() {
        $this->pdo->commit();
    }

    public function rollBack() {
        $this->pdo->rollBack();
    }

    public static function sanitizeIdentifier($identifier) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid identifier: $identifier");
        }
        return "`$identifier`";
    }
}