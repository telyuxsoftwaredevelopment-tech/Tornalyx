# TAREA 7 – CIBERSEGURIDAD
## Política de Contraseñas – Sistema Tornalyx

---

## 1. Objetivo

Establecer los estándares mínimos de seguridad para las contraseñas de todos los usuarios del sistema Tornalyx, con el fin de reducir el riesgo de acceso no autorizado mediante ataques de fuerza bruta, diccionario o reutilización de credenciales comprometidas.

Esta política aplica a:
- Todos los usuarios de la aplicación web (participantes, organizadores, administradores).
- Los usuarios del sistema operativo del servidor.
- Las credenciales de la base de datos.

---

## 2. Requisitos de Complejidad

### 2.1 Contraseñas de usuarios de la aplicación web

| Parámetro | Requisito | Razón |
|-----------|-----------|-------|
| **Longitud mínima** | 8 caracteres | Base NIST SP 800-63B |
| **Longitud máxima** | 128 caracteres | Soporte para gestores de contraseñas |
| **Letras mayúsculas** | Al menos 1 (A-Z) | Aumenta el espacio de búsqueda |
| **Letras minúsculas** | Al menos 1 (a-z) | Aumenta el espacio de búsqueda |
| **Dígitos numéricos** | Al menos 1 (0-9) | Aumenta el espacio de búsqueda |
| **Carácter especial** | Al menos 1 (!@#$%^&*) | Aumenta el espacio de búsqueda |
| **No repetir caracteres** | Máximo 3 consecutivos iguales | Previene "aaa111!!!" |
| **No usar nombre de usuario** | Prohibido | Contraseñas personalizadas son débiles |
| **No usar email** | Prohibido | Idem |
| **No usar palabras del diccionario** | Verificar lista de contraseñas comunes | Previene "password", "tornalyx123" |

**Ejemplos de contraseñas:**
- ❌ `carlos123` — Sin mayúsculas ni especiales
- ❌ `Password` — Sin números ni especiales
- ❌ `Tornalyx2026` — Palabra del sistema + año común
- ✅ `Tr0n@lyx#26!` — Cumple todos los requisitos
- ✅ `M1_Equ1p0_G@na!` — Frase con sustituciones (fácil de recordar, segura)
- ✅ `Xk#9pL!mQ7vN2@rT` — Generada por gestor de contraseñas

### 2.2 Contraseñas de administradores del sistema

Los administradores deben cumplir requisitos más estrictos:

| Parámetro | Requisito |
|-----------|-----------|
| **Longitud mínima** | **16 caracteres** |
| **Todos los requisitos anteriores** | ✅ |
| **No reutilizar las últimas 12 contraseñas** | ✅ |
| **Autenticación de dos factores (2FA)** | **Obligatorio** |

---

## 3. Política de Expiración

### 3.1 Usuarios de la aplicación

| Tipo de usuario | Expiración | Acción al expirar |
|----------------|------------|-------------------|
| **Participante** | 365 días (1 año) | Obligado a cambiar en próximo login |
| **Organizador** | 180 días (6 meses) | Obligado a cambiar en próximo login |
| **Administrador** | 90 días (3 meses) | Obligado a cambiar; acceso bloqueado hasta cambio |

### 3.2 Notificaciones de expiración

```
- 30 días antes: email de aviso (primera notificación)
- 15 días antes: email de aviso (segunda notificación)
- 7 días antes:  email de aviso urgente
- 1 día antes:   email final de advertencia
- Al expirar:    bloqueo de acceso + email con enlace de restablecimiento
```

---

## 4. Política de Bloqueos

### 4.1 Bloqueo por intentos fallidos

| Parámetro | Valor |
|-----------|-------|
| **Intentos fallidos permitidos** | 5 intentos consecutivos |
| **Ventana de tiempo** | 15 minutos |
| **Duración del bloqueo** | 15 minutos (automático) |
| **Bloqueo prolongado** | Después de 3 bloqueos en 1 hora: bloqueo manual por admin |
| **CAPTCHA** | Se activa desde el 3er intento fallido |

### 4.2 Proceso de desbloqueo

```
Bloqueo temporal (< 15 min) → Se desbloquea automáticamente
Bloqueo prolongado (manual) → El usuario debe:
  1. Solicitar desbloqueo por email
  2. El admin verifica la identidad
  3. El admin desbloquea y fuerza cambio de contraseña
```

---

## 5. Almacenamiento Seguro de Contraseñas

### 5.1 Algoritmo de hash

```php
// Tornalyx usa bcrypt con factor de costo 12
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verificación
$es_valida = password_verify($password_ingresado, $hash_almacenado);

// Rehasheado automático si el costo aumenta en el futuro
if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
    $nuevo_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    // Actualizar en la base de datos
}
```

**¿Por qué bcrypt con costo 12?**
- bcrypt es resistente a ataques de GPU (el hardware de los atacantes).
- Factor de costo 12 requiere ~300ms por hash → impracticable para ataques masivos.
- Factor de costo 10 (valor por defecto) requiere ~75ms → menos seguro.
- Factor de costo 14 requiere ~1.2s → muy lento para el usuario; no recomendado.

### 5.2 Lo que NUNCA se hace en Tornalyx

```
❌ Almacenar contraseñas en texto plano
❌ Usar MD5 o SHA1 (rotos criptográficamente, reversibles con rainbow tables)
❌ Usar SHA256 sin salt (vulnerable a rainbow tables)
❌ Registrar contraseñas en logs del sistema
❌ Enviar contraseñas por email (solo tokens de restablecimiento)
❌ Mostrar contraseñas antiguas al usuario al hacer cambio
```

---

## 6. Proceso de Restablecimiento de Contraseña

```
1. Usuario solicita restablecimiento → ingresa su email
2. Sistema verifica que el email existe (sin revelar si existe o no para prevenir enumeración)
3. Si existe: genera token criptográfico seguro (32 bytes aleatorios con random_bytes())
4. Almacena el hash del token en BD con expiración de 1 hora
5. Envía email con enlace: https://tornalyx.uy/reset?token=<token>
6. Usuario hace clic → ingresa nueva contraseña (x2 para confirmar)
7. Sistema valida el token (no expirado, no usado), actualiza el hash en BD
8. Invalida el token usado (borrar de BD)
9. Notifica al usuario por email que su contraseña fue cambiada
```

```php
// Generación de token seguro
$token = bin2hex(random_bytes(32));  // 64 caracteres hexadecimales
$hash_token = hash('sha256', $token); // Almacenar el hash, no el token
$expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
```

---

## 7. Educación al Usuario

Al registrarse y al cambiar contraseña, Tornalyx muestra:
- Indicador visual de fortaleza en tiempo real (débil / regular / buena / fuerte).
- Consejo: "Usa una frase con sustituciones, por ejemplo: `M1_Perr0_S3_Ll@ma_Max!`"
- Recomendación de usar un gestor de contraseñas (Bitwarden, KeePass — gratuitos).
- Aviso de que Tornalyx nunca pedirá la contraseña por email o teléfono.

---

## 8. Resumen de la Política

| Parámetro | Participante | Organizador | Administrador |
|-----------|:------------:|:-----------:|:-------------:|
| Longitud mínima | 8 | 8 | 16 |
| Mayúscula requerida | ✅ | ✅ | ✅ |
| Número requerido | ✅ | ✅ | ✅ |
| Especial requerido | ✅ | ✅ | ✅ |
| Expiración | 365 días | 180 días | 90 días |
| No reutilizar últimas N | 5 | 8 | 12 |
| Intentos antes de bloqueo | 5 | 5 | 3 |
| 2FA obligatorio | ❌ | ❌ | ✅ |
| Algoritmo hash | bcrypt costo 12 | bcrypt costo 12 | bcrypt costo 12 |
