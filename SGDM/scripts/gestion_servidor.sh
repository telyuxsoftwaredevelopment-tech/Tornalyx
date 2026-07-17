#!/usr/bin/env bash
#
# gestion_servidor.sh — Script general de gestión del servidor Tornalyx (SGDM).
#
# Punto de entrada único para las tareas de mantenimiento diarias de
# admin_tornalyx / operador_web: ver estado general, reiniciar servicios
# puntuales, revisar logs, actualizar el sistema operativo y limpiar
# archivos temporales. No reemplaza a los scripts especializados
# (gestion_usuarios.sh, respaldo.sh, monitoreo_bd.sh, etc.); los referencia.
#
# Requisito: ASO tercera entrega ("Script general de gestión del servidor")
# y objetivo específico 13 (mantenimiento básico).
#
# Uso:
#   ./gestion_servidor.sh estado             # resumen del servidor
#   ./gestion_servidor.sh reiniciar apache2   # reinicia un servicio puntual
#   ./gestion_servidor.sh logs apache2        # últimas líneas del log
#   ./gestion_servidor.sh actualizar          # apt update && upgrade
#   ./gestion_servidor.sh limpiar             # limpieza de temporales/paquetes huérfanos
#   ./gestion_servidor.sh -h

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
LOG_FILE="/var/log/tornalyx/gestion_servidor.log"

SERVICIOS_PERMITIDOS=(apache2 mysql fail2ban cron ufw)

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
  [[ "$(id -u)" -eq 0 ]] || fallar "esta operación requiere privilegios de root (sudo)."
}

validar_servicio() {
  local s="$1" ok=0 permitido
  for permitido in "${SERVICIOS_PERMITIDOS[@]}"; do
    [[ "$s" == "$permitido" ]] && ok=1
  done
  [[ "$ok" -eq 1 ]] || fallar "servicio no permitido: '$s'. Válidos: ${SERVICIOS_PERMITIDOS[*]}."
}

cmd_estado() {
  echo "== Estado general del servidor Tornalyx =="
  echo "Fecha:  $(date)"
  echo "Uptime: $(uptime -p 2>/dev/null || uptime)"
  echo "Carga:  $(uptime | awk -F'load average:' '{print $2}')"
  echo
  echo "-- Disco --"
  df -h / /var 2>/dev/null | awk 'NR==1 || /\//'
  echo
  echo "-- Memoria --"
  free -h
  echo
  echo "-- Servicios --"
  local s estado_srv
  for s in "${SERVICIOS_PERMITIDOS[@]}"; do
    estado_srv="$(systemctl is-active "$s" 2>/dev/null || true)"
    [[ -z "$estado_srv" ]] && estado_srv="no-instalado"
    printf '  %-10s %s\n' "$s" "$estado_srv"
  done
  echo
  echo "Para más detalle: ./monitoreo_sistema.sh · ./monitoreo_bd.sh estado"
}

cmd_reiniciar() {
  requiere_root
  local s="${1:-}"
  [[ -n "$s" ]] || fallar "uso: $0 reiniciar <servicio>"
  validar_servicio "$s"
  log "Reiniciando servicio '$s'..."
  systemctl restart "$s"
  systemctl is-active --quiet "$s" && log "'$s' reiniciado correctamente." || fallar "'$s' no quedó activo tras el reinicio."
}

cmd_logs() {
  local s="${1:-}" lineas="${2:-50}"
  [[ -n "$s" ]] || fallar "uso: $0 logs <servicio> [lineas]"
  validar_servicio "$s"
  journalctl -u "$s" -n "$lineas" --no-pager 2>/dev/null \
    || fallar "no se pudo leer el log de '$s' (¿corriendo con journald?)."
}

cmd_actualizar() {
  requiere_root
  log "Actualizando paquetes del sistema (apt update && upgrade)..."
  apt-get update -y
  DEBIAN_FRONTEND=noninteractive apt-get upgrade -y
  log "Sistema actualizado."
}

cmd_limpiar() {
  requiere_root
  log "Limpiando paquetes huérfanos y caché de apt..."
  apt-get autoremove -y
  apt-get autoclean -y

  log "Limpiando archivos temporales de PHP/Apache con más de 7 días..."
  find /tmp -maxdepth 1 -mtime +7 -type f -delete 2>/dev/null || true

  log "Limpieza completa."
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 <comando> [args]

Comandos:
  estado                      Resumen de disco, memoria, carga y servicios.
  reiniciar <servicio>        Reinicia un servicio permitido (${SERVICIOS_PERMITIDOS[*]}).
  logs <servicio> [lineas]    Últimas líneas de log del servicio (default 50).
  actualizar                  apt update && apt upgrade.
  limpiar                     Elimina paquetes huérfanos, caché y temporales viejos.
  -h, --help                  Muestra esta ayuda.

Scripts relacionados en $SCRIPT_DIR:
  gestion_usuarios.sh   respaldo.sh   monitoreo_sistema.sh
  monitoreo_bd.sh       firewall.sh   instalar.sh   desplegar.sh
EOF
}

main() {
  local comando="${1:-}"; shift || true
  case "$comando" in
    estado) cmd_estado ;;
    reiniciar) cmd_reiniciar "${1:-}" ;;
    logs) cmd_logs "${1:-}" "${2:-}" ;;
    actualizar) cmd_actualizar ;;
    limpiar) cmd_limpiar ;;
    -h|--help|help|"") mostrar_ayuda ;;
    *) fallar "comando desconocido: '$comando'. Usá '$0 --help'." ;;
  esac
}

main "$@"
