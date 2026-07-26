# TAREA 6 – SISTEMAS OPERATIVOS
## Análisis de Usuarios del Sistema Operativo

---

## 1. Introducción

En un servidor Linux que aloja la aplicación Tornalyx, es fundamental definir los usuarios del sistema operativo con precisión. Esto aplica el principio de **mínimo privilegio**: cada usuario tiene únicamente los permisos estrictamente necesarios para su función. Un usuario con más permisos de los que necesita representa una vulnerabilidad de seguridad.

---

## 2. Usuarios del Sistema Operativo (AlmaLinux 8.10)

### 2.1 Usuario: `admin_tornalyx`
**Tipo:** Usuario sudoer (administrador del sistema)

| Atributo | Valor |
|----------|-------|
| **Nombre de usuario** | `admin_tornalyx` |
| **UID** | 1001 |
| **Grupo principal** | `admin_tornalyx` |
| **Grupos secundarios** | `wheel`, `apache` |
| **Shell** | `/bin/bash` |
| **Directorio home** | `/home/admin_tornalyx` |
| **Acceso SSH** | Sí (clave pública RSA/ED25519 únicamente) |
| **Contraseña** | Requerida (política fuerte) |

**Responsabilidades:**
- Instalar, actualizar y configurar paquetes del sistema (Apache, PHP, MySQL).
- Gestionar certificados SSL con Certbot.
- Configurar el firewall con `firewalld`.
- Ejecutar tareas de mantenimiento del servidor (reinicios, logs).
- Revisar y aplicar parches de seguridad.

**Permisos específicos:**
```bash
# Puede ejecutar cualquier comando como root mediante sudo
# Acceso: sudo -i o sudo <comando>
# Política: cada acción sudo queda registrada en /var/log/secure
```

**Restricciones:**
- No debe usar el usuario `root` directamente para las operaciones diarias.
- Las conexiones SSH se realizan solo con clave pública (no contraseña).
- Autenticación de dos factores (2FA) recomendada para SSH.

---

### 2.2 Usuario: `operador_web`
**Tipo:** Usuario sin privilegios elevados (operador de aplicación)

| Atributo | Valor |
|----------|-------|
| **Nombre de usuario** | `operador_web` |
| **UID** | 1002 |
| **Grupo principal** | `apache` |
| **Grupos secundarios** | ninguno |
| **Shell** | `/bin/bash` |
| **Directorio home** | `/home/operador_web` |
| **Acceso SSH** | Sí (acceso restringido al directorio web) |
| **Contraseña** | Requerida |

**Responsabilidades:**
- Actualizar los archivos de la aplicación Tornalyx en `/var/www/tornalyx/`.
- Revisar y rotar logs de la aplicación en `/var/log/tornalyx/`.
- Gestionar las migraciones de base de datos (acceso limitado a MySQL).
- Monitorear el estado del servicio Apache/PHP.
- Ejecutar scripts de despliegue (`deploy.sh`).

**Permisos específicos:**
```bash
# Propietario de los archivos de la aplicación
sudo chown -R operador_web:apache /var/www/tornalyx/
sudo chmod -R 750 /var/www/tornalyx/

# Permisos específicos de sudo (solo comandos necesarios)
# En /etc/sudoers.d/operador_web:
operador_web ALL=(ALL) NOPASSWD: /bin/systemctl restart httpd
operador_web ALL=(ALL) NOPASSWD: /bin/systemctl reload httpd
operador_web ALL=(ALL) NOPASSWD: /usr/bin/certbot renew
```

**Restricciones:**
- No tiene acceso a `sudo` general.
- No puede instalar paquetes del sistema (`dnf install`).
- No puede modificar configuraciones de firewall.
- Acceso a MySQL limitado a la base de datos `tornalyx_db` con usuario `tornalyx_user`.

---

### 2.3 Usuario: `dev_tornalyx`
**Tipo:** Usuario de desarrollo (acceso en entorno de desarrollo/staging)

| Atributo | Valor |
|----------|-------|
| **Nombre de usuario** | `dev_tornalyx` |
| **UID** | 1003 |
| **Grupo principal** | `dev_tornalyx` |
| **Grupos secundarios** | `apache` |
| **Shell** | `/bin/bash` |
| **Directorio home** | `/home/dev_tornalyx` |
| **Acceso SSH** | Sí (solo en servidor de desarrollo) |
| **Contraseña** | Requerida |

**Responsabilidades:**
- Desarrollar, probar y depurar el código fuente de Tornalyx.
- Ejecutar migraciones y seeders de base de datos en entorno de desarrollo.
- Crear y gestionar ramas del repositorio Git.
- Ejecutar pruebas unitarias y de integración.
- Generar documentación técnica.

**Permisos específicos:**
```bash
# Acceso completo al directorio de desarrollo
sudo chown -R dev_tornalyx:dev_tornalyx /home/dev_tornalyx/tornalyx/

# Acceso a Git en el servidor
# Acceso a MySQL de desarrollo (base de datos tornalyx_dev)
# Puede reiniciar servicios en servidor de desarrollo:
dev_tornalyx ALL=(ALL) NOPASSWD: /bin/systemctl restart httpd, /bin/systemctl restart mysqld
```

**Restricciones:**
- **Solo tiene acceso al servidor de desarrollo**, nunca al de producción directamente.
- Los cambios a producción se realizan mediante `operador_web` y un pipeline de CI/CD.
- No tiene acceso a la base de datos de producción.

---

### 2.4 Usuario: `backup_tornalyx`
**Tipo:** Usuario de sistema (sin shell interactiva, solo para scripts automáticos)

| Atributo | Valor |
|----------|-------|
| **Nombre de usuario** | `backup_tornalyx` |
| **UID** | 1004 |
| **Grupo principal** | `backup_tornalyx` |
| **Grupos secundarios** | ninguno |
| **Shell** | `/sbin/nologin` (no puede iniciar sesión interactiva) |
| **Directorio home** | `/var/backups/tornalyx/` |
| **Acceso SSH** | No |
| **Contraseña** | No (cuenta del sistema) |

**Responsabilidades:**
- Ejecutar automáticamente los scripts de copia de seguridad programados con `cron`.
- Comprimir y almacenar backups de la base de datos MySQL.
- Comprimir y almacenar backups de los archivos de la aplicación.
- Transferir copias de seguridad a almacenamiento externo (S3, SFTP).
- Verificar la integridad de los backups con checksums.

**Cron jobs programados:**
```bash
# /etc/cron.d/tornalyx-backup
# Backup diario de base de datos a las 02:00
0 2 * * * backup_tornalyx /opt/scripts/backup_db.sh >> /var/log/tornalyx/backup.log 2>&1

# Backup semanal de archivos los domingos a las 03:00
0 3 * * 0 backup_tornalyx /opt/scripts/backup_files.sh >> /var/log/tornalyx/backup.log 2>&1
```

**Permisos específicos:**
```bash
# Acceso de solo lectura a la aplicación
sudo chown backup_tornalyx:backup_tornalyx /var/backups/tornalyx/
sudo chmod 700 /var/backups/tornalyx/

# Permisos de sudo mínimos para mysqldump
backup_tornalyx ALL=(ALL) NOPASSWD: /usr/bin/mysqldump
```

**Restricciones:**
- Shell = `/sbin/nologin` → nadie puede iniciar sesión como este usuario.
- Solo puede escribir en `/var/backups/tornalyx/`.
- No tiene acceso de escritura a los archivos de la aplicación.
- En caso de compromiso, el atacante no puede modificar la aplicación.

---

## 3. Resumen de Permisos por Usuario

| Acción | admin_tornalyx | operador_web | dev_tornalyx | backup_tornalyx |
|--------|:--------------:|:------------:|:------------:|:---------------:|
| Instalar paquetes (dnf) | ✅ (sudo) | ❌ | ❌ | ❌ |
| Reiniciar servicios | ✅ | ✅ (limitado) | ✅ (dev) | ❌ |
| Modificar archivos web | ✅ | ✅ | ✅ | ❌ |
| Leer archivos web | ✅ | ✅ | ✅ | ✅ |
| Gestionar firewall (firewalld) | ✅ | ❌ | ❌ | ❌ |
| Acceso MySQL producción | ✅ | ✅ (limitado) | ❌ | ✅ (solo dump) |
| Acceso MySQL desarrollo | ✅ | ❌ | ✅ | ❌ |
| Ejecutar backups | ✅ | ❌ | ❌ | ✅ (automático) |
| Login SSH | ✅ | ✅ | ✅ | ❌ |
| Login interactivo | ✅ | ✅ | ✅ | ❌ |

---

## 4. Principios de Seguridad Aplicados

1. **Mínimo privilegio:** cada usuario tiene solo los permisos necesarios para su función.
2. **Separación de responsabilidades:** administración, operación, desarrollo y respaldo son roles distintos.
3. **No uso de root:** el usuario root no se usa directamente; se usa `sudo` con registro de auditoría.
4. **Cuenta de servicio sin shell:** `backup_tornalyx` no puede ser usado por atacantes para acceso interactivo.
5. **Aislamiento de entornos:** el desarrollador no tiene acceso a producción.
6. **Control de acceso obligatorio:** AlmaLinux trae SELinux en modo *enforcing*, que actúa como una segunda capa por encima de los permisos de usuarios y grupos. Aunque una cuenta lograra escribir dentro del directorio publicado, Apache no serviría ese archivo si no tiene el contexto `httpd_sys_content_t`.

> **Nota sobre la distribución.** Los nombres de grupos y servicios de este documento son los de la familia RHEL, que difieren de los de Debian y Ubuntu: el grupo de administradores es `wheel` (no `sudo`), Apache corre como el usuario `apache` (no `www-data`) y su servicio se llama `httpd` (no `apache2`), las acciones de `sudo` se registran en `/var/log/secure` (no en `/var/log/auth.log`), los paquetes se instalan con `dnf` (no con `apt`) y el firewall es `firewalld` (no `ufw`).
