#!/usr/bin/env bash
#
# respaldo.sh — Script de respaldos y automatización de Tornalyx (SGDM).
#
# Genera backups comprimidos de la base de datos y de los archivos de la
# aplicación, con checksum de verificación y rotación por antigüedad.
# Pensado para correr como el usuario de servicio backup_tornalyx (ver
# analisis-usuarios-sistema.md), tanto a mano como desde cron.
#
# Requisitos:
#   - ASO segunda entrega: "Script de respaldos y automatización (cron)
#     implementado en el servidor."
#   - Objetivo específico 13: scripts de respaldo.
#
# Uso:
#   ./respaldo.sh bd              # backup de la base de datos
#   ./respaldo.sh archivos        # backup de los archivos de la app
#   ./respaldo.sh todo            # ambos backups
#   ./respaldo.sh --install-cron  # instala el cron en /etc/cron.d (root)
#   ./respaldo.sh -h
#
# DB_HOST/DB_PORT/DB_NAME se leen del .env en la raíz del repo si no están
# ya definidas. Las credenciales de mysqldump son propias de este script,
# DISTINTAS de las de la app (DB_USER/DB_PASS del .env, que no tienen
# LOCK TABLES/TRIGGER/EVENT): DB_BACKUP_USER (default tornalyx_backup) y
# DB_BACKUP_PASS, exportadas a mano (ver SGDM/database/dcl.sql).

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/tornalyx}"
BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/tornalyx}"
RETENCION_DIAS="${RETENCION_DIAS:-14}"
LOG_FILE="/var/log/tornalyx/backup.log"

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." &>/dev/null && pwd)"

log() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
  echo "$msg"
  mkdir -p "$(dirname -- "$LOG_FILE")" 2>/dev/null && echo "$msg" >>"$LOG_FILE" 2>/dev/null || true
}

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
  DB_BACKUP_USER="${DB_BACKUP_USER:-tornalyx_backup}"
  DB_BACKUP_PASS="${DB_BACKUP_PASS:-}"
}

backup_bd() {
  [[ -n "$DB_BACKUP_PASS" ]] || fallar "DB_BACKUP_PASS no está definida. Exportala antes de correr este comando (usuario de backup de dcl.sql, nunca el de la app)."

  local destino="$BACKUP_ROOT/db"
  mkdir -p "$destino"
  local marca; marca="$(date '+%Y%m%d_%H%M%S')"
  local archivo="$destino/tornalyx_db_${marca}.sql.gz"

  log "Iniciando backup de la base de datos '$DB_NAME'..."
  MYSQL_PWD="$DB_BACKUP_PASS" mysqldump \
    -h "$DB_HOST" -P "$DB_PORT" -u "$DB_BACKUP_USER" \
    --single-transaction --routines --triggers --quick \
    "$DB_NAME" | gzip >"$archivo"

  sha256sum "$archivo" >"$archivo.sha256"
  log "Backup de BD creado: $archivo ($(du -h "$archivo" | cut -f1))."
}

backup_archivos() {
  local destino="$BACKUP_ROOT/files"
  mkdir -p "$destino"
  local marca; marca="$(date '+%Y%m%d_%H%M%S')"
  local archivo="$destino/tornalyx_files_${marca}.tar.gz"

  [[ -d "$APP_DIR" ]] || fallar "no existe el directorio de la app: $APP_DIR"

  log "Iniciando backup de archivos de $APP_DIR..."
  tar --exclude='.git' --exclude='vendor' --exclude='node_modules' \
      -czf "$archivo" -C "$(dirname -- "$APP_DIR")" "$(basename -- "$APP_DIR")"

  sha256sum "$archivo" >"$archivo.sha256"
  log "Backup de archivos creado: $archivo ($(du -h "$archivo" | cut -f1))."
}

rotar_backups() {
  log "Eliminando backups con más de $RETENCION_DIAS días..."
  find "$BACKUP_ROOT" -type f \( -name '*.sql.gz' -o -name '*.tar.gz' -o -name '*.sha256' \) \
    -mtime "+$RETENCION_DIAS" -print -delete | while read -r f; do
      log "  Eliminado: $f"
    done
}

instalar_cron() {
  [[ "$(id -u)" -eq 0 ]] || fallar "instalar el cron requiere privilegios de root (sudo)."
  local origen="$SCRIPT_DIR/cron/tornalyx-backup"
  local destino="/etc/cron.d/tornalyx-backup"
  [[ -f "$origen" ]] || fallar "no se encontró la plantilla de cron: $origen"

  install -m 644 -o root -g root "$origen" "$destino"
  systemctl reload cron 2>/dev/null || systemctl restart cron 2>/dev/null || true
  log "Cron de respaldos instalado en $destino."

  if [[ ! -f /etc/tornalyx/backup.env ]]; then
    log "AVISO: falta /etc/tornalyx/backup.env con DB_BACKUP_PASS. El backup de BD"
    log "  fallará hasta instalarlo: sudo ./gestion_usuarios.sh crear backup_tornalyx"
    log "  (y despues editar el placeholder DB_BACKUP_PASS con el password real)."
  fi
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 <comando>

Comandos:
  bd              Backup de la base de datos (mysqldump + gzip + checksum).
  archivos        Backup de los archivos de la aplicación (tar.gz + checksum).
  rotar           Elimina backups más viejos que RETENCION_DIAS.
  todo            Ejecuta ambos backups y rota los antiguos.
  --install-cron  Instala el cron de respaldos en /etc/cron.d (requiere root).
  -h, --help      Muestra esta ayuda.

Variables de entorno: APP_DIR, BACKUP_ROOT (default /var/backups/tornalyx),
RETENCION_DIAS (default 14), DB_BACKUP_USER (default tornalyx_backup),
DB_BACKUP_PASS (sin default, obligatoria para 'bd'/'todo').
EOF
}

main() {
  local comando="${1:-}"
  case "$comando" in
    bd) cargar_env; backup_bd ;;
    archivos) backup_archivos ;;
    rotar) rotar_backups ;;
    todo) cargar_env; backup_bd; backup_archivos; rotar_backups ;;
    --install-cron) instalar_cron ;;
    -h|--help|help|"") mostrar_ayuda ;;
    *) fallar "comando desconocido: '$comando'. Usá '$0 --help'." ;;
  esac
}

main "$@"
