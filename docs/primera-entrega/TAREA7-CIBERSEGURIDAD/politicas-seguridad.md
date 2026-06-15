# TAREA 7 – CIBERSEGURIDAD
## Políticas de Seguridad del Sistema Tornalyx

---

## Política 1: Política de Seguridad para Usuarios

**Versión:** 1.0 | **Fecha:** Junio 2026 | **Estado:** Vigente

### 1.1 Alcance
Aplica a todos los usuarios registrados en la plataforma Tornalyx: participantes, organizadores y administradores.

### 1.2 Responsabilidades del Usuario

**El usuario ES RESPONSABLE de:**
- Mantener la confidencialidad de sus credenciales de acceso. Ningún compañero, organizador ni personal de soporte debe conocer su contraseña.
- Utilizar una contraseña única para Tornalyx (no reutilizar contraseñas de otras plataformas).
- Cerrar su sesión al terminar de usar el sistema, especialmente en dispositivos compartidos o públicos.
- Notificar inmediatamente al administrador si sospecha que su cuenta fue comprometida (acceso no autorizado, cambios que no realizó).
- Mantener actualizado su email de recuperación para poder restablecer su contraseña si la olvida.

**El usuario NO DEBE:**
- Compartir sus credenciales de acceso con ninguna persona bajo ninguna circunstancia.
- Intentar acceder a cuentas de otros usuarios o a áreas del sistema para las que no tiene autorización.
- Usar la plataforma para actividades no relacionadas con la gestión de torneos deportivos.
- Intentar explotar, modificar o interrumpir el funcionamiento del sistema.
- Publicar o compartir información confidencial de otros participantes obtenida a través del sistema.

### 1.3 Uso aceptable del sistema
Tornalyx es una plataforma deportiva. El uso está restringido a:
- Gestión y participación en torneos deportivos, mentales y electrónicos.
- Consulta de resultados y estadísticas.
- Comunicación relacionada con los torneos.

### 1.4 Consecuencias del incumplimiento
- Primer incidente: advertencia formal por email.
- Segundo incidente: suspensión temporal de la cuenta (7 días).
- Tercer incidente: cancelación permanente de la cuenta.
- Incidente grave (intento de hackeo, fraude): cancelación inmediata y posible reporte a autoridades.

---

## Política 2: Política de Seguridad para Administradores

**Versión:** 1.0 | **Fecha:** Junio 2026 | **Estado:** Vigente

### 2.1 Alcance
Aplica a todos los usuarios con rol de administrador del sistema Tornalyx, incluyendo los usuarios del sistema operativo del servidor con privilegios elevados.

### 2.2 Requisitos obligatorios para administradores

**Autenticación:**
- Contraseña de mínimo 16 caracteres con todos los requisitos de complejidad.
- Autenticación de dos factores (2FA) mediante aplicación TOTP (Google Authenticator, Authy) obligatoria en todos los accesos al panel de administración y al servidor SSH.
- Cambio de contraseña obligatorio cada 90 días.

**Acceso al servidor:**
- El acceso SSH se realiza exclusivamente mediante clave pública RSA/ED25519 (4096 bits mínimo). Las claves privadas deben estar protegidas con passphrase.
- Nunca se usa el usuario `root` directamente. Toda operación privilegiada se realiza con `sudo`, quedando registrada en `/var/log/auth.log`.
- El acceso SSH desde redes públicas (Wi-Fi de cafetería, universidad) debe realizarse a través de VPN.
- Al finalizar cada sesión de trabajo en el servidor, se debe ejecutar `exit` para cerrar la sesión SSH.

**Principio de mínimo privilegio:**
- Cada administrador tiene acceso solo a las funciones necesarias para su rol específico.
- Las acciones destructivas (eliminar torneos, eliminar usuarios, modificar configuración del sistema) requieren confirmación explícita y quedan registradas en el log de auditoría.

### 2.3 Gestión de accesos
- Los accesos de administrador son nominales: un usuario = un acceso. No se comparten cuentas de administrador.
- Cuando un administrador deja el proyecto, su cuenta debe ser desactivada dentro de las 24 horas.
- Se realiza una revisión trimestral de los accesos activos de administrador para verificar que todos son necesarios.

### 2.4 Manejo de incidentes
El administrador que detecte un incidente de seguridad debe:
1. Documentar inmediatamente: fecha, hora, qué se observó, qué acciones se tomaron.
2. Notificar al equipo de seguridad del proyecto en las primeras 2 horas.
3. Si el incidente implica datos de usuarios: notificar según la legislación vigente de Uruguay (Ley 18.331 de Protección de Datos Personales).
4. No intentar "tapar" el incidente: la transparencia y la documentación son obligatorias.

---

## Política 3: Política de Copias de Seguridad (Backup)

**Versión:** 1.0 | **Fecha:** Junio 2026 | **Estado:** Vigente

### 3.1 Frecuencia de respaldos

| Tipo de backup | Frecuencia | Hora programada | Retención |
|---------------|------------|-----------------|-----------|
| Base de datos (completo) | Diaria | 02:00 AM | 30 días |
| Base de datos (incremental) | Cada 6 horas | 08:00 / 14:00 / 20:00 | 7 días |
| Archivos de la aplicación | Semanal (domingo) | 03:00 AM | 12 semanas (3 meses) |
| Configuración del servidor | Semanal | 03:30 AM | 12 semanas |
| Backup completo (todo) | Mensual (1er domingo) | 04:00 AM | 12 meses (1 año) |

### 3.2 Almacenamiento de backups

**Regla 3-2-1 (estándar de la industria):**
- **3 copias** del backup: original en el servidor, copia local, copia remota.
- **2 tipos de medios** distintos: disco del servidor + almacenamiento en la nube.
- **1 copia off-site** (fuera del servidor): almacenamiento S3 o servicio similar.

### 3.3 Verificación de integridad
```bash
# Script de verificación ejecutado después de cada backup
sha256sum /var/backups/tornalyx/backup_$(date +%Y%m%d).sql.gz > \
    /var/backups/tornalyx/checksums.txt

# Verificar que el backup es restaurable (prueba mensual)
mysqldump --no-data tornalyx_db | gzip > /tmp/test_backup.sql.gz
```

**Prueba de restauración:** El primer domingo de cada mes se realiza una restauración de prueba en el servidor de desarrollo para verificar que los backups son funcionales. Se documenta el resultado.

### 3.4 Cifrado de backups
Todos los backups se cifran con GPG antes de transferirse al almacenamiento externo:
```bash
gpg --symmetric --cipher-algo AES256 backup.sql.gz
```

---

## Política 4: Política de Acceso Remoto

**Versión:** 1.0 | **Fecha:** Junio 2026 | **Estado:** Vigente

### 4.1 Métodos de acceso remoto permitidos

| Método | Quién puede usarlo | Condiciones |
|--------|-------------------|-------------|
| SSH con clave pública | Administradores del servidor | Solo desde IPs conocidas o con VPN |
| SFTP (transferencia de archivos) | Operadores web | Solo para despliegues, no navegación libre |
| Panel web de administración (HTTPS) | Admins de la aplicación | Solo con 2FA activo |

**Métodos PROHIBIDOS:**
- FTP (sin cifrado).
- Telnet.
- Acceso SSH con contraseña (deshabilitado en la configuración).
- RDP o VNC sin túnel cifrado.

### 4.2 Gestión de claves SSH
- Las claves SSH se generan con `ssh-keygen -t ed25519 -b 4096 -C "usuario@tornalyx"`.
- La clave privada nunca sale del dispositivo del administrador y está protegida con passphrase.
- Cada administrador tiene su propio par de claves (no se comparten).
- Las claves se rotan (reemplazan) cada 12 meses o inmediatamente si el dispositivo del administrador es comprometido.
- Las claves de administradores que dejan el proyecto se eliminan de `~/.ssh/authorized_keys` el mismo día.

### 4.3 Registro y monitoreo
Todas las sesiones SSH se registran automáticamente con:
- Fecha y hora de inicio/fin de la sesión.
- Usuario y dirección IP de origen.
- Comandos ejecutados con `sudo` (en `/var/log/auth.log`).

---

## Política 5: Política de Protección de Datos Personales

**Versión:** 1.0 | **Fecha:** Junio 2026 | **Estado:** Vigente

### 5.1 Marco legal
Tornalyx cumple con la **Ley 18.331 de Protección de Datos Personales de Uruguay** y, por extensión, los principios del **Reglamento General de Protección de Datos (GDPR)** de la Unión Europea.

### 5.2 Datos recolectados y su justificación

| Dato | Finalidad | Base legal | Retención |
|------|-----------|------------|-----------|
| Nombre y apellido | Identificación en torneos | Consentimiento del usuario | Mientras la cuenta esté activa + 1 año |
| Email | Comunicaciones y login | Consentimiento del usuario | Idem |
| Fecha de nacimiento | Verificación de categoría etaria | Consentimiento del usuario | Idem |
| Historial de torneos | Estadísticas y rankings | Interés legítimo del sistema | 3 años |
| Dirección IP de login | Seguridad y auditoría | Interés legítimo (seguridad) | 90 días |

**Tornalyx NO recolecta:** datos financieros, documentos de identidad, información de salud, ni datos de localización.

### 5.3 Derechos del usuario (según Ley 18.331)
Los usuarios tienen derecho a:
- **Acceso:** solicitar una copia de todos sus datos personales en el sistema.
- **Rectificación:** corregir datos incorrectos o desactualizados.
- **Cancelación:** solicitar la eliminación de su cuenta y datos personales.
- **Oposición:** oponerse al procesamiento de sus datos para fines específicos.

**Para ejercer estos derechos:** email a `privacidad@tornalyx.uy` con identificación del solicitante. Respuesta en máximo 10 días hábiles.

### 5.4 Notificación de brechas de seguridad
En caso de brecha de seguridad que afecte datos personales:
- Notificación a la **Unidad Reguladora y de Control de Datos Personales (URCDP)** dentro de las 72 horas de detectado el incidente.
- Notificación a los usuarios afectados dentro de los 5 días hábiles, explicando qué datos se vieron comprometidos y qué medidas se tomaron.

---

## Resumen de Políticas

| Política | Aplica a | Revisión |
|----------|----------|---------|
| Seguridad de usuarios | Todos los usuarios | Anual |
| Seguridad de administradores | Rol admin | Semestral |
| Copias de seguridad | Sistema completo | Trimestral |
| Acceso remoto | Admins y operadores | Semestral |
| Protección de datos | Sistema completo | Anual o ante cambios legales |

*Todas las políticas son revisadas y actualizadas según cambios en la legislación, cambios en la infraestructura o recomendaciones del equipo de seguridad.*
