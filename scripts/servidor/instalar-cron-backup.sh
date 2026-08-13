#!/usr/bin/env bash
# Tornalyx — instala el cron de respaldo en el servidor real.
# Ejecutar como root. Asume que el repo ya está desplegado en /opt/tornalyx
# (ajustar la ruta si el despliegue real usa otra).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEST_REPO="${TORNALYX_DEST:-/opt/tornalyx}"

echo "==> Creando /etc/tornalyx/ para el archivo de credenciales de respaldo"
mkdir -p /etc/tornalyx
if [[ ! -f /etc/tornalyx/backup.env ]]; then
    cp "$SCRIPT_DIR/backup.env.example" /etc/tornalyx/backup.env
    echo "    Creado /etc/tornalyx/backup.env desde el ejemplo. EDITALO con la contraseña real de tornalyx_backup antes de seguir."
fi
chmod 600 /etc/tornalyx/backup.env

echo "==> Creando directorio de respaldos"
mkdir -p /var/backups/tornalyx/db /var/backups/tornalyx/avatares

echo "==> Instalando el cron"
install -m 0644 "$SCRIPT_DIR/cron-tornalyx" /etc/cron.d/tornalyx
# El cron referencia $DEST_REPO/scripts/servidor/respaldo.sh; si el repo real
# no vive en /opt/tornalyx, editar /etc/cron.d/tornalyx después de este paso.

echo "==> Listo. Verificar en el servidor con:"
echo "    cat /etc/cron.d/tornalyx"
echo "    systemctl status cron"
echo "    (esperar a las 03:05 o correr manualmente: sudo TORNALYX_BACKUP_ENV=/etc/tornalyx/backup.env $DEST_REPO/scripts/servidor/respaldo.sh)"
