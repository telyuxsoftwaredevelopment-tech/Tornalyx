<?php
/**
 * Aplica las migraciones pendientes de la base de datos.
 *
 * Uso (desde la raíz del repositorio):
 *   php SGDM/backend/database/migrar.php            # aplica lo pendiente
 *   php SGDM/backend/database/migrar.php --estado   # solo informa, no toca nada
 *
 * Lo corre solo el entrypoint del contenedor al arrancar
 * (SGDM/docker/entrypoint.sh) y, en desarrollo, el propio front controller la
 * primera vez que detecta migraciones nuevas. También sirve a mano en un
 * servidor donde el despliegue no pase por Docker.
 *
 * Credenciales: si están definidas DB_DDL_USER/DB_DDL_PASS se usan esas (el
 * usuario de la app no tiene privilegios de DDL en producción a propósito, ver
 * database/dcl.sql). Si no, se usa la conexión normal.
 *
 * Termina con código 0 si todo quedó al día, 1 si alguna migración falló.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede ejecutarse por linea de comandos.\n");
}

require_once __DIR__ . '/../shared/Migrador.php';

$soloEstado = in_array('--estado', $argv ?? [], true);

try {
    $migrador   = new Migrador(Migrador::conexionDDL());
    $pendientes = $migrador->pendientes();
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo consultar el estado de las migraciones: {$e->getMessage()}\n");
    exit(1);
}

if ($soloEstado) {
    $aplicadas = $migrador->aplicadas();
    echo "Migraciones aplicadas (" . count($aplicadas) . "):\n";
    foreach ($migrador->archivos() as $archivo) {
        $marca = in_array($archivo, $aplicadas, true) ? 'x' : ' ';
        echo "  [{$marca}] {$archivo}\n";
    }
    echo "\nPendientes: " . count($pendientes) . "\n";
    exit(0);
}

if ($pendientes === []) {
    echo "Base de datos al dia: no hay migraciones pendientes.\n";
    exit(0);
}

echo "Migraciones pendientes: " . count($pendientes) . "\n";
try {
    $migrador->aplicar(static function (string $archivo, string $estado): void {
        // 'ya-estaba' = el archivo se habia aplicado a mano antes de existir el
        // registro; se marca como aplicado sin volver a tocar el esquema.
        $etiqueta = $estado === 'ya-estaba' ? 'ya estaba aplicada, se registra' : 'aplicada';
        echo "  - {$archivo}: {$etiqueta}\n";
    });
} catch (Throwable $e) {
    fwrite(STDERR, "\nERROR: {$e->getMessage()}\n");
    exit(1);
}

echo "Listo.\n";
exit(0);
