# Entrega 2 — Bloque B: Backups y monitoreo (22 pts) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cubrir los 5 criterios del Bloque B de la rúbrica: política de respaldos (tipos + cronograma), script de respaldo automatizado por cron, y sistema de monitoreo (Grafana + Prometheus) para el VPS real.

**Architecture:** Este bloque no tiene nada previo en el repo (a diferencia del Bloque A). Se construye desde cero: un documento de política, un script de backup que reutiliza el usuario `tornalyx_backup` ya creado en `SGDM/backend/database/dcl.sql`, su automatización vía cron, y la configuración de Prometheus + node_exporter + mysqld_exporter + Grafana. Igual que en el Bloque A, no hay VPS real disponible ahora: se entregan scripts/config listos para aplicar manualmente.

**Tech Stack:** Bash + `mysqldump` (backup), cron, Prometheus + node_exporter + mysqld_exporter + Grafana (monitoreo) — target Debian/Ubuntu.

## Global Constraints

- Documentos nuevos en `docs/entrega2/`, scripts en `scripts/servidor/` — nunca en la raíz (regla del CLAUDE.md del proyecto).
- Ningún archivo nuevo supera 500 líneas.
- Reusar el usuario `tornalyx_backup` ya definido en `SGDM/backend/database/dcl.sql:108-110` (`SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER`) — no crear un usuario de BD nuevo para esto.
- La contraseña de `tornalyx_backup` se lee de una variable de entorno (`DB_BACKUP_PASS`) desde un archivo con permisos `600`, nunca hardcodeada en el script ni commiteada al repo.
- Alcance real de los datos a respaldar (confirmado en el código, no supuesto): la base `tornalyx_db` completa vía `mysqldump`, más el directorio `SGDM/frontend/uploads/avatars` (subida de archivos real — ver `SGDM/backend/controllers/PerfilController.php:33`, `AVATAR_DIR`). El resto del código de la app ya vive en git, no necesita backup aparte.
- Sin VPS real ni `mysqldump`/`cron`/cliente de MySQL disponibles en esta máquina de desarrollo: verificación limitada a `bash -n` y revisión manual; la prueba real (`mysqldump` de verdad, restore de prueba, cron corriendo) se documenta como paso pendiente para el servidor.
- OS asumido: Debian/Ubuntu, igual que el Bloque A.

---

### Task 1: Política de respaldos (tipos + cronograma)

**Files:**
- Create: `docs/entrega2/politica-backups.md`

**Interfaces:**
- Consumes: usuario `tornalyx_backup` de `SGDM/backend/database/dcl.sql`.
- Produces: documento que las Tasks 2-3 implementan.

- [ ] **Step 1: Escribir el documento**

Contenido de `docs/entrega2/politica-backups.md`:

```markdown
# Política de respaldos — Tornalyx (Entrega 2)

## Qué se respalda

| Dato | Origen | Motivo |
|------|--------|--------|
| Base de datos completa (`tornalyx_db`) | MySQL, vía `mysqldump` con el usuario `tornalyx_backup` (`SGDM/backend/database/dcl.sql:108-110`) | Contiene usuarios, torneos, partidos, resultados — todo el estado de negocio, no reconstruible. |
| Avatares subidos por usuarios | `SGDM/frontend/uploads/avatars/` (ver `SGDM/backend/controllers/PerfilController.php:33`) | Único dato binario que vive fuera de git y fuera de la base. |
| Código de la aplicación | — | **No se respalda por separado**: ya vive versionado en git/GitHub, que es su propio sistema de respaldo distribuido. |

## Tipo de respaldo

- **Lógico completo (full) de la base**, vía `mysqldump --single-transaction`
  (consistente sin bloquear escrituras en tablas InnoDB, que es el motor de
  todas las tablas de `schema.sql`). No se usa incremental/binlog: el
  tamaño de la base de un sistema de gestión de torneos universitario no
  justifica la complejidad operativa de un point-in-time recovery.
- **Copia completa (full) del directorio de avatares** vía `tar`, ya que es
  la manera más simple de mantener consistencia con el `avatar_url` que
  apunta a esos archivos en la base.

## Cronograma

| Respaldo | Frecuencia | Horario | Retención |
|----------|------------|---------|-----------|
| Base de datos | Diario | 03:00 (hora del servidor, baja carga) | 7 diarios + 4 semanales (domingo) + 3 mensuales (día 1) |
| Avatares | Diario | 03:05 (después de la base, mismo cron) | Igual que la base: 7 diarios + 4 semanales + 3 mensuales |

La retención escalonada (diario/semanal/mensual) evita que un error
detectado tarde (ej.: una semana después) ya no tenga respaldo disponible,
sin guardar 30 copias diarias completas indefinidamente.

## Dónde se guardan

- Copia local en el propio VPS: `/var/backups/tornalyx/` (fuera del
  `DocumentRoot` de Apache, así nunca queda servible por HTTP).
- Recomendado para producción real (fuera del alcance de este entregable
  académico): sincronizar `/var/backups/tornalyx/` a un storage externo
  (ej. `rclone` a un bucket S3/Backblaze) para sobrevivir a la pérdida
  completa del VPS. El script de la Task 2 deja un `TODO` explícito marcado
  para este punto, no lo implementa.

## Prueba de restauración

Un respaldo que nunca se probó restaurar no es un respaldo confiable. Antes
de dar por buena la política en el servidor real:

```bash
gunzip -c /var/backups/tornalyx/db/tornalyx_db_YYYY-MM-DD.sql.gz | \
  mysql -u tornalyx_dev -p tornalyx_dev   # restaurar contra la base de DEV, nunca contra producción
```
```

- [ ] **Step 2: Confirmar que el path de avatares citado sigue siendo correcto**

```bash
grep -n "AVATAR_DIR" SGDM/backend/controllers/PerfilController.php
```

Expected: una línea mostrando `private const AVATAR_DIR = __DIR__ . '/../../frontend/uploads/avatars';`. Si el path cambió, corregir el documento antes de seguir.

- [ ] **Step 3: Commit**

```bash
git add docs/entrega2/politica-backups.md
git commit -m "docs: agregar politica de respaldos (tipos y cronograma)"
```

---

### Task 2: Script de respaldo (`respaldo.sh`)

**Files:**
- Create: `scripts/servidor/respaldo.sh`
- Create: `scripts/servidor/backup.env.example`

**Interfaces:**
- Consumes: `DB_BACKUP_USER`/`DB_BACKUP_PASS` (usuario `tornalyx_backup` de `dcl.sql`), directorio `SGDM/frontend/uploads/avatars`.
- Produces: archivos en `/var/backups/tornalyx/{db,avatares}/` que Task 3 automatiza vía cron.

- [ ] **Step 1: Crear el archivo de ejemplo de variables de entorno**

`scripts/servidor/backup.env.example`:

```bash
# Copiar a /etc/tornalyx/backup.env en el servidor real, con permisos 600
# (chmod 600, propietario del usuario que corre el cron de respaldo).
# NUNCA commitear el archivo real con la contraseña verdadera.

DB_BACKUP_USER=tornalyx_backup
DB_BACKUP_PASS=CAMBIAR_PASSWORD_BACKUP
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=tornalyx_db

AVATARES_DIR=/var/www/html/SGDM/frontend/uploads/avatars
BACKUP_DIR=/var/backups/tornalyx

# Cuántas copias diarias/semanales/mensuales conservar (ver
# docs/entrega2/politica-backups.md).
RETENCION_DIARIA=7
RETENCION_SEMANAL=4
RETENCION_MENSUAL=3
```

- [ ] **Step 2: Crear el script de respaldo**

`scripts/servidor/respaldo.sh`:

```bash
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
```

- [ ] **Step 3: Verificar sintaxis del script**

```bash
bash -n scripts/servidor/respaldo.sh
```

Expected: sin salida (sintaxis válida).

- [ ] **Step 4: Confirmar que las variables usadas en el script existen en el `.env.example`**

```bash
for v in DB_BACKUP_USER DB_BACKUP_PASS DB_HOST DB_PORT DB_NAME AVATARES_DIR BACKUP_DIR RETENCION_DIARIA RETENCION_SEMANAL RETENCION_MENSUAL; do
  grep -q "^$v=" scripts/servidor/backup.env.example && echo "OK $v" || echo "FALTA $v"
done
```

Expected: `OK` para las 9 variables, ningún `FALTA`.

- [ ] **Step 5: Marcar como ejecutable y commit**

```bash
chmod +x scripts/servidor/respaldo.sh
git add scripts/servidor/respaldo.sh scripts/servidor/backup.env.example
git commit -m "feat: agregar script de respaldo de base de datos y avatares"
```

---

### Task 3: Automatización vía cron

**Files:**
- Create: `scripts/servidor/cron-tornalyx`
- Create: `scripts/servidor/instalar-cron-backup.sh`

**Interfaces:**
- Consumes: `scripts/servidor/respaldo.sh` (Task 2).
- Produces: entrada de cron activa en el servidor real; evidencia para el checklist.

- [ ] **Step 1: Crear el archivo de definición de cron**

`scripts/servidor/cron-tornalyx`:

```cron
# Tornalyx — cron de respaldo diario.
# Instalar en /etc/cron.d/tornalyx (lo hace instalar-cron-backup.sh).
# Corre como root porque necesita leer SGDM/frontend/uploads/avatars, que es
# propiedad de www-data; mysqldump igual se autentica con el usuario de BD
# de mínimo privilegio tornalyx_backup, no con root de MySQL.
#
# minuto hora día mes día-semana usuario comando
5 3 * * * root TORNALYX_BACKUP_ENV=/etc/tornalyx/backup.env /opt/tornalyx/scripts/servidor/respaldo.sh >> /var/log/tornalyx-backup.log 2>&1
```

- [ ] **Step 2: Crear el script de instalación del cron**

`scripts/servidor/instalar-cron-backup.sh`:

```bash
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
```

- [ ] **Step 3: Verificar sintaxis del script de instalación**

```bash
bash -n scripts/servidor/instalar-cron-backup.sh
```

Expected: sin salida (sintaxis válida).

- [ ] **Step 4: Verificar formato del archivo de cron**

```bash
grep -c "^5 3 \* \* \* root" scripts/servidor/cron-tornalyx
```

Expected: `1`.

- [ ] **Step 5: Commit**

```bash
chmod +x scripts/servidor/instalar-cron-backup.sh
git add scripts/servidor/cron-tornalyx scripts/servidor/instalar-cron-backup.sh
git commit -m "feat: agregar automatizacion de respaldo via cron"
```

---

### Task 4: Monitoreo (Prometheus + node_exporter + mysqld_exporter + Grafana)

**Files:**
- Create: `scripts/servidor/instalar-monitoreo.sh`
- Create: `scripts/servidor/prometheus.yml`
- Create: `docs/entrega2/monitoreo.md`

**Interfaces:**
- Consumes: usuario `tornalyx_monitor` ya definido en `SGDM/backend/database/dcl.sql:95-99` (`SELECT` + `PROCESS`, sin permisos de escritura).
- Produces: stack de monitoreo accesible en `http://<vps>:3000` (Grafana), restringido por `scripts/servidor/firewall-ufw.sh` del Bloque A.

- [ ] **Step 1: Crear la configuración de Prometheus**

`scripts/servidor/prometheus.yml`:

```yaml
# Tornalyx — configuración de Prometheus.
# Copiar a /etc/prometheus/prometheus.yml en el servidor real.
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  # Métricas del sistema operativo: CPU, memoria, disco, red.
  - job_name: "node"
    static_configs:
      - targets: ["localhost:9100"]

  # Métricas de MySQL: conexiones, queries lentas, tamaño de tablas, etc.
  # mysqld_exporter se autentica con tornalyx_monitor (dcl.sql), que solo
  # tiene SELECT + PROCESS, nunca puede escribir datos de negocio.
  - job_name: "mysql"
    static_configs:
      - targets: ["localhost:9104"]
```

- [ ] **Step 2: Crear el script de instalación del stack de monitoreo**

`scripts/servidor/instalar-monitoreo.sh`:

```bash
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
cat > /etc/prometheus-mysqld-exporter/.my.cnf <<EOF
[client]
user=tornalyx_monitor
password=${DB_MONITOR_PASS}
host=127.0.0.1
EOF
chmod 600 /etc/prometheus-mysqld-exporter/.my.cnf
chown prometheus:prometheus /etc/prometheus-mysqld-exporter/.my.cnf

echo "==> Instalando Prometheus"
apt-get install -y prometheus
install -m 0644 "$SCRIPT_DIR/prometheus.yml" /etc/prometheus/prometheus.yml
systemctl restart prometheus
systemctl enable prometheus

echo "==> Instalando Grafana"
apt-get install -y apt-transport-https software-properties-common
if [[ ! -f /etc/apt/sources.list.d/grafana.list ]]; then
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
```

- [ ] **Step 3: Verificar sintaxis del script**

```bash
bash -n scripts/servidor/instalar-monitoreo.sh
```

Expected: sin salida (sintaxis válida).

- [ ] **Step 4: Escribir el documento explicativo**

`docs/entrega2/monitoreo.md`:

```markdown
# Sistema de monitoreo — Tornalyx (Entrega 2)

Stack: **Prometheus** (recolección de métricas) + **node_exporter**
(métricas de SO) + **mysqld_exporter** (métricas de MySQL) + **Grafana**
(visualización), en vez de Zabbix (alternativa que da el enunciado) por ser
más liviano para un único VPS y porque separa claramente colección
(Prometheus) de visualización (Grafana), útil para mostrar en la entrega.

## Qué se monitorea

| Exporter | Puerto | Métricas | Usuario/permiso |
|----------|--------|----------|-------------------|
| node_exporter | 9100 | CPU, memoria, disco, red, carga del sistema | No requiere credenciales de BD (lee `/proc` del propio host). |
| mysqld_exporter | 9104 | Conexiones activas, queries lentas, tamaño de tablas, replicación (si la hubiera) | `tornalyx_monitor` (`SGDM/backend/database/dcl.sql:95-99`): solo `SELECT` + `PROCESS`, no puede modificar datos. |

Prometheus scrapea ambos exporters cada 15s (`scripts/servidor/prometheus.yml`)
y Grafana los consulta para armar dashboards.

## Por qué reusar `tornalyx_monitor`

El usuario ya existe en `dcl.sql` con exactamente los permisos de
solo-lectura que necesita `mysqld_exporter` — evita crear un usuario nuevo
con permisos redundantes y mantiene la separación de funciones que ya
documenta ese archivo.

## Acceso

Grafana queda en el puerto 3000, expuesto **solo a la IP del admin** por
`scripts/servidor/firewall-ufw.sh` (Bloque A) — no es un dashboard público.

## Aplicación en el servidor real

No se ejecutó contra un servidor en vivo (no hay VPS provisionado
actualmente). Pasos para aplicarlo:

```bash
scp -r scripts/servidor admin@<ip-del-vps>:/tmp/tornalyx-monitoreo
ssh admin@<ip-del-vps>
sudo DB_MONITOR_PASS='<contraseña real de tornalyx_monitor>' \
    /tmp/tornalyx-monitoreo/instalar-monitoreo.sh
```

Después de instalar, crear en Grafana un datasource Prometheus apuntando a
`http://localhost:9090` e importar (o armar) un dashboard con paneles de:
uso de CPU/memoria/disco (node_exporter) y conexiones activas / queries
lentas de MySQL (mysqld_exporter). Este paso final de armado visual del
dashboard se hace a mano en la UI de Grafana, no es scripteable de forma
útil.
```

- [ ] **Step 5: Marcar como ejecutable y commit**

```bash
chmod +x scripts/servidor/instalar-monitoreo.sh
git add scripts/servidor/instalar-monitoreo.sh scripts/servidor/prometheus.yml docs/entrega2/monitoreo.md
git commit -m "feat: agregar stack de monitoreo (Prometheus + Grafana)"
```

---

### Task 5: Checklist final del Bloque B

**Files:**
- Create: `docs/entrega2/checklist-bloque-b.md`

**Interfaces:**
- Consumes: los documentos y scripts de Tasks 1-4.
- Produces: checklist final, mismo formato que `docs/entrega2/checklist-bloque-a.md`.

- [ ] **Step 1: Confirmar que todos los archivos existen**

```bash
test -f docs/entrega2/politica-backups.md && echo OK1
test -f scripts/servidor/respaldo.sh && echo OK2
test -f scripts/servidor/backup.env.example && echo OK3
test -f scripts/servidor/cron-tornalyx && echo OK4
test -f scripts/servidor/instalar-cron-backup.sh && echo OK5
test -f scripts/servidor/instalar-monitoreo.sh && echo OK6
test -f scripts/servidor/prometheus.yml && echo OK7
test -f docs/entrega2/monitoreo.md && echo OK8
```

Expected: `OK1` a `OK8`.

- [ ] **Step 2: Escribir el checklist**

Contenido de `docs/entrega2/checklist-bloque-b.md`:

```markdown
# Bloque B — Checklist de evidencia (Entrega 2, 22 pts)

| # | Criterio | Estado | Evidencia |
|---|----------|--------|-----------|
| 1 | Política de respaldos: tipos de respaldo | ✅ | `docs/entrega2/politica-backups.md` (sección "Tipo de respaldo") |
| 2 | Política de respaldos: cronograma definido | ✅ | `docs/entrega2/politica-backups.md` (sección "Cronograma") |
| 3 | Script de respaldos y automatización (cron) implementado | ✅ | `scripts/servidor/respaldo.sh`, `scripts/servidor/cron-tornalyx`, `scripts/servidor/instalar-cron-backup.sh` |
| 4 | Sistema de monitoreo implementado en el servidor | ✅ | `scripts/servidor/instalar-monitoreo.sh`, `scripts/servidor/prometheus.yml`, `docs/entrega2/monitoreo.md` |

Nota: los scripts de este bloque están listos para aplicar pero no se
ejecutaron contra un VPS real (no hay uno provisionado al momento de esta
entrega). Cada documento incluye los pasos exactos para aplicarlos cuando
exista el servidor.
```

- [ ] **Step 3: Commit**

```bash
git add docs/entrega2/checklist-bloque-b.md
git commit -m "docs: agregar checklist de evidencia del bloque B de la entrega 2"
```
