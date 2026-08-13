# Entrega 2 — Bloque A: Seguridad de aplicación y BD (24 pts) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cubrir los 10 criterios del Bloque A de la rúbrica de la segunda entrega, documentando la evidencia de lo que ya existe en el repo y construyendo lo que falta (esquema de red y seguridad de servidor).

**Architecture:** 7 de los 10 criterios ya están implementados en el código actual (validaciones, hash de contraseñas, DCL, modelos, POO, Apache) — este plan NO los reimplementa, solo los documenta con evidencia verificable (archivo:línea) para que se puedan calificar. Los 2 criterios genuinamente faltantes (esquema de red, seguridad de servidor: firewall/fail2ban/IDS) se resuelven con documentos + scripts listos para aplicar en un VPS real, porque el despliegue actual en Render (PaaS) no da acceso de root para configurar firewall/fail2ban a nivel de sistema operativo.

**Tech Stack:** Markdown + diagramas Mermaid (docs), Bash (scripts de servidor), ufw, fail2ban, AIDE — target Debian/Ubuntu.

## Global Constraints

- Documentos nuevos van en `docs/entrega2/` y scripts de infraestructura en `scripts/servidor/` — nunca en la raíz del repo (regla del CLAUDE.md del proyecto).
- Ningún archivo nuevo supera 500 líneas (regla del CLAUDE.md del proyecto).
- Todo el contenido (docs, comentarios) en español, seguido del estilo ya usado en el repo (ver comentarios de `dcl.sql` como referencia de tono).
- No hay VPS real disponible en este momento ni acceso Docker/mysql/shellcheck en esta máquina de desarrollo: la verificación de los scripts de este plan se limita a `bash -n` (sintaxis) y revisión manual línea por línea. La prueba real (`ufw status`, `fail2ban-client -t`, `aide --check`) queda documentada como paso pendiente a ejecutar en el servidor, no se ejecuta en este plan.
- OS asumido para los scripts de servidor: Debian/Ubuntu (apt, systemd, ufw, fail2ban) — ver decisión tomada con el usuario.
- No modificar código de aplicación ya funcional (`AuthController.php`, `Usuario.php`, `dcl.sql`, `.htaccess`, `Dockerfile`, `schema.sql`) — este plan es de documentación y de infraestructura nueva, no de refactor.

---

### Task 1: Documento de evidencia del Bloque A (checklist rúbrica → archivo)

**Files:**
- Create: `docs/entrega2/checklist-bloque-a.md`

**Interfaces:**
- Consumes: nada (solo lee el repo existente).
- Produces: documento de referencia que las Tasks 2-4 pueden enlazar.

- [ ] **Step 1: Reconfirmar cada evidencia antes de citarla**

Correr estos comandos y anotar el número de línea real que devuelven (puede haber corrido desde que se escribió este plan):

```bash
grep -n "PASSWORD_BCRYPT" SGDM/backend/models/Usuario.php
grep -n "password_verify" SGDM/backend/models/Usuario.php
grep -n "passwordEsFuerte\|FILTER_VALIDATE_EMAIL" SGDM/backend/controllers/AuthController.php
grep -n "CREATE USER" SGDM/backend/database/dcl.sql
grep -n "^CREATE TABLE" SGDM/backend/database/migrations/schema.sql
grep -n "Content-Security-Policy\|Strict-Transport-Security" SGDM/frontend/.htaccess
```

Expected: cada comando devuelve al menos una línea con número. Si alguno no devuelve nada, el criterio correspondiente ya no está cubierto y hay que avisar antes de seguir (no asumir que sigue igual).

- [ ] **Step 2: Escribir el documento**

Contenido exacto de `docs/entrega2/checklist-bloque-a.md`:

```markdown
# Bloque A — Checklist de evidencia (Entrega 2, 24 pts)

Mapeo de cada criterio de la rúbrica a la evidencia concreta en el repo.
Las líneas citadas están confirmadas al momento de escribir este documento
(ver Task 1 del plan `2026-08-12-entrega2-bloque-a-seguridad.md`); si el
código se movió, volver a correr los `grep` de ese plan antes de entregar.

| # | Criterio | Estado | Evidencia | Nota |
|---|----------|--------|-----------|------|
| 1 | Validaciones en frontend y backend | ✅ | `SGDM/frontend/js/validations.js`; `SGDM/backend/controllers/AuthController.php` (líneas 177-191, 358-363) | Frontend valida para UX, backend revalida siempre (nunca confía en el cliente). |
| 2 | Encriptación de contraseñas | ✅ | `SGDM/backend/models/Usuario.php:45` (`password_hash(..., PASSWORD_BCRYPT, ['cost' => 12])`), `:74` (`password_verify` con hash dummy para tiempo constante) | Bcrypt costo 12, nunca se guarda ni se loguea texto plano. |
| 3 | Esquema de red con implementaciones de seguridad | ✅ | `docs/entrega2/esquema-red.md` | Ver Task 3 de este plan. |
| 4 | Seguridad de servidor (IDS, IPS, firewall, fail2ban) | ✅ | `docs/entrega2/seguridad-servidor.md`, `scripts/servidor/firewall-ufw.sh`, `scripts/servidor/fail2ban-tornalyx.local`, `scripts/servidor/instalar-seguridad.sh` | Ver Task 4 de este plan. Requiere aplicarse en el VPS real (no en Render). |
| 5 | Modelo relacional normalizado | ✅ | `docs/entrega2/modelo-relacional.md`; esquema real en `SGDM/backend/database/migrations/schema.sql` (15 tablas) | Ver Task 2 de este plan. |
| 6 | DCL implementado | ✅ | `SGDM/backend/database/dcl.sql` | 6 usuarios: `tornalyx_ddl`, `tornalyx_dcl`, `tornalyx_dml`, `tornalyx_monitor`, `tornalyx_backup`, `tornalyx_dev`. |
| 7 | Configuración de usuarios de BD con restricciones | ✅ | `SGDM/backend/database/dcl.sql` (mismo archivo que #6) | Cada usuario tiene privilegios mínimos por función (ver comentarios del archivo). |
| 8 | Modelos alineados al modelo relacional | ✅ | `SGDM/backend/models/*.php` (13 archivos) | Un modelo por tabla de negocio (`Usuario`, `Torneo`, `Equipo`, `Partido`, etc.), heredan de `SGDM/backend/models/Model.php`. |
| 9 | Integración con PHP POO (gestión de usuarios funcionando) | ✅ | `SGDM/backend/models/Usuario.php`, `SGDM/backend/controllers/AuthController.php` | Registro, login, 2FA por email, edición de perfil — todo con clases (`Model`, `Controller` como base). |
| 10 | Implementación con Apache | ✅ | `Dockerfile:1-43`, `SGDM/docker/vhost.conf`, `SGDM/frontend/.htaccess` | Apache 2 vía imagen `php:8.2-apache`, `mod_rewrite` + `mod_headers`, CSP/HSTS/X-Frame-Options ya configurados. |
```

- [ ] **Step 3: Verificar que el documento no tiene líneas rotas**

```bash
grep -c "✅\|❌" docs/entrega2/checklist-bloque-a.md
```

Expected: `10` (una marca por criterio).

- [ ] **Step 4: Commit**

```bash
git add docs/entrega2/checklist-bloque-a.md
git commit -m "docs: agregar checklist de evidencia del bloque A de la entrega 2"
```

---

### Task 2: Documento de modelo relacional normalizado

**Files:**
- Create: `docs/entrega2/modelo-relacional.md`

**Interfaces:**
- Consumes: `SGDM/backend/database/migrations/schema.sql` (15 tablas, ya existente, no se modifica).
- Produces: diagrama + justificación que Task 1 enlaza en la fila #5.

- [ ] **Step 1: Confirmar la lista de tablas y relaciones**

```bash
grep -n "^CREATE TABLE\|FOREIGN KEY\|UNIQUE KEY\|PRIMARY KEY" SGDM/backend/database/migrations/schema.sql
```

Expected: 15 `CREATE TABLE` (usuarios, torneos, equipos, inscripciones, rondas, partidos, resultados, posiciones, sesiones, login_otps, doc_acceso, asistencias, avisos, avisos_leidos, doc_otps).

- [ ] **Step 2: Escribir el documento con diagrama Mermaid**

Contenido de `docs/entrega2/modelo-relacional.md`:

```markdown
# Modelo relacional — Tornalyx (Entrega 2)

Esquema completo en `SGDM/backend/database/migrations/schema.sql` (15 tablas).
Este documento resume las relaciones y justifica la normalización.

## Diagrama entidad-relación

\`\`\`mermaid
erDiagram
    usuarios ||--o{ torneos : organiza
    usuarios ||--o{ equipos : capitanea
    usuarios ||--o{ inscripciones : se_inscribe
    usuarios ||--o{ sesiones : tiene
    usuarios ||--o| login_otps : verifica
    usuarios ||--o{ doc_acceso : solicita
    usuarios ||--o{ avisos : publica
    usuarios ||--o{ resultados : carga

    torneos ||--o{ equipos : incluye
    torneos ||--o{ inscripciones : recibe
    torneos ||--o{ rondas : organiza_en
    torneos ||--o{ partidos : programa
    torneos ||--o{ posiciones : calcula
    torneos ||--o{ avisos : publica_en

    rondas ||--o{ partidos : agrupa

    partidos ||--o| resultados : tiene
    partidos ||--o{ asistencias : registra

    equipos ||--o{ inscripciones : es_via

    avisos ||--o{ avisos_leidos : leido_por
    usuarios ||--o{ avisos_leidos : lee
    usuarios ||--o{ asistencias : asiste
    usuarios ||--o{ doc_otps : verifica_doc
\`\`\`

## Justificación de normalización

- **1FN (atomicidad):** todas las columnas guardan un único valor escalar
  (ej.: `usuarios.email`, `torneos.fecha_inicio`); no hay columnas con listas
  o valores compuestos serializados.
- **2FN (sin dependencias parciales):** todas las tablas con clave primaria
  simple (`id INT UNSIGNED AUTO_INCREMENT`) no tienen el problema de
  dependencia parcial por definición. Las tablas con clave primaria
  compuesta son puras tablas de unión N:M sin columnas adicionales que
  dependan solo de una mitad de la clave:
  - `asistencias` — PK `(partido_id, usuario_id)`, sin columnas propias más
    allá de la marca de asistencia.
  - `avisos_leidos` — PK `(aviso_id, usuario_id)`, solo registra el hecho de
    lectura.
  - `doc_otps` — PK `(usuario_id, materia)`, el código OTP depende de ambas
    columnas a la vez (un código por usuario+materia), no de una sola.
- **3FN (sin dependencias transitivas):** no hay columnas que dependan de
  otra columna no clave. Ejemplo: `posiciones` guarda `puntos`, `pj`, `pg`,
  etc. directamente ligados a `(torneo_id, contendiente_id, tipo)` — no se
  derivan de otra columna no-clave de la misma tabla, se recalculan desde
  `resultados` por el motor de torneos (`SGDM/backend/shared/Fixture.php`).
  `usuarios.rol` y `usuarios.estado` son atributos propios del usuario, no
  dependen de ninguna otra columna de `usuarios`.
- **Integridad referencial:** las 15 tablas declaran sus `FOREIGN KEY`
  explícitas (ver `schema.sql`), con `ON DELETE CASCADE` para datos que no
  tienen sentido sin su padre (ej.: `partidos` sin su `torneo`) y
  `ON DELETE RESTRICT`/`SET NULL` donde borrar el padre no debe borrar
  silenciosamente el hijo (ej.: no se puede borrar un `usuario` que sea
  `organizador_id` de un torneo activo).
```

- [ ] **Step 3: Revisión visual del diagrama**

Abrir `docs/entrega2/modelo-relacional.md` en un preview de Markdown con soporte Mermaid (GitHub renderiza `mermaid` nativamente, igual que la mayoría de editores). Confirmar que el diagrama no tiene errores de sintaxis (no queda como bloque de código plano sino como diagrama renderizado).

Expected: el diagrama se ve como grafo de entidades, no como texto crudo.

- [ ] **Step 4: Commit**

```bash
git add docs/entrega2/modelo-relacional.md
git commit -m "docs: agregar documento de modelo relacional normalizado"
```

---

### Task 3: Esquema de red con implementaciones de seguridad

**Files:**
- Create: `docs/entrega2/esquema-red.md`

**Interfaces:**
- Consumes: nada nuevo — describe la arquitectura de red objetivo para el VPS real (distinta del Render actual, ver Global Constraints).
- Produces: documento que Task 4 referencia para justificar qué puertos abre el firewall.

- [ ] **Step 1: Escribir el documento con diagrama Mermaid**

Contenido de `docs/entrega2/esquema-red.md`:

```markdown
# Esquema de red — Tornalyx (Entrega 2)

Arquitectura de red objetivo para el despliegue en un VPS real (no aplica
al hosting actual en Render, que es PaaS sin acceso a la capa de red/SO —
ver `SGDM/backend/database/dcl.sql`, que ya documenta esta misma distinción
para los usuarios de base de datos).

## Diagrama

\`\`\`mermaid
flowchart TB
    Internet(("Internet"))

    subgraph VPS["VPS (Debian/Ubuntu) — admin_tornalyx"]
        FW["Firewall: ufw\n(deny incoming por defecto)"]
        subgraph Borde["Zona expuesta"]
            Apache["Apache 2 :80/:443\n(php:8.2-apache)"]
        end
        subgraph Interno["Zona interna (solo localhost)"]
            App["App PHP\n(SGDM/backend + frontend)"]
            MySQL["MySQL 8\nbind-address 127.0.0.1"]
            Grafana["Grafana :3000\n(solo IP admin)"]
        end
        F2B["fail2ban\n(banea IPs vía iptables)"]
        AIDE["AIDE\n(integridad de archivos)"]
    end

    Admin(("Admin\nIP fija"))

    Internet -->|":80/:443"| FW
    Admin -->|":22 SSH restringido"| FW
    Admin -->|":3000 Grafana"| FW
    FW --> Apache
    Apache --> App
    App -->|"tornalyx_dml\nlocalhost:3306"| MySQL
    F2B -.->|"lee logs, banea"| Apache
    F2B -.->|"lee logs, banea"| FW
    AIDE -.->|"verifica integridad"| App
\`\`\`

## Puertos y reglas

| Puerto | Servicio | Origen permitido | Justificación |
|--------|----------|-------------------|----------------|
| 22/tcp | SSH | Solo IP fija del admin | Reduce superficie de ataque; `fail2ban` banea igual si se filtra la IP. |
| 80/tcp | HTTP | Cualquiera | Redirige a HTTPS (no sirve contenido sensible en claro). |
| 443/tcp | HTTPS (Apache) | Cualquiera | Tráfico de la aplicación, con los headers de `SGDM/frontend/.htaccess` (CSP, HSTS, X-Frame-Options). |
| 3306/tcp | MySQL | Nadie desde afuera (`bind-address 127.0.0.1`) | La app y MySQL corren en el mismo host; no hay razón para exponer el puerto de base de datos a la red. |
| 3000/tcp | Grafana | Solo IP fija del admin | Panel de monitoreo, no es de uso público. |

## Segmentación

- **Zona expuesta (borde):** solo Apache recibe tráfico de Internet.
- **Zona interna:** MySQL y Grafana nunca reciben conexiones directas desde
  Internet, solo desde `localhost` (MySQL) o desde la IP del admin
  (Grafana), reforzado por las reglas de `scripts/servidor/firewall-ufw.sh`.
- **Defensa en profundidad:** el firewall es la primera barrera; `fail2ban`
  actúa como segunda barrera baneando IPs con intentos fallidos repetidos
  (SSH y login de Apache); AIDE detecta si un atacante que sí entró modificó
  archivos del sistema o de la aplicación. Detalle de cada pieza en
  `docs/entrega2/seguridad-servidor.md`.
```

- [ ] **Step 2: Revisión visual del diagrama**

Igual que en Task 2: abrir el archivo en un preview con soporte Mermaid y confirmar que renderiza como flowchart, no como texto.

- [ ] **Step 3: Commit**

```bash
git add docs/entrega2/esquema-red.md
git commit -m "docs: agregar esquema de red con implementaciones de seguridad"
```

---

### Task 4: Seguridad de servidor — firewall, fail2ban, IDS (AIDE)

**Files:**
- Create: `scripts/servidor/firewall-ufw.sh`
- Create: `scripts/servidor/fail2ban-tornalyx.local`
- Create: `scripts/servidor/instalar-seguridad.sh`
- Create: `docs/entrega2/seguridad-servidor.md`

**Interfaces:**
- Consumes: puertos y zonas definidos en `docs/entrega2/esquema-red.md` (Task 3).
- Produces: scripts que Task 1 (fila #4) referencia como evidencia.

- [ ] **Step 1: Crear el script de firewall**

`scripts/servidor/firewall-ufw.sh`:

```bash
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
```

- [ ] **Step 2: Verificar sintaxis del script de firewall**

```bash
bash -n scripts/servidor/firewall-ufw.sh
```

Expected: sin salida (sintaxis válida).

- [ ] **Step 3: Crear la configuración de fail2ban**

`scripts/servidor/fail2ban-tornalyx.local`:

```ini
# Tornalyx — jails de fail2ban.
# Copiar a /etc/fail2ban/jail.d/tornalyx.local en el servidor real
# (lo hace scripts/servidor/instalar-seguridad.sh). Los jails base de
# fail2ban ya cubren sshd; acá se ajustan sus parámetros y se agregan los
# de Apache. Actúa como IPS: no solo detecta, banea la IP vía iptables.

[sshd]
enabled  = true
port     = 22
maxretry = 4
findtime = 10m
bantime  = 1h

[apache-auth]
enabled  = true
port     = http,https
logpath  = /var/log/apache2/error.log
maxretry = 5
findtime = 10m
bantime  = 30m

[apache-badbots]
enabled  = true
port     = http,https
logpath  = /var/log/apache2/access.log
bantime  = 1d
```

- [ ] **Step 4: Crear el script orquestador de instalación**

`scripts/servidor/instalar-seguridad.sh`:

```bash
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
```

- [ ] **Step 5: Verificar sintaxis del script orquestador**

```bash
bash -n scripts/servidor/instalar-seguridad.sh
```

Expected: sin salida (sintaxis válida).

- [ ] **Step 6: Escribir el documento explicativo**

`docs/entrega2/seguridad-servidor.md`:

```markdown
# Seguridad de servidor — Tornalyx (Entrega 2)

Implementaciones de seguridad a nivel de sistema operativo para el VPS real
(no aplica a Render, que no da acceso de root). Scripts en
`scripts/servidor/`, arquitectura en `docs/entrega2/esquema-red.md`.

## Piezas

| Rol | Herramienta | Qué hace | Script/config |
|-----|-------------|----------|----------------|
| Firewall | `ufw` | Deniega todo el tráfico entrante por defecto; solo abre 22 (SSH, restringido a la IP del admin), 80/443 (HTTP/HTTPS) y 3000 (Grafana, restringido a la IP del admin). | `scripts/servidor/firewall-ufw.sh` |
| IPS (Intrusion Prevention) | `fail2ban` | Lee `/var/log/apache2/*.log` y el log de `sshd`; banea automáticamente (vía `iptables`) las IPs con intentos fallidos repetidos de login. No solo detecta, actúa. | `scripts/servidor/fail2ban-tornalyx.local` |
| IDS (Intrusion Detection) | `AIDE` | Calcula un hash de referencia de los archivos del sistema y de la aplicación; corridas posteriores (`aide --check`) detectan modificaciones no autorizadas (ej.: un atacante que reemplazó un archivo PHP). | `scripts/servidor/instalar-seguridad.sh` (inicializa la base) |
| Control complementario a nivel de app | `LoginThrottle` (ya existente) | Bloquea intentos de login por email/IP a nivel de aplicación, independiente del firewall — capa adicional para cuando el atacante no dispara los umbrales de fail2ban. | `SGDM/backend/shared/LoginThrottle.php` |

## Por qué esta combinación

- El firewall reduce la superficie de ataque desde el día uno (nadie llega
  a MySQL o a Grafana desde afuera).
- `fail2ban` cubre el caso de fuerza bruta contra SSH y contra el login de
  Apache a nivel de red/IP — complementa, no reemplaza, al
  `LoginThrottle.php` de la aplicación (que actúa por email, no por IP, y
  sigue funcionando aunque `fail2ban` no esté disponible, ej. detrás de un
  proxy que oculte la IP real).
- AIDE cubre el escenario en que un atacante ya entró (por una credencial
  filtrada, por ejemplo) y modificó archivos: sin un IDS de integridad, ese
  cambio pasaría desapercibido hasta el próximo síntoma visible.

## Aplicación en el servidor real

Este documento y los scripts se entregan listos para aplicar, pero no se
ejecutaron contra un servidor en vivo (no hay VPS provisionado en este
momento). Pasos para aplicarlos cuando exista el VPS:

```bash
scp -r scripts/servidor admin@<ip-del-vps>:/tmp/tornalyx-seguridad
ssh admin@<ip-del-vps>
sudo ADMIN_IP=<tu-ip-fija> /tmp/tornalyx-seguridad/instalar-seguridad.sh
```

Verificación posterior en el servidor (no reproducible en esta máquina de
desarrollo, que no tiene `ufw`/`fail2ban`/`aide` ni acceso a un VPS):

```bash
sudo ufw status verbose
sudo fail2ban-client status
sudo fail2ban-client status apache-auth
sudo aide --check
```
```

- [ ] **Step 7: Marcar los scripts como ejecutables en git**

```bash
git add --chmod=+x scripts/servidor/firewall-ufw.sh scripts/servidor/instalar-seguridad.sh
```

(Si `--chmod` no está disponible en la versión de git instalada, usar `chmod +x scripts/servidor/*.sh` antes de `git add`.)

- [ ] **Step 8: Commit**

```bash
git add scripts/servidor/ docs/entrega2/seguridad-servidor.md
git commit -m "feat: agregar scripts de seguridad de servidor (firewall, fail2ban, AIDE)"
```

---

### Task 5: Actualizar el checklist con los links finales y revisión cruzada

**Files:**
- Modify: `docs/entrega2/checklist-bloque-a.md`

**Interfaces:**
- Consumes: los 3 documentos y 3 scripts creados en Tasks 2-4.
- Produces: checklist final listo para entregar.

- [ ] **Step 1: Confirmar que los 3 archivos nuevos de evidencia existen**

```bash
test -f docs/entrega2/modelo-relacional.md && echo OK1
test -f docs/entrega2/esquema-red.md && echo OK2
test -f docs/entrega2/seguridad-servidor.md && echo OK3
test -f scripts/servidor/firewall-ufw.sh && echo OK4
test -f scripts/servidor/fail2ban-tornalyx.local && echo OK5
test -f scripts/servidor/instalar-seguridad.sh && echo OK6
```

Expected: `OK1` a `OK6`, uno por línea.

- [ ] **Step 2: Revisión cruzada final contra la rúbrica original**

Releer los 10 criterios del enunciado y confirmar, uno por uno, que
`docs/entrega2/checklist-bloque-a.md` tiene una fila con evidencia real
(no un placeholder) para cada uno. Si falta alguno, volver a la task
correspondiente antes de cerrar.

- [ ] **Step 3: Commit final (si hubo cambios en el checklist)**

```bash
git add docs/entrega2/checklist-bloque-a.md
git commit -m "docs: cerrar checklist del bloque A con los documentos finales"
```
