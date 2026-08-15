<?php
require_once __DIR__ . '/Model.php';

/**
 * Registro de qué migraciones .sql de database/migrations/ ya se
 * aplicaron a esta base (ver add_schema_migrations.sql). Cada add_*.sql
 * nuevo se inserta a sí mismo con INSERT IGNORE al final; este modelo
 * compara esa tabla contra los archivos que existen en el repo para que
 * el panel de admin pueda avisar si falta correr alguno.
 */
class Migracion extends Model {
    protected string $table = 'schema_migrations';
    protected string $primaryKey = 'filename';

    private const DIR = __DIR__ . '/../database/migrations';

    /**
     * Migraciones incrementales (add_*.sql) que existen en el repo pero no
     * figuran como aplicadas en la base.
     *
     * @return string[]
     */
    public function faltantes(): array {
        $archivos  = array_map('basename', glob(self::DIR . '/add_*.sql') ?: []);
        $aplicadas = $this->db->query('SELECT filename FROM schema_migrations')
            ->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_diff($archivos, $aplicadas));
    }
}
