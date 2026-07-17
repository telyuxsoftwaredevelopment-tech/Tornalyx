#!/usr/bin/env bash
#
# desplegar.sh — Script de despliegue de Tornalyx (SGDM) en el servidor.
#
# Actualiza el código desde Git, aplica las migraciones SQL pendientes,
# corrige permisos y reinicia Apache. Pensado para ser ejecutado por
# operador_web (ver analisis-usuarios-sistema.md) como parte de sus tareas
# habituales de despliegue.
#
# Requisito: Objetivo específico 13 ("scripts de administración para
# instalación, despliegue, respaldo y mantenimiento básico").
#
# Uso:
#   sudo ./desplegar.sh                # despliegue completo
#   sudo ./desplegar.sh solo-migrar    # solo aplica migraciones SQL
#   sudo ./desplegar.sh solo-codigo    # solo git + permisos
#   ./desplegar.sh -h
#
# actualizar_codigo/corregir_permisos/reiniciar_apache necesitan privilegios
# de root (chown, systemctl): correr con sudo. operador_web tiene permiso
# NOPASSWD para invocar este script completo (ver sudoers.d/operador_web).
#
# Variables de entorno para DB_HOST/DB_PORT/DB_NAME: se leen del .env en la
# raíz del repo si no están ya definidas.
#
# Las migraciones (solo-migrar) usan credenciales DDL propias, DISTINTAS de
# las de la app (DB_USER/DB_PASS del .env, que son de solo DML y no pueden
# hacer CREATE/ALTER/DROP — ver SGDM/database/dcl.sql): DB_DDL_USER
# (default tornalyx_ddl) y DB_DDL_PASS. Ninguna de las dos vive en el .env
# de la app; exportalas a mano antes de desplegar, por ejemplo:
#   sudo DB_DDL_PASS='...' ./desplegar.sh solo-migrar

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/tornalyx}"
REPO_BRANCH="${REPO_BRANCH:-main}"
LOG_FILE="/var/log/tornalyx/desplegar.log"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-http://localhost/}"

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." &>/dev/null && pwd)"
MIGRATIONS_DIR="$REPO_ROOT/SGDM/database/migrations"

log() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
  echo "$msg"
  mkdir -p "$(dirname -- "$LOG_FILE")" 2>/dev/null && echo "$msg" >>"$LOG_FILE" 2>/dev/null || true
}

fallar() {
  echo "ERROR: $*" >&2
  exit 1
}

requiere_root() {
  [[ "$(id -u)" -eq 0 ]] || fallar "esta operación requiere privilegios de root (sudo ./desplegar.sh)."
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
  # Credenciales DDL para las migraciones, independientes de las DML de la
  # app: nunca viven en el .env de la app (ver dcl.sql). Se exportan a mano.
  DB_DDL_USER="${DB_DDL_USER:-tornalyx_ddl}"
  DB_DDL_PASS="${DB_DDL_PASS:-}"
}

actualizar_codigo() {
  [[ -d "$APP_DIR/.git" ]] || fallar "no hay un repositorio git en $APP_DIR. Cloná el proyecto ahí primero."

  log "Actualizando código en $APP_DIR (rama $REPO_BRANCH)..."
  git -C "$APP_DIR" fetch origin "$REPO_BRANCH"
  git -C "$APP_DIR" checkout "$REPO_BRANCH"
  git -C "$APP_DIR" reset --hard "origin/$REPO_BRANCH"
  log "Código actualizado a $(git -C "$APP_DIR" rev-parse --short HEAD)."
}

aplicar_migraciones() {
  [[ -d "$MIGRATIONS_DIR" ]] || fallar "no se encontró el directorio de migraciones: $MIGRATIONS_DIR"
  [[ -n "$DB_DDL_PASS" ]] || fallar "DB_DDL_PASS no está definida. Exportala antes de correr este comando (usuario DDL de dcl.sql, nunca el de la app)."

  log "Aplicando migraciones SQL desde $MIGRATIONS_DIR (usuario DDL: $DB_DDL_USER)..."
  local f
  for f in "$MIGRATIONS_DIR"/*.sql; do
    [[ -e "$f" ]] || continue
    log "  -> $(basename "$f")"
    MYSQL_PWD="$DB_DDL_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_DDL_USER" <"$f"
  done
  log "Migraciones aplicadas."
}

corregir_permisos() {
  log "Corrigiendo permisos de $APP_DIR..."
  chown -R operador_web:www-data "$APP_DIR" 2>/dev/null || chown -R www-data:www-data "$APP_DIR"
  find "$APP_DIR" -type d -exec chmod 750 {} \;
  find "$APP_DIR" -type f -exec chmod 640 {} \;
  # storage/throttle debe ser escribible por Apache (control de fuerza bruta).
  if [[ -d "$APP_DIR/SGDM/storage" ]]; then
    chmod -R u+w,g+w "$APP_DIR/SGDM/storage"
  fi
}

reiniciar_apache() {
  log "Reiniciando Apache..."
  systemctl reload apache2 2>/dev/null || systemctl restart apache2
}

healthcheck() {
  log "Verificando que la aplicación responda en $HEALTHCHECK_URL..."
  local codigo
  codigo="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$HEALTHCHECK_URL" || echo "000")"
  if [[ "$codigo" =~ ^[23] ]]; then
    log "Healthcheck OK (HTTP $codigo)."
  else
    fallar "healthcheck falló (HTTP $codigo). Revisá los logs de Apache/PHP."
  fi
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 [comando]

Comandos:
  (sin comando)   Despliegue completo: git pull, migraciones, permisos,
                  reinicio de Apache y healthcheck. Requiere root (sudo).
  solo-migrar     Solo aplica las migraciones SQL pendientes.
  solo-codigo     Solo actualiza el código (git) y corrige permisos.
                  Requiere root (sudo).
  -h, --help      Muestra esta ayuda.

Variables de entorno: APP_DIR (default /var/www/tornalyx),
REPO_BRANCH (default main), HEALTHCHECK_URL (default http://localhost/),
DB_DDL_USER (default tornalyx_ddl), DB_DDL_PASS (sin default, obligatoria
para aplicar migraciones).
EOF
}

main() {
  local comando="${1:-}"
  cargar_env
  case "$comando" in
    solo-migrar) aplicar_migraciones ;;
    solo-codigo) requiere_root; actualizar_codigo; corregir_permisos ;;
    -h|--help|help) mostrar_ayuda ;;
    "")
      requiere_root
      actualizar_codigo
      aplicar_migraciones
      corregir_permisos
      reiniciar_apache
      healthcheck
      log "Despliegue completo."
      ;;
    *) fallar "comando desconocido: '$comando'. Usá '$0 --help'." ;;
  esac
}

main "$@"
