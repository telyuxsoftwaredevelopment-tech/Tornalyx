#!/usr/bin/env bash
# Tornalyx — instalación de Prometheus + node_exporter + mysqld_exporter +
# Grafana en el servidor real. Ejecutar como root (Debian/Ubuntu).
# Requiere que DB_MONITOR_PASS esté seteada con la contraseña real del
# usuario tornalyx_monitor (SGDM/backend/database/dcl.sql:95-99).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DB_MONITOR_PASS="${DB_MONITOR_PASS:?Definí DB_MONITOR_PASS con la contraseña de tornalyx_monitor antes de correr este script}"

echo "==> Instalando node_exporter (métricas del sistema)"
apt-get update
apt-get install -y prometheus-node-exporter

echo "==> Instalando mysqld_exporter (métricas de MySQL)"
apt-get install -y prometheus-mysqld-exporter
install -d -m 0750 -o prometheus -g prometheus /etc/prometheus-mysqld-exporter
(umask 077; cat > /etc/prometheus-mysqld-exporter/.my.cnf <<EOF
[client]
user=tornalyx_monitor
password=${DB_MONITOR_PASS}
EOF
)
chown prometheus:prometheus /etc/prometheus-mysqld-exporter/.my.cnf

echo "==> Instalando Prometheus"
apt-get install -y prometheus
install -m 0644 "$SCRIPT_DIR/prometheus.yml" /etc/prometheus/prometheus.yml
systemctl restart prometheus
systemctl enable prometheus

echo "==> Instalando Grafana"
apt-get install -y apt-transport-https software-properties-common
if [[ ! -f /etc/apt/sources.list.d/grafana.list ]]; then
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://apt.grafana.com/gpg.key | gpg --dearmor -o /etc/apt/keyrings/grafana.gpg
    echo "deb [signed-by=/etc/apt/keyrings/grafana.gpg] https://apt.grafana.com stable main" \
        > /etc/apt/sources.list.d/grafana.list
    apt-get update
fi
apt-get install -y grafana
systemctl restart grafana-server
systemctl enable grafana-server

echo "==> Listo. Verificar en el servidor con:"
echo "    systemctl status prometheus-node-exporter prometheus-mysqld-exporter prometheus grafana-server"
echo "    curl -s http://localhost:9100/metrics | head"
echo "    curl -s http://localhost:9104/metrics | head"
echo "    curl -s http://localhost:9090/-/healthy   # Prometheus"
echo "    abrir http://<ip-del-vps>:3000 (Grafana, usuario admin / contraseña generada en el primer login)"
echo "    IMPORTANTE: el puerto 3000 solo debe quedar abierto para la IP del admin"
echo "    (ver scripts/servidor/firewall-ufw.sh del bloque A, ya lo restringe)."
