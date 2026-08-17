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
        $this->asegurarTabla();
        $archivos  = array_map('basename', glob(self::DIR . '/add_*.sql') ?: []);
        $stmt      = $this->db->query('SELECT filename FROM schema_migrations');
        $aplicadas = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        return array_values(array_diff($archivos, $aplicadas));
    }

    /**
     * Aplica automáticamente todas las migraciones add_*.sql pendientes.
     * Seguro e idempotente: ignora errores si las columnas ya existen.
     */
    public function ejecutarFaltantes(): void {
        $this->asegurarTabla();
        $archivos = glob(self::DIR . '/add_*.sql') ?: [];
        sort($archivos);

        $stmt = $this->db->query('SELECT filename FROM schema_migrations');
        $aplicadas = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        foreach ($archivos as $archivo) {
            $base = basename($archivo);
            if (in_array($base, $aplicadas, true)) {
                continue;
            }
            $sql = file_get_contents($archivo);
            if ($sql === false) {
                continue;
            }
            // Quitar sentencias USE para soportar cualquier nombre de BD configurado
            $sql = preg_replace('/^\s*USE\s+[^;]+;/mi', '', $sql);

            $sentencias = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($sentencias as $s) {
                if ($s === '') {
                    continue;
                }
                try {
                    $this->db->exec($s);
                } catch (Throwable $e) {
                    error_log("Auto-migracion ($base) aviso: " . $e->getMessage());
                }
            }

            try {
                $regStmt = $this->db->prepare('INSERT IGNORE INTO schema_migrations (filename) VALUES (?)');
                $regStmt->execute([$base]);
            } catch (Throwable $e) {
                // Ignore
            }
        }
    }

    private function asegurarTabla(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
                filename    VARCHAR(180) NOT NULL PRIMARY KEY,
                applied_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            // Ignore
        }
    }
}
