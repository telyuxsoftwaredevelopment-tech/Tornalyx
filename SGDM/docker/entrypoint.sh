#!/bin/sh
# Arranque del contenedor: pone la base al día ANTES de levantar Apache.
#
# El esquema de una base ya existente no se actualiza solo: el script de
# /docker-entrypoint-initdb.d que usa MySQL corre únicamente cuando el volumen
# está vacío, así que a partir del segundo arranque las migraciones nuevas
# nunca se aplicaban. La app quedaba corriendo contra un esquema viejo y
# fallaba mucho más tarde, al tocar una tabla o columna inexistente.
#
# Variables:
#   MIGRAR_AL_ARRANCAR=0  omite la migración (útil si la maneja otro proceso).
#   MIGRAR_ESTRICTO=1     aborta el arranque si la migración falla; por defecto
#                         se registra el error y Apache levanta igual, para que
#                         un problema de migración no deje el sitio entero caído.
set -e

if [ "${MIGRAR_AL_ARRANCAR:-1}" = "1" ]; then
    echo "[entrypoint] Aplicando migraciones pendientes..."
    if php /var/www/html/SGDM/backend/database/migrar.php; then
        echo "[entrypoint] Base de datos al dia."
    else
        echo "[entrypoint] ERROR: fallaron las migraciones." >&2
        if [ "${MIGRAR_ESTRICTO:-0}" = "1" ]; then
            echo "[entrypoint] MIGRAR_ESTRICTO=1: se aborta el arranque." >&2
            exit 1
        fi
        echo "[entrypoint] Apache arranca igual: revisa el error de arriba, el esquema puede estar desactualizado." >&2
    fi
fi

# Cede el control al arranque normal de la imagen (apache2-foreground).
exec "$@"
