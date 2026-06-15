# SITEMAP – Sistema de Gestión Deportiva Modular (SGDM)
## Tornalyx | TAREA 5 – Programación Full Stack

---

## Estructura General del Sitio

```
TORNALYX
├── ZONA PÚBLICA
│   ├── / (Inicio)
│   │   ├── Hero / Presentación del sistema
│   │   ├── Torneos destacados
│   │   ├── Últimos resultados
│   │   └── CTA: Registrarse / Ver torneos
│   │
│   ├── /torneos (Buscar torneos)
│   │   ├── Filtros (deporte, estado, fecha, formato)
│   │   ├── Listado de torneos
│   │   └── Paginación
│   │
│   ├── /torneos/{id} (Detalle de torneo)
│   │   ├── Información general
│   │   ├── Equipos/Participantes inscritos
│   │   ├── Fixture / Cuadro de llave
│   │   └── Resultados
│   │
│   ├── /login (Iniciar sesión)
│   │   ├── Formulario email/contraseña
│   │   ├── Recuperar contraseña
│   │   └── Enlace a registro
│   │
│   └── /registro (Registro de usuario)
│       ├── Formulario de registro
│       ├── Selección de rol (participante/organizador)
│       └── Confirmación por email
│
├── ZONA PARTICIPANTE (requiere login)
│   └── /participante/perfil
│       ├── Datos personales
│       ├── Historial de torneos
│       ├── Estadísticas
│       └── Equipos a los que pertenece
│
├── ZONA ORGANIZADOR (requiere login + rol organizador)
│   └── /organizador/dashboard
│       ├── Mis torneos
│       ├── Crear torneo
│       ├── Gestionar participantes/equipos
│       └── Cargar resultados
│
└── ZONA ADMINISTRADOR (requiere login + rol admin)
    └── /admin/dashboard
        ├── Panel general (estadísticas globales)
        ├── Gestión de usuarios
        ├── Gestión de torneos
        ├── Gestión de equipos
        ├── Configuración del sistema
        └── Reportes
```

---

## Mapa de Rutas PHP (Backend)

| Ruta Frontend         | Archivo PHP Backend         | Descripción                        |
|-----------------------|-----------------------------|------------------------------------|
| `/`                   | `index.php`                 | Página de inicio pública           |
| `/torneos`            | `pages/torneos.php`         | Listado y búsqueda de torneos      |
| `/torneos/{id}`       | `pages/torneo-detalle.php`  | Detalle de un torneo específico    |
| `/login`              | `pages/login.php`           | Inicio de sesión                   |
| `/registro`           | `pages/registro.php`        | Registro de nuevos usuarios        |
| `/participante/perfil`| `pages/participante/perfil.php` | Perfil del participante        |
| `/organizador/`       | `pages/organizador/dashboard.php` | Dashboard del organizador  |
| `/admin/`             | `pages/admin/dashboard.php` | Dashboard del administrador        |

---

## Flujo de Navegación Principal

```
Usuario no autenticado:
Inicio → Buscar torneos → Ver detalle → Login → Registro

Usuario autenticado (participante):
Login → Perfil participante → Ver torneos → Inscribirse

Usuario autenticado (organizador):
Login → Dashboard organizador → Crear torneo → Gestionar → Cargar resultados

Usuario autenticado (administrador):
Login → Dashboard admin → Gestión global del sistema
```

---

## Nivel de Acceso por Página

| Página                   | Público | Participante | Organizador | Admin |
|--------------------------|:-------:|:------------:|:-----------:|:-----:|
| Inicio                   |   ✅    |     ✅       |     ✅      |  ✅   |
| Buscar torneos           |   ✅    |     ✅       |     ✅      |  ✅   |
| Detalle torneo           |   ✅    |     ✅       |     ✅      |  ✅   |
| Login                    |   ✅    |     —        |     —       |  —    |
| Registro                 |   ✅    |     —        |     —       |  —    |
| Perfil participante      |   ❌    |     ✅       |     ✅      |  ✅   |
| Dashboard organizador    |   ❌    |     ❌       |     ✅      |  ✅   |
| Dashboard administrador  |   ❌    |     ❌       |     ❌      |  ✅   |

---

*Documento generado para la Primera Entrega del proyecto SGDM – Tornalyx*
*Bachillerato Tecnológico en TI | Grupo: [Nombre del grupo]*
