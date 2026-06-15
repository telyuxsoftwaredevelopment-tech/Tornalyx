# TAREA 7 – CIBERSEGURIDAD
## Controles de Acceso – Tabla de Permisos por Rol

---

## 1. Introducción

Tornalyx implementa un modelo de control de acceso basado en roles (**RBAC – Role-Based Access Control**). Cada usuario del sistema tiene asignado un rol que determina exactamente a qué recursos puede acceder y qué operaciones puede realizar. Este modelo aplica el principio de **mínimo privilegio**: ningún usuario tiene más permisos de los estrictamente necesarios para cumplir su función.

---

## 2. Roles del Sistema

| Rol | Descripción | Código en BD |
|-----|-------------|-------------|
| **Público** | Visitante no autenticado | — |
| **Participante** | Usuario registrado que compite en torneos | `participante` |
| **Organizador** | Usuario que crea y gestiona torneos | `organizador` |
| **Administrador** | Control total del sistema | `admin` |

---

## 3. Tabla Completa de Permisos por Módulo y Rol

### 3.1 Módulo: Autenticación y Sesión

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Ver página de login | ✅ | ❌ (redirige al dashboard) | ❌ | ❌ |
| Iniciar sesión | ✅ | — | — | — |
| Ver página de registro | ✅ | ❌ | ❌ | ❌ |
| Registrarse | ✅ | — | — | — |
| Cerrar sesión | ❌ | ✅ | ✅ | ✅ |
| Cambiar contraseña propia | ❌ | ✅ | ✅ | ✅ |
| Recuperar contraseña | ✅ | ✅ | ✅ | ✅ |

---

### 3.2 Módulo: Torneos (Vista pública)

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Listar todos los torneos | ✅ | ✅ | ✅ | ✅ |
| Buscar/filtrar torneos | ✅ | ✅ | ✅ | ✅ |
| Ver detalle de torneo | ✅ | ✅ | ✅ | ✅ |
| Ver tabla de posiciones | ✅ | ✅ | ✅ | ✅ |
| Ver bracket de eliminación | ✅ | ✅ | ✅ | ✅ |
| Ver historial de resultados | ✅ | ✅ | ✅ | ✅ |
| Ver equipos inscritos | ✅ | ✅ | ✅ | ✅ |

---

### 3.3 Módulo: Torneos (Gestión)

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Crear torneo | ❌ | ❌ | ✅ | ✅ |
| Editar torneo propio | ❌ | ❌ | ✅ | ✅ |
| Editar cualquier torneo | ❌ | ❌ | ❌ | ✅ |
| Eliminar torneo propio | ❌ | ❌ | ✅ (solo si no ha iniciado) | ✅ |
| Eliminar cualquier torneo | ❌ | ❌ | ❌ | ✅ |
| Publicar/activar torneo | ❌ | ❌ | ✅ | ✅ |
| Cancelar torneo | ❌ | ❌ | ✅ (solo propio) | ✅ |
| Configurar formato (liga/suizo/elim.) | ❌ | ❌ | ✅ (al crear) | ✅ |
| Generar fixture automático | ❌ | ❌ | ✅ | ✅ |

---

### 3.4 Módulo: Participantes y Equipos

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Inscribirse a un torneo | ❌ | ✅ | ✅ | ✅ |
| Desinscribirse (antes del inicio) | ❌ | ✅ | ✅ | ✅ |
| Crear equipo | ❌ | ✅ | ✅ | ✅ |
| Editar equipo propio | ❌ | ✅ (si es capitán) | ✅ | ✅ |
| Editar cualquier equipo | ❌ | ❌ | ❌ | ✅ |
| Eliminar equipo | ❌ | ✅ (si es capitán) | ❌ | ✅ |
| Agregar miembros a equipo | ❌ | ✅ (si es capitán) | ✅ | ✅ |
| Aprobar inscripciones en torneo propio | ❌ | ❌ | ✅ | ✅ |
| Ver lista de participantes de un torneo | ✅ | ✅ | ✅ | ✅ |

---

### 3.5 Módulo: Resultados

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Ver resultados de partidos | ✅ | ✅ | ✅ | ✅ |
| Cargar resultado de partido | ❌ | ❌ | ✅ (solo torneos propios) | ✅ |
| Editar resultado de partido | ❌ | ❌ | ✅ (con justificación) | ✅ |
| Eliminar resultado | ❌ | ❌ | ❌ | ✅ |
| Exportar resultados (CSV) | ❌ | ✅ | ✅ | ✅ |

---

### 3.6 Módulo: Gestión de Usuarios (Panel Admin)

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Ver lista de todos los usuarios | ❌ | ❌ | ❌ | ✅ |
| Ver perfil de otro usuario | ❌ | ❌ | ❌ | ✅ |
| Crear usuario manualmente | ❌ | ❌ | ❌ | ✅ |
| Editar datos de cualquier usuario | ❌ | ❌ | ❌ | ✅ |
| Cambiar rol de usuario | ❌ | ❌ | ❌ | ✅ |
| Bloquear/desbloquear usuario | ❌ | ❌ | ❌ | ✅ |
| Eliminar usuario | ❌ | ❌ | ❌ | ✅ |
| Ver propio perfil | ❌ | ✅ | ✅ | ✅ |
| Editar propios datos personales | ❌ | ✅ | ✅ | ✅ |

---

### 3.7 Módulo: Reportes y Estadísticas

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Ver estadísticas públicas del sistema | ✅ | ✅ | ✅ | ✅ |
| Ver mis propias estadísticas | ❌ | ✅ | ✅ | ✅ |
| Ver estadísticas de torneos propios | ❌ | ❌ | ✅ | ✅ |
| Ver reportes globales del sistema | ❌ | ❌ | ❌ | ✅ |
| Exportar reportes (PDF/CSV) | ❌ | ✅ (solo propios) | ✅ | ✅ |

---

### 3.8 Módulo: Configuración del Sistema

| Acción | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Modificar configuración del sistema | ❌ | ❌ | ❌ | ✅ |
| Gestionar categorías de deportes | ❌ | ❌ | ❌ | ✅ |
| Ver logs del sistema | ❌ | ❌ | ❌ | ✅ |
| Realizar backups manuales | ❌ | ❌ | ❌ | ✅ |
| Gestionar permisos de roles | ❌ | ❌ | ❌ | ✅ |

---

## 4. Implementación del RBAC en PHP

```php
<?php
// config/roles.php — Definición de permisos por rol

define('PERMISOS', [
    'admin' => [
        'torneos.crear', 'torneos.editar_cualquiera', 'torneos.eliminar_cualquiera',
        'usuarios.listar', 'usuarios.editar_cualquiera', 'usuarios.bloquear',
        'resultados.cargar', 'resultados.editar', 'resultados.eliminar',
        'reportes.globales', 'sistema.configurar'
    ],
    'organizador' => [
        'torneos.crear', 'torneos.editar_propio', 'torneos.eliminar_propio',
        'participantes.aprobar', 'resultados.cargar', 'reportes.propios'
    ],
    'participante' => [
        'torneos.inscribirse', 'equipos.crear', 'equipos.editar_propio',
        'reportes.propios'
    ]
]);

/**
 * Verifica si el usuario actual tiene un permiso específico.
 * @param string $permiso - Permiso a verificar (ej: 'torneos.crear')
 * @return bool
 */
function tiene_permiso(string $permiso): bool {
    if (!isset($_SESSION['user_rol'])) return false;
    $rol = $_SESSION['user_rol'];
    return in_array($permiso, PERMISOS[$rol] ?? []);
}

// Uso en controladores:
// if (!tiene_permiso('torneos.crear')) {
//     http_response_code(403);
//     die('No autorizado');
// }
?>
```

---

## 5. Resumen Visual de Acceso por Módulo

| Módulo | Público | Participante | Organizador | Admin |
|--------|:-------:|:------------:|:-----------:|:-----:|
| Torneos (ver) | ✅ | ✅ | ✅ | ✅ |
| Torneos (gestión) | ❌ | ❌ | ✅ (propios) | ✅ |
| Equipos (ver) | ✅ | ✅ | ✅ | ✅ |
| Equipos (gestión) | ❌ | ✅ (propios) | ✅ | ✅ |
| Resultados (ver) | ✅ | ✅ | ✅ | ✅ |
| Resultados (cargar) | ❌ | ❌ | ✅ (propios) | ✅ |
| Usuarios (gestión) | ❌ | ❌ | ❌ | ✅ |
| Reportes globales | ❌ | ❌ | ❌ | ✅ |
| Configuración | ❌ | ❌ | ❌ | ✅ |

**Leyenda:** ✅ Permitido · ❌ Denegado (→ 403 o redirige a login)
