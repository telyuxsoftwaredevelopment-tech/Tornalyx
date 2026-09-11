<?php
require_once __DIR__ . '/Model.php';

/**
 * Registro de qué migraciones .sql de database/migrations/ ya se aplicaron a
 * esta base (ver add_schema_migrations.sql). Cada add_*.sql nuevo se inserta a
 * sí mismo con INSERT IGNORE al final; este modelo compara esa tabla contra los
 * archivos que existen en el repo para que el panel de admin avise si falta
 * correr alguno, y guarda el motivo cuando una quedó a medio aplicar.
 */
class Migracion extends Model {
    protected string $table = 'schema_migrations';
    protected string $primaryKey = 'filename';

    private const DIR = __DIR__ . '/../database/migrations';

    /**
     * ¿La tabla tiene la columna content_hash? La agrega asegurarTabla(); en
     * una base vieja que no la tenga (y donde el ALTER falle) el modelo sigue
     * funcionando sin ella, solo que pierde el reintento automático.
     */
    private bool $tieneHash = false;

    /**
     * Códigos de error de MySQL/TiDB que significan "esto ya estaba hecho":
     * tabla existente (1050), columna duplicada (1060), índice repetido (1061),
     * fila duplicada (1062) y DROP de algo inexistente (1091). Re-ejecutar una
     * migración contra una base que ya la tiene los produce en masa, así que no
     * cuentan como falla; cualquier otro código sí.
     */
    private const CODIGOS_YA_APLICADO = [1050, 1060, 1061, 1062, 1091];

    /**
     * Migraciones add_*.sql que no están aplicadas del todo: las que nunca
     * corrieron y las que quedaron registradas con error.
     *
     * @return string[]
     */
    public function faltantes(): array {
        $this->asegurarTabla();
        $archivos = array_map('basename', glob(self::DIR . '/add_*.sql') ?: []);

        $stmt = $this->db->query('SELECT filename, error FROM schema_migrations');
        $registradas = $conError = [];
        foreach ($stmt ? $stmt->fetchAll() : [] as $fila) {
            $registradas[] = $fila['filename'];
            if (($fila['error'] ?? null) !== null) {
                $conError[] = $fila['filename'] . ' (quedó a medio aplicar: ' . $fila['error'] . ')';
            }
        }

        return array_values(array_merge(array_diff($archivos, $registradas), $conError));
    }

    /**
     * Parte un archivo .sql en sentencias.
     *
     * No alcanza con explode(';'): un punto y coma dentro de un comentario
     * parte el archivo al medio y manda a la base un pedazo de prosa. Pasó de
     * verdad con add_drop_sesiones.sql, cuyo comentario decía "no hay datos
     * que migrar; el ON DELETE...": el DROP TABLE quedó pegado a ese texto,
     * TiDB devolvió error de sintaxis y la migración quedó a medio aplicar en
     * producción.
     *
     * Este recorrido saca los comentarios (-- y # hasta fin de línea, y los
     * bloques) respetando las comillas, y corta solo en los ';' que quedan
     * fuera de un literal.
     *
     * @return string[] Sentencias no vacías, ya recortadas.
     */
    private function dividirSentencias(string $sql): array {
        $sentencias = [];
        $actual     = '';
        $largo      = strlen($sql);
        $comilla    = null;   // ', " o ` mientras estamos dentro de un literal

        for ($i = 0; $i < $largo; $i++) {
            $c   = $sql[$i];
            $sig = $sql[$i + 1] ?? '';

            if ($comilla !== null) {
                $actual .= $c;
                if ($c === '\\' && $comilla !== '`') {
                    $actual .= $sig;   // carácter escapado: se copia tal cual
                    $i++;
                } elseif ($c === $comilla) {
                    $comilla = null;
                }
                continue;
            }

            if ($c === "'" || $c === '"' || $c === '`') {
                $comilla = $c;
                $actual .= $c;
                continue;
            }

            // "--" solo abre comentario si le sigue espacio o fin de línea,
            // que es la regla de MySQL (así "5--1" sigue siendo una resta).
            $tras = $sql[$i + 2] ?? "\n";
            if (($c === '-' && $sig === '-'
                 && ($tras === ' ' || $tras === "\t" || $tras === "\n" || $tras === "\r"))
                || $c === '#') {
                while ($i < $largo && $sql[$i] !== "\n") {
                    $i++;
                }
                $actual .= "\n";
                continue;
            }

            if ($c === '/' && $sig === '*') {
                $fin = strpos($sql, '*/', $i + 2);
                $i   = $fin === false ? $largo : $fin + 1;
                continue;
            }

            if ($c === ';') {
                $sentencias[] = $actual;
                $actual = '';
                continue;
            }

            $actual .= $c;
        }
        $sentencias[] = $actual;

        // Las sentencias USE se descartan: la base ya viene elegida por la
        // conexión y su nombre es configurable (DB_NAME).
        return array_values(array_filter(
            array_map('trim', $sentencias),
            static fn(string $s): bool => $s !== '' && stripos($s, 'USE ') !== 0
        ));
    }

    /**
     * Aplica automáticamente todas las migraciones add_*.sql pendientes.
     * Idempotente: los errores de "ya existe" se ignoran, pero cualquier otro
     * queda anotado en la fila de la migración en vez de darla por buena.
     */
    public function ejecutarFaltantes(): void {
        $this->asegurarTabla();
        $archivos = glob(self::DIR . '/add_*.sql') ?: [];
        sort($archivos);

        $columnas = 'filename, error' . ($this->tieneHash ? ', content_hash' : '');
        $stmt     = $this->db->query("SELECT {$columnas} FROM schema_migrations");
        $previas  = [];
        foreach ($stmt ? $stmt->fetchAll() : [] as $fila) {
            $previas[$fila['filename']] = $fila;
        }

        foreach ($archivos as $archivo) {
            $base = basename($archivo);
            $sql  = file_get_contents($archivo);
            if ($sql === false) {
                continue;
            }
            $hash   = hash('sha256', $sql);
            $previa = $previas[$base] ?? null;

            if ($previa !== null) {
                // Ya aplicada y sin errores: no hay nada que hacer.
                if (($previa['error'] ?? null) === null) {
                    continue;
                }
                // Falló antes y el archivo sigue igual: reintentarla daría el
                // mismo error, y hacerlo en cada request sería DDL al pedo. Si
                // el archivo cambió (o no sabemos con qué contenido falló), se
                // reintenta: así una migración arreglada se aplica sola en el
                // deploy siguiente, sin tener que tocar la base a mano.
                if (($previa['content_hash'] ?? null) === $hash) {
                    continue;
                }
            }

            $errores = [];
            foreach ($this->dividirSentencias($sql) as $sentencia) {
                try {
                    $this->db->exec($sentencia);
                } catch (Throwable $e) {
                    $yaAplicado = $this->esYaAplicado($e);
                    error_log(sprintf('Auto-migracion (%s) %s: %s',
                        $base, $yaAplicado ? 'aviso' : 'ERROR', $e->getMessage()));
                    if (!$yaAplicado) {
                        $errores[] = $e->getMessage();
                    }
                }
            }

            // Se registra siempre, junto con el hash del contenido ejecutado:
            // es lo que distingue "falló y sigue igual" de "falló y alguien la
            // arregló", sin reintentar DDL en cada request.
            $this->registrar($base, $errores ? mb_substr(implode(' | ', $errores), 0, 500) : null, $hash);
        }
    }

    /**
     * Deja constancia de la migración y de su resultado (error = NULL si salió
     * limpia). Reescribe la fila si ya existía, para que un reintento exitoso
     * borre el error anterior.
     */
    private function registrar(string $archivo, ?string $error, ?string $hash = null): void {
        try {
            if ($this->tieneHash) {
                $stmt = $this->db->prepare(
                    'INSERT INTO schema_migrations (filename, error, content_hash) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE error = VALUES(error), content_hash = VALUES(content_hash)'
                );
                $stmt->execute([$archivo, $error, $hash]);
                return;
            }
            $stmt = $this->db->prepare(
                'INSERT INTO schema_migrations (filename, error) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE error = VALUES(error)'
            );
            $stmt->execute([$archivo, $error]);
        } catch (Throwable $e) {
            error_log('Auto-migracion: no se pudo registrar ' . $archivo . ': ' . $e->getMessage());
        }
    }
    /** ¿El error solo dice que el cambio ya estaba hecho? */
    private function esYaAplicado(Throwable $e): bool {
        $codigo = ($e instanceof PDOException && isset($e->errorInfo[1])) ? (int) $e->errorInfo[1] : 0;
        return in_array($codigo, self::CODIGOS_YA_APLICADO, true);
    }

    private function asegurarTabla(): void {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
                filename     VARCHAR(180) NOT NULL PRIMARY KEY,
                applied_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                error        VARCHAR(500) NULL DEFAULT NULL,
                content_hash CHAR(64)     NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            // Ignore
        }
        // Bases anteriores a estas columnas: se agregan una sola vez. El SELECT
        // evita mandar un ALTER condenado a fallar en cada request.
        foreach ([
            'error'        => 'VARCHAR(500) NULL DEFAULT NULL',
            'content_hash' => 'CHAR(64)     NULL DEFAULT NULL',
        ] as $columna => $definicion) {
            try {
                $this->db->query("SELECT {$columna} FROM schema_migrations LIMIT 1");
            } catch (Throwable $e) {
                try {
                    $this->db->exec("ALTER TABLE schema_migrations ADD COLUMN {$columna} {$definicion}");
                } catch (Throwable $e2) {
                    error_log("Auto-migracion: no se pudo agregar schema_migrations.{$columna}: " . $e2->getMessage());
                    if ($columna === 'content_hash') {
                        return;   // se sigue sin reintento automático
                    }
                }
            }
        }
        $this->tieneHash = true;
    }
}
