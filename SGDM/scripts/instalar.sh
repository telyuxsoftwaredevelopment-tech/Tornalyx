#!/usr/bin/env bash
#
# instalar.sh — Script de instalación del servidor para Tornalyx (SGDM).
#
# Prepara un servidor Ubuntu/Debian nuevo con todo lo necesario para alojar
# la aplicación: Apache, PHP + extensiones, cliente MySQL, Git, ufw,
# fail2ban y certbot. Es el primer script que corre admin_tornalyx en un
# servidor recién provisionado.
#
# Requisito: Objetivo específico 13 ("Crear scripts de administración para
# instalación, despliegue, respaldo y mantenimiento básico").
#
# Uso:
#   sudo ./instalar.sh            # instala todo
#   sudo ./instalar.sh paquetes   # solo instala/actualiza paquetes del sistema
#   sudo ./instalar.sh directorios # solo crea estructura de directorios
#   sudo ./instalar.sh -h
#
# Idempotente: usar apt install (no reinstala si ya está) y mkdir -p.

set -euo pipefail

APP_DIR="/var/www/tornalyx"
LOG_DIR="/var/log/tornalyx"
BACKUP_DIR="/var/backups/tornalyx"
LOG_FILE="$LOG_DIR/instalar.log"

PAQUETES=(
  apache2
  php
  php-mysql
  php-mbstring
  php-xml
  php-curl
  mysql-client
  git
  ufw
  fail2ban
  certbot
  python3-certbot-apache
  unzip
  cron
)

log() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
  echo "$msg"
  mkdir -p "$LOG_DIR" 2>/dev/null && echo "$msg" >>"$LOG_FILE" 2>/dev/null || true
}

fallar() {
  echo "ERROR: $*" >&2
  exit 1
}

requiere_root() {
  [[ "$(id -u)" -eq 0 ]] || fallar "este script debe ejecutarse como root (sudo)."
}

instalar_paquetes() {
  log "Actualizando índice de paquetes (apt update)..."
  apt-get update -y

  log "Instalando paquetes: ${PAQUETES[*]}"
  DEBIAN_FRONTEND=noninteractive apt-get install -y "${PAQUETES[@]}"

  a2enmod rewrite headers ssl >/dev/null 2>&1 || true
  systemctl enable --now apache2 >/dev/null 2>&1 || true
  systemctl enable --now fail2ban >/dev/null 2>&1 || true

  log "Paquetes instalados y Apache/fail2ban habilitados."
}

crear_directorios() {
  log "Creando estructura de directorios de la aplicación..."

  install -d -m 750 -o www-data -g www-data "$APP_DIR"
  install -d -m 750 -o www-data -g www-data "$LOG_DIR"
  install -d -m 700 -o root -g root "$BACKUP_DIR"

  log "Directorios listos: $APP_DIR, $LOG_DIR, $BACKUP_DIR."
}

verificar() {
  log "Verificando versiones instaladas..."
  command -v apache2 >/dev/null && apache2 -v | head -n1
  command -v php >/dev/null && php -v | head -n1
  command -v mysql >/dev/null && mysql --version
  command -v git >/dev/null && git --version
  command -v ufw >/dev/null && ufw --version | head -n1
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 [comando]

Comandos:
  (sin comando)   Instala paquetes + crea directorios + verifica versiones.
  paquetes        Solo instala/actualiza los paquetes del sistema.
  directorios     Solo crea la estructura de directorios de la app.
  verificar       Muestra las versiones instaladas.
  -h, --help      Muestra esta ayuda.

Requiere ejecutarse con privilegios de root (sudo).

Después de instalar, seguí con:
  sudo ./gestion_usuarios.sh crear   # crear los usuarios del sistema
  sudo ./firewall.sh                 # configurar ufw + fail2ban
  sudo ./desplegar.sh                # bajar y desplegar el código
EOF
}

main() {
  local comando="${1:-}"
  case "$comando" in
    paquetes) requiere_root; instalar_paquetes ;;
    directorios) requiere_root; crear_directorios ;;
    verificar) verificar ;;
    -h|--help|help) mostrar_ayuda ;;
    "")
      requiere_root
      instalar_paquetes
      crear_directorios
      verificar
      log "Instalación completa."
      ;;
    *) fallar "comando desconocido: '$comando'. Usá '$0 --help'." ;;
  esac
}

main "$@"
