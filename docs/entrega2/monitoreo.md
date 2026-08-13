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
