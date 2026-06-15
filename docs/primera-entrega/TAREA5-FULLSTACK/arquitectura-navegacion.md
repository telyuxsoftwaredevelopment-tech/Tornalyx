# TAREA 5 – PROGRAMACIÓN FULL STACK
## Arquitectura de Navegación – Tornalyx SGDM

---

## 1. Modelo de Arquitectura

El sistema utiliza una arquitectura **SPA-lite** (Single Page Application ligera) sobre PHP MVC, donde la navegación ocurre mediante:

- **Rutas limpias** gestionadas por PHP (`/torneos`, `/admin/dashboard`)
- **JavaScript** para transiciones de secciones dentro del dashboard (sin recarga)
- **Sesiones PHP** para control de acceso por rol

---

## 2. Diagrama de Arquitectura de Navegación

```
                    ┌─────────────────────────────────┐
                    │        USUARIO VISITANTE         │
                    └───────────────┬─────────────────┘
                                    │
                    ┌───────────────▼─────────────────┐
                    │           INICIO (/)             │
                    │    Hero + Torneos Destacados     │
                    └──────┬──────────────┬────────────┘
                           │              │
              ┌────────────▼──┐    ┌──────▼──────────┐
              │  /torneos     │    │   /login         │
              │  Buscar       │    │   Autenticación  │
              └────────┬──────┘    └──────┬───────────┘
                       │                  │
              ┌────────▼──────┐    ┌──────▼────────────────────────┐
              │ /torneos/:id  │    │      VALIDACIÓN DE ROL        │
              │ Detalle       │    └──────┬──────────┬─────────────┘
              └───────────────┘          │           │            │
                                    ┌────▼───┐ ┌────▼────┐ ┌────▼────┐
                                    │ admin  │ │organiz. │ │partic.  │
                                    └────┬───┘ └────┬────┘ └────┬────┘
                                         │          │            │
                                    ┌────▼──┐ ┌────▼───┐ ┌────▼────┐
                                    │/admin │ │/organ. │ │/partic. │
                                    │/dash  │ │/dash   │ │/perfil  │
                                    └───────┘ └────────┘ └─────────┘
```

---

## 3. Componentes de Navegación

### 3.1 Navbar Principal (Zona Pública)
```
[TORNALYX Logo] | Inicio | Torneos | [Login] [Registro]
```
- Sticky en scroll
- Hamburger menu en mobile (< 768px)
- Resalta el ítem activo

### 3.2 Sidebar (Zonas Privadas)
```
┌─────────────────┐
│  TORNALYX       │
│  ◀ Colapsar     │
├─────────────────┤
│ 📊 Dashboard    │
│ 👥 Usuarios     │
│ 🏆 Torneos      │
│ 👤 Mi Perfil    │
│ ⚙️  Configurar  │
│ 🚪 Cerrar sesión│
└─────────────────┘
```
- Colapsable en mobile
- Ítem activo con highlight
- Badge de notificaciones

### 3.3 Breadcrumb
```
Inicio > Torneos > Copa Regional 2026
```
- Presente en todas las páginas internas
- Enlace clickeable en cada nivel

---

## 4. Sistema de Rutas PHP

```php
// Tabla de rutas del sistema
$routes = [
    '/'                      => 'HomeController@index',
    '/torneos'               => 'TorneoController@index',
    '/torneos/{id}'          => 'TorneoController@show',
    '/login'                 => 'AuthController@loginForm',
    '/login/post'            => 'AuthController@login',
    '/registro'              => 'AuthController@registerForm',
    '/registro/post'         => 'AuthController@register',
    '/logout'                => 'AuthController@logout',
    '/admin/dashboard'       => 'AdminController@dashboard',    // [admin]
    '/organizador/dashboard' => 'OrgController@dashboard',     // [organizador]
    '/participante/perfil'   => 'ParticipanteController@perfil' // [participante]
];
```

---

## 5. Control de Acceso por Rol

| Ruta | Público | Participante | Organizador | Admin |
|------|---------|-------------|-------------|-------|
| `/` | ✅ | ✅ | ✅ | ✅ |
| `/torneos` | ✅ | ✅ | ✅ | ✅ |
| `/torneos/:id` | ✅ | ✅ | ✅ | ✅ |
| `/login` | ✅ | ❌ | ❌ | ❌ |
| `/registro` | ✅ | ❌ | ❌ | ❌ |
| `/participante/perfil` | ❌ | ✅ | ❌ | ❌ |
| `/organizador/dashboard` | ❌ | ❌ | ✅ | ❌ |
| `/admin/dashboard` | ❌ | ❌ | ❌ | ✅ |

---

## 6. Transiciones y UX

- **Página a página:** recarga HTTP estándar con PHP
- **Secciones del dashboard:** cambio con JavaScript (sin recarga) mediante `data-section`
- **Modales:** Vanilla JS para formularios de creación/edición rápida
- **Feedback:** Toast notifications (CSS + JS) para acciones exitosas/fallidas
- **Loading states:** Spinner CSS en peticiones AJAX

---

## 7. Gestión de Sesiones

```
Login exitoso
    │
    ▼
$_SESSION['user_id']   = id del usuario
$_SESSION['user_rol']  = 'admin' | 'organizador' | 'participante'
$_SESSION['user_nombre'] = nombre completo
    │
    ▼
Middleware verifica rol en cada ruta protegida
    │
    ├── Correcto → Render de la vista
    └── Incorrecto → Redirect a /login (con mensaje de error)
```
