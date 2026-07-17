#!/usr/bin/env bash
#
# monitoreo_bd.sh — Script de consultas y monitoreo de la base de datos
# de Tornalyx (SGDM) dentro del servidor.
#
# Requisito: ASO tercera entrega ("Script de consultas y monitoreo de base
# de datos dentro del servidor").
#
# Uso:
#   ./monitoreo_bd.sh estado         # uptime, threads, conexiones del server
#   ./monitoreo_bd.sh procesos       # SHOW FULL PROCESSLIST
#   ./monitoreo_bd.sh tamanos        # tamaño en MB de cada tabla
#   ./monitoreo_bd.sh lentas         # consultas lentas (slow query log activado)
#   ./monitoreo_bd.sh conteos        # cantidad de filas por tabla del sistema
#   ./monitoreo_bd.sh todo           # corre todo lo anterior en orden
#   ./monitoreo_bd.sh -h
#
# DB_HOST/DB_PORT/DB_NAME se leen del .env en la raíz del repo si no están
# ya definidas. Las credenciales son propias de este script, DISTINTAS de
# las de la app (DB_USER/DB_PASS del .env, que no tienen PROCESS ni acceso
# a mysql.slow_log): DB_MONITOR_USER (default tornalyx_monitor) y
# DB_MONITOR_PASS, exportadas a mano (ver SGDM/database/dcl.sql).

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." &>/dev/null && pwd)"

TABLAS_APP=(usuarios torneos equipos inscripciones rondas partidos resultados posiciones sesiones login_otps)

fallar() {
  echo "ERROR: $*" >&2
  exit 1
}

cargar_env() {
  local envfile="$REPO_ROOT/.env"
  if [[ -f "$envfile" ]]; then
    set -a
    # shellcheck disable=SC1090
    source <(grep -vE '^\s*#' "$envfile" | grep -E '^[A-Za-z_][A-Za-z0-9_]*=')
    set +a
  fi
  DB_HOST="${DB_HOST:-localhost}"
  DB_PORT="${DB_PORT:-3306}"
  DB_NAME="${DB_NAME:-tornalyx_db}"
  DB_MONITOR_USER="${DB_MONITOR_USER:-tornalyx_monitor}"
  DB_MONITOR_PASS="${DB_MONITOR_PASS:-}"
  [[ -n "$DB_MONITOR_PASS" ]] || fallar "DB_MONITOR_PASS no está definida. Exportala antes de correr este script (usuario de monitoreo de dcl.sql, nunca el de la app)."
}

mysql_run() {
  MYSQL_PWD="$DB_MONITOR_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_MONITOR_USER" --table -e "$1"
}

estado() {
  echo "== Estado del servidor MySQL =="
  mysql_run "SHOW GLOBAL STATUS WHERE Variable_name IN
    ('Uptime','Threads_connected','Threads_running','Connections',
     'Aborted_connects','Slow_queries','Questions');"
  echo
  echo "== Conexiones máximas configuradas =="
  mysql_run "SHOW VARIABLES WHERE Variable_name IN ('max_connections','max_user_connections');"
}

procesos() {
  echo "== Procesos activos (SHOW FULL PROCESSLIST) =="
  mysql_run "SHOW FULL PROCESSLIST;"
}

tamanos() {
  echo "== Tamaño de tablas en '$DB_NAME' (MB) =="
  mysql_run "
    SELECT table_name AS tabla,
           table_rows AS filas_aprox,
           ROUND(data_length/1024/1024, 2)  AS datos_mb,
           ROUND(index_length/1024/1024, 2) AS indices_mb,
           ROUND((data_length+index_length)/1024/1024, 2) AS total_mb
    FROM information_schema.tables
    WHERE table_schema = '$DB_NAME'
    ORDER BY total_mb DESC;"
}

lentas() {
  echo "== Configuración de slow query log =="
  mysql_run "SHOW VARIABLES WHERE Variable_name IN
    ('slow_query_log','slow_query_log_file','long_query_time');"
  echo
  echo "== Últimas consultas lentas (si mysql.slow_log está habilitada) =="
  mysql_run "
    SELECT start_time, query_time, sql_text
    FROM mysql.slow_log
    ORDER BY start_time DESC
    LIMIT 20;" 2>/dev/null || echo "(mysql.slow_log no disponible; revisar slow_query_log_file en el filesystem)"
}

conteos() {
  echo "== Cantidad de filas por tabla de la aplicación =="
  local t
  for t in "${TABLAS_APP[@]}"; do
    mysql_run "SELECT '$t' AS tabla, COUNT(*) AS filas FROM $DB_NAME.$t;" 2>/dev/null \
      || echo "  (no se pudo consultar '$t')"
  done
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 <comando>

Comandos:
  estado      Uptime, threads y conexiones del servidor MySQL.
  procesos    SHOW FULL PROCESSLIST (consultas en ejecución).
  tamanos     Tamaño en MB de cada tabla de tornalyx_db.
  lentas      Consultas lentas registradas (slow query log).
  conteos     Cantidad de filas por tabla de la aplicación.
  todo        Ejecuta todos los reportes anteriores en orden.
  -h, --help  Muestra esta ayuda.

Variables de entorno: DB_MONITOR_USER (default tornalyx_monitor),
DB_MONITOR_PASS (sin default, obligatoria).
EOF
}

main() {
  local comando="${1:-}"
  case "$comando" in
    estado|procesos|tamanos|lentas|conteos) cargar_env; "$comando" ;;
    todo) cargar_env; estado; echo; procesos; echo; tamanos; echo; conteos; echo; lentas ;;
    -h|--help|help|"") mostrar_ayuda ;;
    *) fallar "comando desconocido: '$comando'. Usá '$0 --help'." ;;
  esac
}

main "$@"
