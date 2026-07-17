#!/usr/bin/env bash
#
# monitoreo_sistema.sh — Monitoreo básico de recursos del servidor Tornalyx.
#
# Complementa una herramienta de monitoreo gráfico (Zabbix/Grafana) con un
# chequeo liviano por línea de comandos: CPU, memoria, disco y estado de
# los servicios críticos (apache2, mysql/mariadb, fail2ban, cron). Registra
# alertas en el log cuando se superan los umbrales configurados.
#
# Requisito: ASO segunda entrega ("Sistema de monitoreo implementado en el
# servidor") y objetivo específico 13 (mantenimiento básico).
#
# Uso:
#   ./monitoreo_sistema.sh            # reporte completo por pantalla
#   ./monitoreo_sistema.sh chequear   # solo evalúa umbrales (para cron)
#   ./monitoreo_sistema.sh -h
#
# Variables de entorno: UMBRAL_CPU, UMBRAL_MEM, UMBRAL_DISCO (porcentajes,
# default 85).

set -euo pipefail

UMBRAL_CPU="${UMBRAL_CPU:-85}"
UMBRAL_MEM="${UMBRAL_MEM:-85}"
UMBRAL_DISCO="${UMBRAL_DISCO:-85}"
LOG_FILE="/var/log/tornalyx/monitoreo.log"

SERVICIOS=(apache2 mysql fail2ban cron)

log() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
  echo "$msg"
  mkdir -p "$(dirname -- "$LOG_FILE")" 2>/dev/null && echo "$msg" >>"$LOG_FILE" 2>/dev/null || true
}

alerta() {
  log "ALERTA: $*"
}

uso_cpu() {
  # % de CPU en uso = 100 - % idle, tomado de un muestreo de 1 segundo con vmstat.
  vmstat 1 2 | tail -n1 | awk '{print 100 - $15}'
}

uso_memoria() {
  free | awk '/Mem:/ {printf "%.0f", ($2-$7)/$2 * 100}'
}

uso_disco() {
  df -P / | awk 'NR==2 {gsub("%","",$5); print $5}'
}

reporte_servicios() {
  local s estado
  for s in "${SERVICIOS[@]}"; do
    if systemctl is-active --quiet "$s" 2>/dev/null; then
      estado="activo"
    else
      estado="INACTIVO"
      alerta "el servicio '$s' no está activo."
    fi
    printf '  %-10s %s\n' "$s" "$estado"
  done
}

reporte_completo() {
  local cpu mem disco
  cpu="$(uso_cpu)"; mem="$(uso_memoria)"; disco="$(uso_disco)"

  echo "== Monitoreo del sistema — Tornalyx — $(date '+%Y-%m-%d %H:%M:%S') =="
  echo "Uptime: $(uptime -p 2>/dev/null || uptime)"
  echo
  printf 'CPU:    %s%%\n' "$cpu"
  printf 'Memoria:%s%%\n' "$mem"
  printf 'Disco / :%s%%\n' "$disco"
  echo
  echo "Servicios:"
  reporte_servicios
  echo
  chequear_umbrales "$cpu" "$mem" "$disco"
}

chequear_umbrales() {
  local cpu="${1:-$(uso_cpu)}" mem="${2:-$(uso_memoria)}" disco="${3:-$(uso_disco)}"

  awk -v v="$cpu" -v u="$UMBRAL_CPU" 'BEGIN{exit !(v+0>u+0)}' && alerta "uso de CPU en ${cpu}% (umbral ${UMBRAL_CPU}%)."
  awk -v v="$mem" -v u="$UMBRAL_MEM" 'BEGIN{exit !(v+0>u+0)}' && alerta "uso de memoria en ${mem}% (umbral ${UMBRAL_MEM}%)."
  awk -v v="$disco" -v u="$UMBRAL_DISCO" 'BEGIN{exit !(v+0>u+0)}' && alerta "uso de disco en ${disco}% (umbral ${UMBRAL_DISCO}%)."
  return 0
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 [comando]

Comandos:
  (sin comando)   Reporte completo de CPU, memoria, disco y servicios.
  chequear        Solo evalúa umbrales y registra alertas (ideal para cron).
  -h, --help      Muestra esta ayuda.

Variables de entorno: UMBRAL_CPU, UMBRAL_MEM, UMBRAL_DISCO (default 85).
EOF
}

main() {
  local comando="${1:-}"
  case "$comando" in
    chequear) chequear_umbrales ;;
    -h|--help|help) mostrar_ayuda ;;
    "") reporte_completo ;;
    *) echo "ERROR: comando desconocido: '$comando'. Usá '$0 --help'." >&2; exit 1 ;;
  esac
}

main "$@"
