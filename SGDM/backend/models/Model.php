<?php
/**
 * Clase base para todos los modelos (patrón MVC).
 * Provee acceso a PDO y operaciones CRUD genéricas.
 */
abstract class Model {

    protected string $table;
    protected string $primaryKey = 'id';

    /** Conexión PDO; se crea de forma perezosa en el primer acceso a $this->db. */
    private ?PDO $pdo = null;

    /**
     * Acceso perezoso a la conexión de base de datos.
     *
     * Como $db no es una propiedad real, leer $this->db dispara este getter,
     * que abre la conexión solo la primera vez que realmente se necesita. Así,
     * instanciar un modelo (p. ej. desde un controlador) no abre conexión: las
     * rutas que no ejecutan consultas —como mostrar el formulario de login—
     * funcionan aunque la base de datos esté caída.
     *
     * @param string $name
     * @return PDO
     */
    public function __get(string $name): PDO {
        if ($name === 'db') {
            if ($this->pdo === null) {
                require_once __DIR__ . '/../config/database.php';
                $this->pdo = getDB();
            }
            return $this->pdo;
        }
        throw new Error(sprintf('Propiedad indefinida: %s::$%s', static::class, $name));
    }

    /**
     * Busca un registro por su clave primaria.
     *
     * @param int $id
     * @return array|null
    /**
     * Entrecomilla un identificador (tabla o columna) para interpolarlo en el
     * SQL. Los nombres de columna de insert()/update() salen de las claves del
     * array que pasa el llamador, y esas claves no pueden ir como parámetro
     * preparado: PDO solo parametriza valores, no identificadores.
     *
     * Hoy todos los llamadores usan claves literales escritas a mano, así que
     * no hay inyección posible; esto hace que siga sin haberla aunque alguien
     * más adelante arme el array desde $_POST. El backtick duplicado es la
     * forma de escaparlo en MySQL (`a``b` es el identificador a`b).
     */
    private function quoteId(string $identificador): string {
        return '`' . str_replace('`', '``', $identificador) . '`';
    }

    /**
     * Busca un registro por su clave primaria.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM ' . $this->quoteId($this->table)
            . ' WHERE ' . $this->quoteId($this->primaryKey) . ' = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Inserta un nuevo registro.
     *
     * @param array $data Pares columna => valor.
     * @return int ID del registro insertado.
     */
    public function insert(array $data): int {
        $cols   = implode(', ', array_map([$this, 'quoteId'], array_keys($data)));
        $places = implode(', ', array_fill(0, count($data), '?'));
        $stmt   = $this->db->prepare(
            'INSERT INTO ' . $this->quoteId($this->table)
            . " ({$cols}) VALUES ({$places})"
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
        $set = implode(', ', array_map(
            fn(string $col): string => $this->quoteId($col) . ' = ?',
            array_keys($data)
        ));
        $stmt = $this->db->prepare(
            'UPDATE ' . $this->quoteId($this->table)
            . " SET {$set} WHERE " . $this->quoteId($this->primaryKey) . ' = ?'
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
            'DELETE FROM ' . $this->quoteId($this->table)
            . ' WHERE ' . $this->quoteId($this->primaryKey) . ' = ?'
        );
        return $stmt->execute([$id]);
    }
}
