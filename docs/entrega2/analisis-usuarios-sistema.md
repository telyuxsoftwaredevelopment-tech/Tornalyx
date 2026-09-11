# Análisis de usuarios del sistema — Tornalyx (Entrega 2)

Quién corre cada proceso en el VPS real y con qué credencial de base de datos
lo hace. Es la contraparte a nivel de sistema operativo de
`SGDM/backend/database/dcl.sql`, que define los usuarios de MySQL. No aplica a
Render, que no da acceso de root ni cuentas de sistema propias.

## Principio

Cada proceso tiene **su propia identidad de sistema y su propia credencial de
base de datos**, con el mínimo privilegio que necesita para su función.
Comprometer un proceso no entrega los permisos de los demás: quien logre
ejecutar código como `www-data` obtiene `tornalyx_dml` (CRUD sobre los datos,
sin poder alterar el esquema ni crear usuarios), no las credenciales de
despliegue ni las de respaldo.

## Usuarios de sistema y su credencial de BD

| Usuario de sistema | Para qué | Shell / login | Usuario de MySQL que usa | Dónde vive la contraseña |
|---|---|---|---|---|
| `admin_tornalyx` | Administración del VPS: `sudo`, firewall, fail2ban, despliegues. Única cuenta con SSH (por clave, ver `esquema-red.md`). | Bash, SSH con clave, sin login por contraseña | `tornalyx_dcl` (alta y ajuste de permisos) y `tornalyx_ddl` (vía `desplegar.sh`) | En ningún archivo: se exporta a mano en la sesión, solo mientras dura la tarea |
| `www-data` | Corre Apache y la aplicación PHP (`/opt/tornalyx`). | Sin shell (`/usr/sbin/nologin`) | `tornalyx_dml` — solo `SELECT/INSERT/UPDATE/DELETE` | `.env` de la app, `chmod 600`, propietario `root:www-data` |
| `backup_tornalyx` | Respaldo diario (`respaldo.sh` vía cron). | Sin shell interactivo | `tornalyx_backup` — solo lectura + `LOCK TABLES` | `/etc/tornalyx/backup.env`, `chmod 600` (ver `backup.env.example`) |
| `monitor_tornalyx` | `mysqld_exporter` / `node_exporter` para Prometheus (ver `monitoreo.md`). | Sin shell interactivo, servicio systemd | `tornalyx_monitor` — `SELECT` + `PROCESS` | Unidad systemd con `EnvironmentFile` en `chmod 600` |
| `dev_tornalyx` | Acceso a MySQL de desarrollo: iterar esquema, probar migraciones y seeders. | Bash, SSH con clave | `tornalyx_dev` — `ALL` pero **solo sobre la base `tornalyx_dev`** | `.my.cnf` del propio usuario, `chmod 600` |

## Correspondencia con el DCL

La separación de arriba es la misma que la de `dcl.sql`, vista desde el otro
lado: el DCL limita **qué puede hacer** cada credencial dentro de MySQL, y este
documento define **quién la tiene**. Los dos controles son necesarios — de nada
sirve un `tornalyx_dml` sin DDL si su contraseña está en un archivo que puede
leer cualquier usuario del sistema.

Dos consecuencias concretas del diseño:

- **La app no despliega.** `tornalyx_ddl` no está en ningún `.env` ni en ningún
  servicio: solo existe en la sesión de `admin_tornalyx` cuando corre
  `scripts/servidor/desplegar.sh`. Por eso el `.env` de la aplicación lleva
  `DB_AUTO_MIGRATE=0`: sin eso, la app intentaría aplicar migraciones con
  `tornalyx_dml`, que por diseño no puede.
- **Desarrollo no toca producción.** `dev_tornalyx` solo tiene privilegios
  sobre la base `tornalyx_dev`; ni siquiera puede hacer `SELECT` sobre
  `tornalyx_db`.

## Estado actual vs. objetivo

Honestidad sobre lo implementado: hoy el cron de respaldo corre **como
`root`**, no como `backup_tornalyx` (ver el `TODO` en
`scripts/servidor/cron-tornalyx`). La credencial de MySQL que usa ya es la de
mínimo privilegio (`tornalyx_backup`), así que el respaldo no puede escribir en
la base; lo que falta es bajar el privilegio del **proceso** que lo lanza.
Cambiarlo requiere ajustar el dueño de `/var/backups/tornalyx` en
`instalar-cron-backup.sh` antes de tocar el cron.

## Verificación

```bash
# Qué usuario corre Apache y con qué shell quedó cada cuenta
ps -o user= -C apache2 | sort -u
getent passwd www-data backup_tornalyx monitor_tornalyx dev_tornalyx

# Que las credenciales no sean legibles por cualquiera
stat -c '%a %U:%G %n' /opt/tornalyx/.env /etc/tornalyx/backup.env

# Que cada usuario de BD tenga solo lo suyo
mysql -u root -p -e "SHOW GRANTS FOR 'tornalyx_dml'@'localhost'"
```
