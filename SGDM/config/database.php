<?php
/**
 * Configuración y conexión a la base de datos.
 * Usa PDO con prepared statements para prevenir inyección SQL.
 *
 * Las credenciales se leen de variables de entorno para no exponerlas
 * en el código fuente ni en el control de versiones. Definí estas
 * variables en el entorno del servidor (o en un archivo .env cargado
 * por el SAPI). Los valores por defecto solo sirven para desarrollo local.
 */

/**
 * Carga variables desde un archivo .env (si existe) en la raíz del proyecto.
 * No sobreescribe variables ya definidas en el entorno real del servidor,
 * de modo que en producción el entorno tiene prioridad sobre el archivo.
 *
 * PHP plano no lee .env automáticamente; este cargador minimalista lo suple
 * sin dependencias externas. El archivo .env está en .gitignore (no se versiona).
 */
function loadDotEnv(string $path): void {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // Quitar comillas envolventes si las hubiera (el valor se toma literal).
        if (strlen($val) >= 2
            && (($val[0] === '"' && $val[-1] === '"') || ($val[0] === "'" && $val[-1] === "'"))) {
            $val = substr($val, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }
}

loadDotEnv(__DIR__ . '/../../.env');

/**
 * Lee una variable de entorno con valor por defecto para desarrollo local.
 */
function envOrDefault(string $key, string $default): string {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('DB_HOST',    envOrDefault('DB_HOST', 'localhost'));
define('DB_NAME',    envOrDefault('DB_NAME', 'tornalyx_db'));
define('DB_USER',    envOrDefault('DB_USER', 'root'));
define('DB_PASS',    envOrDefault('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna la conexión PDO (singleton).
 *
 * @return PDO
 * @throws RuntimeException Si la conexión falla.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Reason: Never exponer detalles de BD al cliente en producción
            error_log('DB connection error: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
    }

    return $pdo;
}
