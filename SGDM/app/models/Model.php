<?php
/**
 * Clase base para todos los modelos (patrón MVC).
 * Provee acceso a PDO y operaciones CRUD genéricas.
 */
abstract class Model {

    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct() {
        require_once __DIR__ . '/../../config/database.php';
        $this->db = getDB();
    }

    /**
     * Busca un registro por su clave primaria.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Retorna todos los registros de la tabla.
     *
     * @return array
     */
    public function findAll(): array {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    /**
     * Inserta un nuevo registro.
     *
     * @param array $data Pares columna => valor.
     * @return int ID del registro insertado.
     */
    public function insert(array $data): int {
        $cols   = implode(', ', array_keys($data));
        $places = implode(', ', array_fill(0, count($data), '?'));
        $stmt   = $this->db->prepare(
            "INSERT INTO {$this->table} ({$cols}) VALUES ({$places})"
        );
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un registro existente.
     *
     * @param int   $id
     * @param array $data Pares columna => valor.
     * @return bool
     */
    public function update(int $id, array $data): bool {
        $set  = implode(' = ?, ', array_keys($data)) . ' = ?';
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    /**
     * Elimina un registro por ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([$id]);
    }
}
