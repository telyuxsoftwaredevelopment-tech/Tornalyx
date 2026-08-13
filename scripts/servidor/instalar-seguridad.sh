#!/usr/bin/env bash
# Tornalyx — instalación de seguridad de servidor: firewall + fail2ban (IPS)
# + AIDE (IDS de integridad de archivos). Ejecutar como root en el VPS real,
# no en Render (ver docs/entrega2/seguridad-servidor.md).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Instalando paquetes: ufw, fail2ban, aide"
apt-get update
apt-get install -y ufw fail2ban aide aide-common

echo "==> Aplicando reglas de firewall"
"$SCRIPT_DIR/firewall-ufw.sh"

echo "==> Instalando jail de fail2ban para Tornalyx"
install -m 0644 "$SCRIPT_DIR/fail2ban-tornalyx.local" /etc/fail2ban/jail.d/tornalyx.local
systemctl restart fail2ban
systemctl enable fail2ban

echo "==> Inicializando base de datos de integridad de archivos (AIDE / IDS)"
aideinit
mv -f /var/lib/aide/aide.db.new /var/lib/aide/aide.db

echo "==> Listo. Verificar manualmente en el servidor con:"
echo "    ufw status verbose"
echo "    fail2ban-client status"
echo "    fail2ban-client status apache-auth"
echo "    aide --check"
