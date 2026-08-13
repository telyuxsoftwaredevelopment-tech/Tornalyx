#!/usr/bin/env bash
# Tornalyx — respaldo diario de base de datos y avatares.
# Ejecutar en el servidor real vía cron (ver scripts/servidor/cron-tornalyx
# y docs/entrega2/politica-backups.md). Usa el usuario tornalyx_backup
# (SGDM/backend/database/dcl.sql), que solo tiene SELECT/LOCK TABLES/SHOW
# VIEW — no puede escribir ni borrar datos de negocio.
set -euo pipefail

ENV_FILE="${TORNALYX_BACKUP_ENV:-/etc/tornalyx/backup.env}"
if [[ ! -f "$ENV_FILE" ]]; then
    echo "No se encontró $ENV_FILE (ver scripts/servidor/backup.env.example)" >&2
    exit 1
fi
# shellcheck disable=SC1090
source "$ENV_FILE"

FECHA="$(date +%F)"
DIA_SEMANA="$(date +%u)"   # 1=lunes .. 7=domingo
DIA_MES="$(date +%d)"

DIR_DB="$BACKUP_DIR/db"
DIR_AVATARES="$BACKUP_DIR/avatares"
mkdir -p "$DIR_DB" "$DIR_AVATARES"

echo "==> Respaldando base de datos ($DB_NAME)"
DUMP_DB="$DIR_DB/tornalyx_db_${FECHA}.sql.gz"
MYSQL_PWD="$DB_BACKUP_PASS" mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    -h "$DB_HOST" -P "$DB_PORT" -u "$DB_BACKUP_USER" \
    "$DB_NAME" | gzip > "$DUMP_DB"
echo "    -> $DUMP_DB"

echo "==> Respaldando avatares ($AVATARES_DIR)"
DUMP_AVATARES="$DIR_AVATARES/avatares_${FECHA}.tar.gz"
tar -czf "$DUMP_AVATARES" -C "$(dirname "$AVATARES_DIR")" "$(basename "$AVATARES_DIR")"
echo "    -> $DUMP_AVATARES"

# Copias semanales (domingo) y mensuales (día 1): se guardan aparte para no
# perderlas cuando la retención diaria las borre (ver política de retención
# escalonada en docs/entrega2/politica-backups.md).
if [[ "$DIA_SEMANA" == "7" ]]; then
    cp "$DUMP_DB" "$DIR_DB/semanal_tornalyx_db_${FECHA}.sql.gz"
    cp "$DUMP_AVATARES" "$DIR_AVATARES/semanal_avatares_${FECHA}.tar.gz"
fi
if [[ "$DIA_MES" == "01" ]]; then
    cp "$DUMP_DB" "$DIR_DB/mensual_tornalyx_db_${FECHA}.sql.gz"
    cp "$DUMP_AVATARES" "$DIR_AVATARES/mensual_avatares_${FECHA}.tar.gz"
fi

echo "==> Aplicando retención"
# Diarias: borrar las que no sean semanales/mensuales y superen RETENCION_DIARIA días.
find "$DIR_DB" -maxdepth 1 -name 'tornalyx_db_*.sql.gz' -mtime "+${RETENCION_DIARIA}" -delete
find "$DIR_AVATARES" -maxdepth 1 -name 'avatares_*.tar.gz' -mtime "+${RETENCION_DIARIA}" -delete
# Semanales: retener RETENCION_SEMANAL semanas (~7 días cada una).
find "$DIR_DB" -maxdepth 1 -name 'semanal_*.sql.gz' -mtime "+$((RETENCION_SEMANAL * 7))" -delete
find "$DIR_AVATARES" -maxdepth 1 -name 'semanal_*.tar.gz' -mtime "+$((RETENCION_SEMANAL * 7))" -delete
# Mensuales: retener RETENCION_MENSUAL meses (~30 días cada uno).
find "$DIR_DB" -maxdepth 1 -name 'mensual_*.sql.gz' -mtime "+$((RETENCION_MENSUAL * 30))" -delete
find "$DIR_AVATARES" -maxdepth 1 -name 'mensual_*.tar.gz' -mtime "+$((RETENCION_MENSUAL * 30))" -delete

# TODO(infra-real, fuera de alcance académico): sincronizar $BACKUP_DIR a un
# storage externo (ej. `rclone sync "$BACKUP_DIR" remoto:tornalyx-backups`)
# para sobrevivir a la pérdida completa del VPS. Ver docs/entrega2/politica-backups.md.

echo "==> Respaldo completo."
