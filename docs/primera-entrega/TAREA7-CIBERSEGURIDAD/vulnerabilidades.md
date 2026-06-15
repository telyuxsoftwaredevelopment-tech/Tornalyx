# TAREA 7 – CIBERSEGURIDAD
## Vulnerabilidades del Sistema Tornalyx

---

## 1. Introducción

Este documento cataloga las vulnerabilidades identificadas en cada capa de la infraestructura de Tornalyx: servidor, base de datos, red y aplicación web. Para cada vulnerabilidad se indica el nivel de riesgo (según la metodología OWASP/CVSS), el vector de ataque y los controles de mitigación.

**Escala de riesgo:**
- 🔴 **CRÍTICO** — Compromiso total del sistema posible
- 🟠 **ALTO** — Impacto severo, explotación relativamente fácil
- 🟡 **MEDIO** — Impacto moderado o explotación compleja
- 🟢 **BAJO** — Impacto menor, difícil de explotar

---

## 2. Vulnerabilidades del Servidor

### V-S01: Software desactualizado 🔴 CRÍTICO
**Descripción:** Versiones antiguas de Apache, PHP o el SO pueden contener vulnerabilidades conocidas con exploits públicos disponibles.

| Atributo | Valor |
|----------|-------|
| **Impacto** | Ejecución remota de código (RCE), escalada de privilegios |
| **Vector** | Red — explotable de forma remota |
| **Probabilidad** | Alta si no se actualiza |
| **CVSS estimado** | 9.8 (Crítico) |

**Mitigación:**
```bash
# Mantener el sistema actualizado automáticamente
sudo apt install unattended-upgrades
sudo dpkg-reconfigure unattended-upgrades

# Verificar versiones actuales
apache2 -v
php -v
mysql --version
```

---

### V-S02: Puertos innecesarios abiertos 🟠 ALTO
**Descripción:** El servidor expone puertos que no son necesarios para el funcionamiento de Tornalyx, aumentando la superficie de ataque.

| Atributo | Valor |
|----------|-------|
| **Impacto** | Punto de entrada adicional para atacantes |
| **Vector** | Red — escaneo de puertos (nmap) |
| **Puertos necesarios** | 22 (SSH), 80 (HTTP→HTTPS), 443 (HTTPS) |

**Mitigación:**
```bash
# Configurar UFW para permitir solo los puertos necesarios
sudo ufw default deny incoming    # Denegar todo por defecto
sudo ufw default allow outgoing   # Permitir salida
sudo ufw allow 22/tcp             # SSH (restringir a IPs específicas si es posible)
sudo ufw allow 80/tcp             # HTTP (redirige a HTTPS)
sudo ufw allow 443/tcp            # HTTPS
sudo ufw enable

# Verificar puertos activos
sudo ss -tlnp
```

---

### V-S03: Acceso SSH con contraseña 🟠 ALTO
**Descripción:** Permitir autenticación SSH por contraseña facilita los ataques de fuerza bruta contra el servidor.

| Atributo | Valor |
|----------|-------|
| **Impacto** | Acceso root al servidor |
| **Vector** | Red — ataques de diccionario |

**Mitigación:**
```bash
# /etc/ssh/sshd_config
PasswordAuthentication no        # Deshabilitar autenticación por contraseña
PubkeyAuthentication yes         # Solo claves públicas
PermitRootLogin no               # Nunca permitir login directo como root
MaxAuthTries 3                   # Máximo 3 intentos fallidos
LoginGraceTime 30                # 30 segundos para autenticarse
AllowUsers admin_tornalyx operador_web  # Solo usuarios autorizados

sudo systemctl restart sshd
```

---

### V-S04: Permisos incorrectos en archivos del servidor 🟡 MEDIO
**Descripción:** Archivos de configuración con permisos demasiado permisivos pueden ser leídos o modificados por usuarios no autorizados del sistema.

**Mitigación:**
```bash
# Permisos correctos para los archivos de Tornalyx
sudo chown -R operador_web:www-data /var/www/tornalyx/
sudo find /var/www/tornalyx/ -type d -exec chmod 750 {} \;  # Directorios: rwxr-x---
sudo find /var/www/tornalyx/ -type f -exec chmod 640 {} \;  # Archivos: rw-r-----

# Archivos de configuración sensibles: solo el dueño puede leerlos
sudo chmod 600 /var/www/tornalyx/config/database.php
```

---

### V-S05: Información sensible en cabeceras HTTP 🟢 BAJO
**Descripción:** Apache revela por defecto la versión del servidor y el SO en las cabeceras HTTP, lo que ayuda a los atacantes a identificar vulnerabilidades específicas.

**Ejemplo de cabecera vulnerable:**
```
Server: Apache/2.4.57 (Ubuntu)
X-Powered-By: PHP/8.3.1
```

**Mitigación en `/etc/apache2/conf-available/security.conf`:**
```apache
ServerTokens Prod          # Solo muestra "Apache"
ServerSignature Off        # Elimina firma en páginas de error
```
**En `php.ini`:**
```ini
expose_php = Off           # Elimina cabecera X-Powered-By
```

---

## 3. Vulnerabilidades de Base de Datos

### V-DB01: Usuario de BD con privilegios excesivos 🔴 CRÍTICO
**Descripción:** Si la aplicación se conecta a MySQL con el usuario `root`, un ataque de inyección SQL puede comprometer toda la base de datos del servidor.

**Mitigación:**
```sql
-- Crear un usuario específico para la aplicación con mínimo privilegio
CREATE USER 'tornalyx_user'@'localhost' IDENTIFIED BY 'ContraseñaSegura2026!';

-- Otorgar solo los permisos necesarios (SELECT, INSERT, UPDATE, DELETE)
GRANT SELECT, INSERT, UPDATE, DELETE ON tornalyx_db.* TO 'tornalyx_user'@'localhost';

-- Denegar permisos peligrosos explícitamente
-- (no otorgar DROP, CREATE, ALTER, GRANT, FILE)
FLUSH PRIVILEGES;
```

---

### V-DB02: Contraseñas almacenadas en texto plano 🔴 CRÍTICO
**Descripción:** Almacenar contraseñas sin hash criptográfico expone todas las credenciales si la base de datos es comprometida.

**Mitigación:**
```php
// ✅ Almacenar con password_hash() usando bcrypt (costo 12)
$hash = password_hash($password_plano, PASSWORD_BCRYPT, ['cost' => 12]);
// Almacena en la BD: $2y$12$... (60 caracteres)

// ✅ Verificar contraseña en login
if (password_verify($password_ingresado, $hash_almacenado)) {
    // Login exitoso
}

// ❌ NUNCA: $query = "INSERT INTO usuarios SET password = '$password'";
// ❌ NUNCA usar MD5 o SHA1 (rotos criptográficamente)
```

---

### V-DB03: Base de datos accesible desde redes externas 🟠 ALTO
**Descripción:** Si MySQL está configurado para escuchar en todas las interfaces de red, es accesible desde Internet.

**Mitigación en `/etc/mysql/mysql.conf.d/mysqld.cnf`:**
```ini
bind-address = 127.0.0.1   # Solo escucha conexiones locales
```
```bash
sudo systemctl restart mysql
# Verificar que MySQL no está expuesto externamente:
sudo ss -tlnp | grep 3306   # Debe mostrar 127.0.0.1:3306, no 0.0.0.0:3306
```

---

### V-DB04: Sin cifrado de datos sensibles en reposo 🟡 MEDIO
**Descripción:** Datos sensibles como emails y teléfonos almacenados en texto plano en la base de datos.

**Mitigación:**
```sql
-- Usar MySQL Transparent Data Encryption (TDE) para el tablespace
-- O cifrar campos sensibles a nivel de aplicación con AES-256
```
```php
// Cifrado de campos sensibles en PHP
$email_cifrado = openssl_encrypt($email, 'AES-256-CBC', $clave_secreta, 0, $iv);
```

---

## 4. Vulnerabilidades de Red

### V-N01: Tráfico HTTP sin cifrar 🔴 CRÍTICO
**Descripción:** Sin HTTPS, todos los datos transmitidos (incluidas contraseñas y cookies de sesión) viajan en texto plano, interceptables con herramientas como Wireshark.

**Mitigación:**
```bash
# Instalar Certbot para SSL gratuito con Let's Encrypt
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tornalyx.uy -d www.tornalyx.uy

# Renovación automática (ya configurada por certbot)
sudo systemctl enable certbot.timer
```
```apache
# Redirigir todo HTTP → HTTPS en Apache
<VirtualHost *:80>
    ServerName tornalyx.uy
    Redirect permanent / https://tornalyx.uy/
</VirtualHost>
```

---

### V-N02: Ausencia de cabeceras de seguridad HTTP 🟡 MEDIO
**Descripción:** Sin las cabeceras de seguridad correctas, el navegador no puede aplicar protecciones adicionales.

**Mitigación en `.htaccess` o configuración de Apache:**
```apache
# Prevenir que el sitio sea embebido en iframes (clickjacking)
Header always set X-Frame-Options "SAMEORIGIN"

# Forzar tipo de contenido declarado (sniffing prevention)
Header always set X-Content-Type-Options "nosniff"

# Política de seguridad de contenido
Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'"

# HTTP Strict Transport Security (forzar HTTPS por 1 año)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Referrer policy
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

---

## 5. Vulnerabilidades de la Aplicación Web

### V-W01: Subida de archivos sin validación 🔴 CRÍTICO
**Descripción:** Si se permite subir imágenes de perfil o logos de equipos sin validación, un atacante puede subir un archivo PHP malicioso y ejecutarlo en el servidor.

**Mitigación:**
```php
// ✅ Validación estricta de archivos subidos
function validar_imagen(array $archivo): bool {
    // Solo tipos MIME permitidos
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo_real = $finfo->file($archivo['tmp_name']); // Leer tipo real, no la extensión

    if (!in_array($tipo_real, $tipos_permitidos)) return false;
    if ($archivo['size'] > 2 * 1024 * 1024) return false; // Max 2 MB

    // Generar nombre seguro (sin mantener el nombre original del atacante)
    $extension = match($tipo_real) {
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'
    };
    $nombre_seguro = bin2hex(random_bytes(16)) . '.' . $extension;

    // Guardar FUERA del directorio web público y servir via controlador
    move_uploaded_file($archivo['tmp_name'], '/var/tornalyx-uploads/' . $nombre_seguro);
    return true;
}
```

---

### V-W02: Mensajes de error con información sensible 🟡 MEDIO
**Descripción:** Los mensajes de error del sistema pueden revelar rutas de archivos, versiones de software o estructura de la base de datos.

**Ejemplo de error vulnerable:**
```
Fatal error: Uncaught PDOException: SQLSTATE[42000]: Syntax error... 
in /var/www/tornalyx/includes/db.php on line 45
```

**Mitigación en `php.ini` (producción):**
```ini
display_errors = Off         # No mostrar errores al usuario
log_errors = On              # Sí registrar errores en el log
error_log = /var/log/tornalyx/php_errors.log
error_reporting = E_ALL      # Registrar todos los errores
```
```php
// Manejador de errores personalizado
set_exception_handler(function($e) {
    error_log($e->getMessage());  // Registra el error real
    http_response_code(500);
    echo "Ha ocurrido un error. Por favor intenta más tarde."; // Mensaje genérico
});
```

---

## 6. Resumen de Vulnerabilidades

| ID | Componente | Vulnerabilidad | Riesgo | Prioridad |
|----|-----------|---------------|--------|-----------|
| V-S01 | Servidor | Software desactualizado | 🔴 Crítico | Inmediata |
| V-DB01 | Base de datos | Usuario BD con privilegios excesivos | 🔴 Crítico | Inmediata |
| V-DB02 | Base de datos | Contraseñas en texto plano | 🔴 Crítico | Inmediata |
| V-N01 | Red | Sin HTTPS | 🔴 Crítico | Inmediata |
| V-W01 | Aplicación | Subida de archivos sin validar | 🔴 Crítico | Inmediata |
| V-S02 | Servidor | Puertos innecesarios abiertos | 🟠 Alto | Alta |
| V-S03 | Servidor | SSH con contraseña | 🟠 Alto | Alta |
| V-DB03 | Base de datos | BD accesible externamente | 🟠 Alto | Alta |
| V-S04 | Servidor | Permisos incorrectos | 🟡 Medio | Media |
| V-DB04 | Base de datos | Sin cifrado en reposo | 🟡 Medio | Media |
| V-N02 | Red | Sin cabeceras de seguridad | 🟡 Medio | Media |
| V-W02 | Aplicación | Errores con info sensible | 🟡 Medio | Media |
| V-S05 | Servidor | Versión en cabeceras HTTP | 🟢 Bajo | Baja |
