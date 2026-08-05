<?php
/**
 * Aplicador de migraciones de base de datos.
 *
 * Lleva un registro de qué archivos de SGDM/backend/database/migrations/ ya se
 * ejecutaron (tabla `migraciones`) y aplica solo los pendientes, en orden. Sin
 * esto, una base creada antes de que se agregara una migración se queda atrás
 * en silencio: la app arranca igual y falla mucho más tarde, al tocar una tabla
 * o columna que no existe.
 *
 * Idempotente por partida doble:
 *   1. el registro evita repetir un archivo ya aplicado;
 *   2. aun así, los errores de "ya existe" (tabla/columna/índice duplicado) se
 *      toleran y se reportan. Esto permite adoptar el sistema sobre una base
 *      que YA tenía migraciones aplicadas a mano, sin registro previo: la
 *      primera pasada las marca como aplicadas en vez de romper.
 */
declare(strict_types=1);

class Migrador {

    /**
     * Errores de MySQL/MariaDB que significan "esto ya estaba hecho".
     * Se toleran para poder correr sobre bases migradas a mano.
     *   1050 tabla ya existe            1060 columna duplicada
     *   1061 índice duplicado           1091 no se puede borrar (no existe)
     *   1022 clave duplicada            1826 clave foránea duplicada
     */
    private const ERRORES_YA_APLICADO = [1050, 1060, 1061, 1091, 1022, 1826];

    private PDO $db;
    private string $dir;

    public function __construct(PDO $db, ?string $dir = null) {
        $this->db  = $db;
        $this->dir = $dir ?? __DIR__ . '/../database/migrations';
    }

    /**
     * Conexión con privilegios de DDL.
     *
     * En producción el usuario de la app (tornalyx_dml) NO puede alterar el
     * esquema, a propósito: ver database/dcl.sql. Por eso, si están definidas
     * DB_DDL_USER/DB_DDL_PASS se abre una conexión aparte con esas credenciales
     * y se usa solo para migrar. Si no están, se reutiliza la conexión normal
     * (caso típico de desarrollo local, donde el usuario ya es root).
     */
    public static function conexionDDL(): PDO {
        require_once __DIR__ . '/../config/database.php';

        $usuario = getenv('DB_DDL_USER') ?: '';
        if ($usuario === '') {
            return getDB();
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        return new PDO($dsn, $usuario, getenv('DB_DDL_PASS') ?: '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /**
     * Archivos de migración en el orden en que deben aplicarse.
     *
     * schema.sql va SIEMPRE primero: crea las tablas base sobre las que el
     * resto hace ALTER. Después, el resto en orden alfabético, que es el mismo
     * criterio que usa MySQL con /docker-entrypoint-initdb.d.
     *
     * @return string[] Nombres de archivo (no rutas).
     */
    public function archivos(): array {
        $todos = glob($this->dir . '/*.sql') ?: [];
        $todos = array_map('basename', $todos);
        sort($todos);

        $schema = array_search('schema.sql', $todos, true);
        if ($schema !== false) {
            unset($todos[$schema]);
            array_unshift($todos, 'schema.sql');
        }
        return array_values($todos);
    }

    /**
     * Crea la tabla de registro si no existe. Es la única pieza que el propio
     * migrador necesita antes de poder consultar nada.
     */
    private function asegurarRegistro(): void {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS migraciones (
                archivo     VARCHAR(190) NOT NULL PRIMARY KEY,
                aplicada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return string[] Archivos ya aplicados según el registro. */
    public function aplicadas(): array {
        $this->asegurarRegistro();
        return $this->db->query('SELECT archivo FROM migraciones')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return string[] Archivos que todavía no se aplicaron. */
    public function pendientes(): array {
        return array_values(array_diff($this->archivos(), $this->aplicadas()));
    }

    /**
     * Aplica todas las migraciones pendientes.
     *
     * @param callable|null $log Recibe (string $archivo, string $estado, string $detalle).
     *                           $estado: 'aplicada' | 'ya-estaba' | 'error'.
     * @return int Cantidad de archivos aplicados.
     * @throws RuntimeException Si una migración falla por un motivo real.
     */
    public function aplicar(?callable $log = null): int {
        $aplicados = 0;
        foreach ($this->pendientes() as $archivo) {
            $yaEstaba = $this->ejecutarArchivo($this->dir . '/' . $archivo);
            $this->marcarAplicada($archivo);
            $aplicados++;
            if ($log) {
                $log($archivo, $yaEstaba ? 'ya-estaba' : 'aplicada', '');
            }
        }
        return $aplicados;
    }

    private function marcarAplicada(string $archivo): void {
        $stmt = $this->db->prepare(
            'INSERT INTO migraciones (archivo) VALUES (?)
             ON DUPLICATE KEY UPDATE archivo = archivo'
        );
        $stmt->execute([$archivo]);
    }

    /**
     * Ejecuta un archivo .sql sentencia por sentencia.
     *
     * @return bool true si TODAS las sentencias dieron "ya existe" (es decir,
     *              el archivo ya estaba aplicado a mano sobre esta base).
     * @throws RuntimeException ante un error que no sea de duplicado.
     */
    private function ejecutarArchivo(string $ruta): bool {
        $sql = file_get_contents($ruta);
        if ($sql === false) {
            throw new RuntimeException("No se pudo leer la migración: {$ruta}");
        }

        $ejecutadas = 0;
        $duplicadas = 0;
        foreach (self::separarSentencias($sql) as $sentencia) {
            try {
                $this->db->exec($sentencia);
                $ejecutadas++;
            } catch (PDOException $e) {
                $codigo = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
                if (!in_array($codigo, self::ERRORES_YA_APLICADO, true)) {
                    throw new RuntimeException(sprintf(
                        'Falló %s: [%d] %s',
                        basename($ruta), $codigo, $e->getMessage()
                    ), 0, $e);
                }
                $duplicadas++;
            }
        }
        return $ejecutadas === 0 && $duplicadas > 0;
    }

    /**
     * Parte un script SQL en sentencias individuales.
     *
     * Descarta comentarios y las sentencias USE: la base ya viene seleccionada
     * por la conexión, y respetar el USE del archivo apuntaría a un nombre de
     * base fijo (tornalyx_db) aunque el despliegue use otro (DB_NAME).
     *
     * No contempla DELIMITER ni cuerpos de procedimientos, porque las
     * migraciones del proyecto son DDL plano.
     *
     * @return string[]
     */
    private static function separarSentencias(string $sql): array {
        $sentencias = [];
        $actual     = '';
        $comilla    = null;          // comilla abierta: ' " o `
        $largo      = strlen($sql);

        for ($i = 0; $i < $largo; $i++) {
            $c   = $sql[$i];
            $sig = $i + 1 < $largo ? $sql[$i + 1] : '';

            if ($comilla !== null) {
                $actual .= $c;
                if ($c === '\\' && $comilla !== '`') {
                    // Escape: el siguiente carácter es literal.
                    if ($sig !== '') { $actual .= $sig; $i++; }
                } elseif ($c === $comilla) {
                    $comilla = null;
                }
                continue;
            }

            // Comentario de línea: -- ... o # ...
            if (($c === '-' && $sig === '-') || $c === '#') {
                while ($i < $largo && $sql[$i] !== "\n") { $i++; }
                $actual .= ' ';
                continue;
            }
            // Comentario de bloque: /* ... */
            if ($c === '/' && $sig === '*') {
                $fin = strpos($sql, '*/', $i + 2);
                $i   = $fin === false ? $largo : $fin + 1;
                $actual .= ' ';
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') {
                $comilla = $c;
                $actual .= $c;
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

        $limpias = [];
        foreach ($sentencias as $sentencia) {
            $sentencia = trim($sentencia);
            if ($sentencia === '' || preg_match('/^USE\s/i', $sentencia)) {
                continue;
            }
            $limpias[] = $sentencia;
        }
        return $limpias;
    }

    // ──────────────────────────────────────────────────────────────
    // Sello de revisión: evita consultar la base en cada request.
    // ──────────────────────────────────────────────────────────────

    /**
     * ¿Hace falta revisar si hay migraciones pendientes?
     *
     * Compara la fecha del archivo de sello contra la del .sql más reciente.
     * Mientras nadie agregue ni toque una migración, esto es un par de stat()
     * y no abre ninguna conexión: el costo por request es despreciable.
     */
    public static function hayQueRevisar(string $sello, ?string $dir = null): bool {
        $dir = $dir ?? __DIR__ . '/../database/migrations';
        if (!is_file($sello)) {
            return true;
        }
        $marca = filemtime($sello);
        foreach (glob($dir . '/*.sql') ?: [] as $archivo) {
            if (filemtime($archivo) > $marca) {
                return true;
            }
        }
        return false;
    }

    /** Deja constancia de que ya se revisó con este juego de migraciones. */
    public static function sellar(string $sello): void {
        $dir = dirname($sello);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        @file_put_contents($sello, (string) time());
    }
}
