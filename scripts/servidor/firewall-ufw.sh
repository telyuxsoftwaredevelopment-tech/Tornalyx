#!/usr/bin/env bash
# Tornalyx — reglas de firewall (ufw) para el VPS de producción.
# Ejecutar como root/sudo en el servidor real (Debian/Ubuntu). Política:
# denegar todo el tráfico entrante por defecto, permitir solo lo necesario
# (ver docs/entrega2/esquema-red.md para la justificación de cada puerto).
set -euo pipefail

# IP fija del admin: reemplazar antes de aplicar en el servidor real.
ADMIN_IP="${ADMIN_IP:?Definí ADMIN_IP con la IP fija del admin antes de correr este script (export ADMIN_IP=203.0.113.10)}"

if ! command -v ufw >/dev/null 2>&1; then
    apt-get update
    apt-get install -y ufw
fi

ufw --force reset

ufw default deny incoming
ufw default allow outgoing

ufw allow from "$ADMIN_IP" to any port 22 proto tcp comment 'SSH admin'
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
ufw allow from "$ADMIN_IP" to any port 3000 proto tcp comment 'Grafana admin'

ufw logging on
ufw --force enable
ufw status verbose
