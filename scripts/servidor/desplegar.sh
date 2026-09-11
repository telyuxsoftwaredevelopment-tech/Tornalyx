#!/usr/bin/env bash
# Tornalyx — aplica el esquema y las migraciones en el servidor real.
#
# Es la contraparte de SGDM/backend/database/dcl.sql: el DDL lo corre
# tornalyx_ddl (CREATE/ALTER/DROP + SELECT/INSERT solo sobre
# schema_migrations), NUNCA el usuario de la app. La app corre como
# tornalyx_dml y con DB_AUTO_MIGRATE=0 en su .env, así que no puede —ni
# necesita— tocar el esquema por su cuenta.
#
# Uso:
#   export DB_DDL_USER=tornalyx_ddl
#   export DB_DDL_PASS='...'            # nunca en el .env de la app
#   scripts/servidor/desplegar.sh              # esquema + migraciones
#   scripts/servidor/desplegar.sh solo-migrar  # solo las migraciones pendientes
#
# Variables opcionales: DB_HOST (127.0.0.1), DB_PORT (3306),
# DB_NAME (tornalyx_db).
set -euo pipefail

MODO="${1:-completo}"
case "$MODO" in
    completo|solo-migrar) ;;
    -h|--ayuda|--help)
        sed -n '2,17p' "$0" | sed 's/^# \{0,1\}//'
        exit 0
        ;;
    *)
        echo "Modo desconocido: $MODO (usar 'completo' o 'solo-migrar')" >&2
        exit 1
        ;;
esac

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DIR_SQL="$RAIZ/SGDM/backend/database"
DIR_MIG="$DIR_SQL/migrations"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-tornalyx_db}"

if [[ -z "${DB_DDL_USER:-}" || -z "${DB_DDL_PASS:-}" ]]; then
    echo "Faltan DB_DDL_USER / DB_DDL_PASS (usuario DDL de dcl.sql)." >&2
    echo "No uses las credenciales de la app: por diseño no tienen DDL." >&2
    exit 1
fi

# Guardia contra el error clásico de pegar acá el usuario de la app.
if [[ "$DB_DDL_USER" == "tornalyx_dml" ]]; then
    echo "DB_DDL_USER es tornalyx_dml, el usuario de runtime de la app." >&2
    echo "Ese usuario no tiene CREATE/ALTER a propósito (ver dcl.sql)." >&2
    exit 1
fi

# mysql lee la contraseña de MYSQL_PWD para que no quede en la línea de
# comandos (visible en 'ps' para cualquier usuario del sistema).
#
# Conecta SIN base por defecto a propósito: en un servidor nuevo tornalyx_db
# todavía no existe, y pasarla como base por defecto haría fallar la conexión
# antes de poder crearla. Cada archivo se aplica con su propio USE (ver abajo).
ddl() {
    MYSQL_PWD="$DB_DDL_PASS" mysql \
        -h "$DB_HOST" -P "$DB_PORT" -u "$DB_DDL_USER" \
        --default-character-set=utf8mb4 "$@"
}

# Quita los 'USE <base>;' para que el archivo se aplique sobre $DB_NAME sea
# cual sea su nombre (mismo criterio que Migracion::ejecutarFaltantes()).
sin_use() {
    sed -E 's/^[[:space:]]*USE[[:space:]]+[^;]+;//I' "$1"
}

# ¿La migración trae backfill de datos además de DDL? tornalyx_ddl no tiene
# DML sobre las tablas de negocio, así que esas van aparte con tornalyx_dcl.
# El auto-registro en schema_migrations no cuenta: ese permiso sí lo tiene,
# acotado a esa tabla (ver dcl.sql).
#
# Es una heurística: detecta INSERT/UPDATE/DELETE/REPLACE al principio de
# línea, que es como están escritas todas las migraciones del repo. Una
# sentencia DML indentada o pegada a otra en la misma línea se le escaparía y
# fallaría al aplicarse, con el error a la vista (no se registra como
# aplicada). Verificado contra las 11 migraciones actuales.
tiene_backfill() {
    local dml
    dml="$(grep -iE '^[[:space:]]*(INSERT|UPDATE|DELETE|REPLACE)[[:space:]]' "$1" || true)"
    dml="$(printf '%s' "$dml" | grep -iv 'schema_migrations' || true)"
    [[ -n "${dml//[[:space:]]/}" ]]
}

if [[ "$MODO" == "completo" ]]; then
    echo "==> Aplicando esquema base sobre $DB_NAME ($DB_HOST:$DB_PORT)"
    # schema.sql es idempotente (CREATE DATABASE/TABLE IF NOT EXISTS) y trae
    # plegado el efecto de todas las migraciones, con su backfill en
    # schema_migrations: en una base nueva esto deja cero pendientes.
    {
        echo "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        echo "USE \`$DB_NAME\`;"
        sin_use "$DIR_MIG/schema.sql"
    } | ddl
    echo "    -> schema.sql aplicado"
fi

echo "==> Revisando migraciones pendientes"
APLICADAS="$(ddl -N -B -e "SELECT filename FROM \`$DB_NAME\`.schema_migrations" 2>/dev/null || true)"
if [[ -z "$APLICADAS" ]]; then
    echo "    aviso: schema_migrations vacía o inexistente; se intentarán todas." >&2
fi

pendientes=0
aplicadas=0
declare -a REQUIEREN_DCL=()
declare -a FALLADAS=()

for archivo in "$DIR_MIG"/add_*.sql; do
    [[ -e "$archivo" ]] || continue
    base="$(basename "$archivo")"

    if printf '%s\n' "$APLICADAS" | grep -qxF "$base"; then
        continue
    fi
    pendientes=$((pendientes + 1))

    if tiene_backfill "$archivo"; then
        REQUIEREN_DCL+=("$base")
        continue
    fi

    echo "    aplicando $base"
    # --force: que un statement que falla no corte el resto del archivo, igual
    # que Migracion::ejecutarFaltantes() en PHP, que evalúa error por error.
    salida="$({ echo "USE \`$DB_NAME\`;"; sin_use "$archivo"; } | ddl --force 2>&1 || true)"

    # Códigos que solo dicen "esto ya estaba hecho" (mismo criterio que
    # Migracion::esYaAplicado()): tabla, columna, índice o fila duplicada y
    # DROP de algo inexistente. Aparecen al reaplicar sobre un esquema que ya
    # las tiene — p. ej. tras restaurar un backup sin el registro de
    # migraciones — y no son una falla.
    reales="$(printf '%s\n' "$salida" | grep -E '^ERROR [0-9]+' \
              | grep -vE '^ERROR (1050|1060|1061|1062|1091) ' || true)"

    if [[ -n "${reales//[[:space:]]/}" ]]; then
        echo "        FALLÓ, no se registra:"
        printf '        %s\n' "$reales"
        FALLADAS+=("$base")
        continue
    fi

    # Cada .sql se registra a sí mismo con INSERT IGNORE, pero lo repetimos acá
    # para que el registro quede correcto aunque ese statement se haya salteado.
    ddl -e "INSERT IGNORE INTO \`$DB_NAME\`.schema_migrations (filename) VALUES ('$base')"
    if printf '%s\n' "$salida" | grep -qE '^ERROR '; then
        echo "        ya estaba aplicada en el esquema; solo se registró."
    fi
    aplicadas=$((aplicadas + 1))
done

echo "==> Resumen: $pendientes pendiente(s), $aplicadas aplicada(s)"

if (( ${#FALLADAS[@]} > 0 )); then
    echo
    echo "Fallaron y quedaron SIN registrar (corregir y volver a correr):"
    for m in "${FALLADAS[@]}"; do
        echo "  - $m"
    done
    exit 1
fi

if (( ${#REQUIEREN_DCL[@]} > 0 )); then
    echo
    echo "Las siguientes migraciones traen backfill de datos y NO se aplicaron:"
    for m in "${REQUIEREN_DCL[@]}"; do
        echo "  - $m"
    done
    echo
    echo "tornalyx_ddl no tiene DML sobre las tablas de negocio (es el punto"
    echo "de la separación de privilegios). Aplicalas con tornalyx_dcl:"
    for m in "${REQUIEREN_DCL[@]}"; do
        echo "  mysql -h $DB_HOST -P $DB_PORT -u tornalyx_dcl -p $DB_NAME < $DIR_MIG/$m"
    done
    exit 2
fi

echo
echo "Recordatorio: el .env de la app debe tener DB_AUTO_MIGRATE=0 y las"
echo "credenciales de tornalyx_dml, nunca las de este script."
