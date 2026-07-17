#!/usr/bin/env bash
#
# firewall.sh — Configuración de firewall (ufw) y fail2ban para el
# servidor Tornalyx (SGDM).
#
# Aplica la política de acceso remoto documentada en
# docs/primera-entrega/TAREA7-CIBERSEGURIDAD/politicas-seguridad.md:
# solo SSH (clave pública), HTTP y HTTPS permitidos; todo lo demás
# denegado por defecto. FTP/Telnet quedan explícitamente prohibidos.
#
# Requisito: ASO tercera entrega ("Implementación de firewall, iptables,
# antivirus, entre otros dentro del servidor").
#
# Uso:
#   sudo ./firewall.sh aplicar     # configura ufw + fail2ban (idempotente)
#   sudo ./firewall.sh estado      # muestra reglas activas
#   sudo ./firewall.sh -h
#
# Nota: ufw es un front-end de iptables/nftables; usarlo es la forma
# estándar y menos propensa a errores de administrar iptables en Ubuntu.

set -euo pipefail

SSH_PUERTO="${SSH_PUERTO:-22}"
LOG_FILE="/var/log/tornalyx/firewall.log"

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
  [[ "$(id -u)" -eq 0 ]] || fallar "este script debe ejecutarse como root (sudo)."
}

requiere_comando() {
  command -v "$1" >/dev/null 2>&1 || fallar "falta el comando '$1'. Instalalo primero (./instalar.sh)."
}

configurar_ufw() {
  requiere_comando ufw

  log "Aplicando política por defecto: denegar entrante, permitir saliente..."
  ufw --force default deny incoming
  ufw --force default allow outgoing

  log "Permitiendo SSH (puerto $SSH_PUERTO), HTTP (80) y HTTPS (443)..."
  ufw allow "$SSH_PUERTO"/tcp comment 'SSH (clave publica)'
  ufw allow 80/tcp  comment 'HTTP'
  ufw allow 443/tcp comment 'HTTPS'

  # FTP/Telnet quedan explícitamente prohibidos (no se abren sus puertos).
  # MySQL (3306) no se expone: la app y mysqldump corren en el mismo host.

  log "Habilitando ufw..."
  ufw --force enable

  log "Firewall (ufw) configurado."
}

configurar_fail2ban() {
  requiere_comando fail2ban-client

  local jail_local="/etc/fail2ban/jail.local"
  log "Escribiendo jail para sshd en $jail_local..."
  cat >"$jail_local" <<EOF
# Generado por firewall.sh — Tornalyx
[sshd]
enabled  = true
port     = $SSH_PUERTO
filter   = sshd
logpath  = /var/log/auth.log
maxretry = 5
findtime = 15m
bantime  = 1h
EOF

  systemctl enable --now fail2ban >/dev/null 2>&1 || systemctl restart fail2ban
  log "fail2ban configurado y activo (jail sshd: 5 intentos / 15 min -> baneo 1h)."
}

cmd_aplicar() {
  requiere_root
  configurar_ufw
  configurar_fail2ban
  log "Configuración de firewall completa."
}

cmd_estado() {
  requiere_comando ufw
  echo "== Estado de ufw =="
  ufw status verbose
  echo
  echo "== Estado de fail2ban =="
  fail2ban-client status 2>/dev/null || echo "fail2ban no está corriendo."
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 <comando>

Comandos:
  aplicar   Configura ufw (deny por defecto, permite SSH/HTTP/HTTPS) y
            fail2ban (jail sshd). Requiere root.
  estado    Muestra las reglas de ufw y el estado de fail2ban.
  -h, --help  Muestra esta ayuda.

Variables de entorno: SSH_PUERTO (default 22).
EOF
}

main() {
  local comando="${1:-}"
  case "$comando" in
    aplicar) cmd_aplicar ;;
    estado) cmd_estado ;;
    -h|--help|help|"") mostrar_ayuda ;;
    *) fallar "comando desconocido: '$comando'. Usá '$0 --help'." ;;
  esac
}

main "$@"
