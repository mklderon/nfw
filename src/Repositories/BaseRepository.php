<?php

namespace App\Repositories;

use Core\Database\Db;
use Core\Exceptions\DatabaseException;

abstract class BaseRepository {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $allowedFields = [];
    protected $searchableFields = [];

    public function __construct(Db $db) {
        $this->db = $db;
    }

    /**
     * Obtiene el nombre de la tabla con protección contra inyección SQL
     * 
     * @return string
     */
    protected function getTableName(): string
    {
        return Db::sanitizeIdentifier($this->table);
    }

    /**
     * Obtiene el nombre de la clave primaria
     * 
     * @return string
     */
    protected function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Obtiene los campos permitidos para escritura en la base de datos
     * 
     * @return array
     */
    protected function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    /**
     * Filtra los campos permitidos de un conjunto de datos
     * 
     * @param array $data Datos a filtrar
     * @return array Datos filtrados
     */
    protected function filterAllowedFields(array $data): array
    {
        $allowedFields = $this->getAllowedFields();
        
        if (empty($allowedFields)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($allowedFields));
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

    /**
     * Obtiene todos los registros
     * 
     * @return array
     */
    public function all() {
        return $this->executeSafely(
            function () {
                return $this->db->query("SELECT * FROM {$this->getTableName()}")->fetchAll();
            },
            'Error al obtener todos los registros'
        );
    }

    /**
     * Obtiene registros con paginación
     * 
     * @param int $page Número de página (comienza en 1)
     * @param int $perPage Cantidad de registros por página
     * @return array [items, total]
     */
    public function paginate(int $page = 1, int $perPage = 15): array {
        return $this->executeSafely(
            function () use ($page, $perPage) {
                // Asegurar que page es >= 1
                $page = max(1, $page);
                $offset = ($page - 1) * $perPage;
                
                // Obtener los elementos de la página actual
                $items = $this->db->query(
                    "SELECT * FROM {$this->getTableName()} LIMIT ? OFFSET ?",
                    [$perPage, $offset]
                )->fetchAll();
                
                // Obtener el total de registros
                $total = (int) $this->db->queryValue("SELECT COUNT(*) FROM {$this->getTableName()}");
                
                return [
                    'items' => $items,
                    'total' => $total
                ];
            },
            'Error al obtener registros paginados'
        );
    }

    /**
     * Encuentra un registro por su ID
     * 
     * @param mixed $id
     * @return array|null
     */
    public function find($id) {
        return $this->executeSafely(
            function () use ($id) {
                return $this->db->queryRow(
                    "SELECT * FROM {$this->getTableName()} WHERE {$this->getPrimaryKey()} = ?", 
                    [$id]
                );
            },
            "Error al buscar el registro con ID: {$id}"
        );
    }

    /**
     * Verifica si existe un registro con el ID dado
     * 
     * @param mixed $id
     * @return bool
     */
    public function exists($id): bool {
        return $this->executeSafely(
            function () use ($id) {
                $count = $this->db->queryValue(
                    "SELECT COUNT(*) FROM {$this->getTableName()} WHERE {$this->getPrimaryKey()} = ?", 
                    [$id]
                );
                return (int)$count > 0;
            },
            "Error al verificar la existencia del registro con ID: {$id}"
        );
    }

    /**
     * Crea un nuevo registro
     * 
     * @param array $data
     * @return mixed ID del registro creado
     */
    public function create(array $data) {
        return $this->executeSafely(
            function () use ($data) {
                $filteredData = $this->filterAllowedFields($data);
                return $this->db->save($this->getTableName(), $filteredData);
            },
            'Error al crear el registro'
        );
    }

    /**
     * Actualiza un registro existente
     * 
     * @param mixed $id
     * @param array $data
     * @return int Número de filas afectadas
     */
    public function update($id, array $data) {
        return $this->executeSafely(
            function () use ($id, $data) {
                $filteredData = $this->filterAllowedFields($data);
                $filteredData[$this->getPrimaryKey()] = $id;
                
                return $this->db->updateItem(
                    $this->getTableName(), 
                    $this->getPrimaryKey(), 
                    $filteredData
                );
            },
            "Error al actualizar el registro con ID: {$id}"
        );
    }

    /**
     * Elimina un registro
     * 
     * @param mixed $id
     * @return int Número de filas afectadas
     */
    public function delete($id) {
        return $this->executeSafely(
            function () use ($id) {
                return $this->db->update(
                    "DELETE FROM {$this->getTableName()} WHERE {$this->getPrimaryKey()} = ?", 
                    [$id]
                );
            },
            "Error al eliminar el registro con ID: {$id}"
        );
    }

    /**
     * Realiza una búsqueda con criterios personalizados
     * 
     * @param array $conditions Condiciones para la búsqueda [campo => valor]
     * @param array $options Opciones adicionales (orderBy, limit, offset)
     * @return array
     */
    public function where(array $conditions, array $options = []) {
        return $this->executeSafely(
            function () use ($conditions, $options) {
                $sql = "SELECT * FROM {$this->getTableName()} WHERE 1=1";
                $params = [];
                
                // Añadir condiciones
                foreach ($conditions as $field => $value) {
                    // Si el valor es un array, usar operador personalizado
                    if (is_array($value) && isset($value['operator'])) {
                        $sql .= " AND {$field} {$value['operator']} ?";
                        $params[] = $value['value'];
                    } 
                    // Si el valor es null, usar IS NULL
                    else if ($value === null) {
                        $sql .= " AND {$field} IS NULL";
                    } 
                    // De lo contrario, usar igualdad
                    else {
                        $sql .= " AND {$field} = ?";
                        $params[] = $value;
                    }
                }
                
                // Añadir opciones adicionales
                if (!empty($options['orderBy'])) {
                    $sql .= " ORDER BY {$options['orderBy']}";
                    
                    if (!empty($options['order']) && in_array(strtoupper($options['order']), ['ASC', 'DESC'])) {
                        $sql .= " " . strtoupper($options['order']);
                    }
                }
                
                if (!empty($options['limit'])) {
                    $sql .= " LIMIT " . (int)$options['limit'];
                    
                    if (!empty($options['offset'])) {
                        $sql .= " OFFSET " . (int)$options['offset'];
                    }
                }
                
                // Ejecutar la consulta
                return $this->db->query($sql, $params)->fetchAll();
            },
            'Error al buscar registros con los criterios especificados'
        );
    }

    /**
     * Busca registros que coincidan con un término de búsqueda en campos específicos
     * 
     * @param string $searchTerm Término de búsqueda
     * @param array $fields Campos en los que buscar (opcional, usa searchableFields por defecto)
     * @return array
     */
    public function search(string $searchTerm, array $fields = []) {
        return $this->executeSafely(
            function () use ($searchTerm, $fields) {
                $fieldsToSearch = !empty($fields) ? $fields : $this->searchableFields;
                
                if (empty($fieldsToSearch)) {
                    throw new \InvalidArgumentException("No se han definido campos para búsqueda");
                }
                
                $sql = "SELECT * FROM {$this->getTableName()} WHERE ";
                $conditions = [];
                $params = [];
                
                foreach ($fieldsToSearch as $field) {
                    $conditions[] = "{$field} LIKE ?";
                    $params[] = "%{$searchTerm}%";
                }
                
                $sql .= "(" . implode(" OR ", $conditions) . ")";
                
                return $this->db->query($sql, $params)->fetchAll();
            },
            'Error al buscar registros que coincidan con el término de búsqueda'
        );
    }
    
    /**
     * Obtiene registros ordenados por un campo
     * 
     * @param string $field Campo por el cual ordenar
     * @param string $order Dirección de ordenamiento ('ASC' o 'DESC')
     * @param int $limit Límite de registros a retornar (opcional)
     * @return array
     */
    public function orderBy(string $field, string $order = 'ASC', int $limit = null) {
        return $this->executeSafely(
            function () use ($field, $order, $limit) {
                $sql = "SELECT * FROM {$this->getTableName()} ORDER BY {$field} ";
                $sql .= strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
                
                if ($limit !== null) {
                    $sql .= " LIMIT " . (int)$limit;
                }
                
                return $this->db->query($sql)->fetchAll();
            },
            "Error al obtener registros ordenados por {$field}"
        );
    }
    
    /**
     * Cuenta registros basado en condiciones
     * 
     * @param array $conditions Condiciones para contar
     * @return int
     */
    public function count(array $conditions = []): int {
        return $this->executeSafely(
            function () use ($conditions) {
                $sql = "SELECT COUNT(*) FROM {$this->getTableName()}";
                $params = [];
                
                if (!empty($conditions)) {
                    $sql .= " WHERE ";
                    $clauses = [];
                    
                    foreach ($conditions as $field => $value) {
                        if (is_array($value) && isset($value['operator'])) {
                            $clauses[] = "{$field} {$value['operator']} ?";
                            $params[] = $value['value'];
                        } else if ($value === null) {
                            $clauses[] = "{$field} IS NULL";
                        } else {
                            $clauses[] = "{$field} = ?";
                            $params[] = $value;
                        }
                    }
                    
                    $sql .= implode(" AND ", $clauses);
                }
                
                return (int) $this->db->queryValue($sql, $params);
            },
            "Error al contar registros"
        );
    }
    
    /**
     * Obtiene registros con relaciones
     * 
     * @param array $relations Definiciones de relaciones
     * @param array $conditions Condiciones adicionales
     * @return array
     */
    public function withRelations(array $relations, array $conditions = []) {
        // Este método es un ejemplo de cómo podría implementarse
        // En una implementación real, necesitarías adaptar esto a tu esquema de base de datos
        
        return $this->executeSafely(
            function () use ($relations, $conditions) {
                // Consulta base
                $mainTable = $this->getTableName();
                $primaryKey = $this->getPrimaryKey();
                
                // Primero obtenemos los registros base
                $items = empty($conditions) 
                    ? $this->all() 
                    : $this->where($conditions);
                
                if (empty($items)) {
                    return [];
                }
                
                // Para cada relación, cargamos los datos relacionados
                foreach ($relations as $relationName => $relationConfig) {
                    // Verificar que la configuración tenga los campos necesarios
                    if (!isset($relationConfig['table']) || !isset($relationConfig['foreignKey'])) {
                        continue;
                    }
                    
                    $relationTable = $relationConfig['table'];
                    $foreignKey = $relationConfig['foreignKey'];
                    $localKey = $relationConfig['localKey'] ?? $primaryKey;
                    
                    // Extraer IDs de los registros principales
                    $ids = array_column($items, $localKey);
                    
                    if (empty($ids)) {
                        continue;
                    }
                    
                    // Construir placeholders para los IDs
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    
                    // Obtener los registros relacionados
                    $relatedItems = $this->db->query(
                        "SELECT * FROM {$relationTable} WHERE {$foreignKey} IN ({$placeholders})",
                        $ids
                    )->fetchAll();
                    
                    // Organizar los registros relacionados por ID
                    $relatedItemsByKey = [];
                    foreach ($relatedItems as $relatedItem) {
                        $keyValue = $relatedItem[$foreignKey];
                        
                        if (!isset($relatedItemsByKey[$keyValue])) {
                            $relatedItemsByKey[$keyValue] = [];
                        }
                        
                        $relatedItemsByKey[$keyValue][] = $relatedItem;
                    }
                    
                    // Asignar los registros relacionados a cada registro principal
                    foreach ($items as &$item) {
                        $keyValue = $item[$localKey];
                        $item[$relationName] = $relatedItemsByKey[$keyValue] ?? [];
                    }
                }
                
                return $items;
            },
            "Error al cargar relaciones"
        );
    }
    
    /**
     * Transacción: ejecuta una serie de operaciones en una transacción
     * 
     * @param callable $callback Función a ejecutar dentro de la transacción
     * @return mixed Resultado del callback
     * @throws \Exception Si ocurre un error
     */
    public function transaction(callable $callback) {
        return $this->executeSafely(
            function () use ($callback) {
                $this->db->beginTransaction();
                
                try {
                    $result = $callback($this);
                    $this->db->commit();
                    return $result;
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    throw $e;
                }
            },
            "Error en la transacción"
        );
    }
}