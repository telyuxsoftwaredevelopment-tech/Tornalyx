# TAREA 5 – PROGRAMACIÓN FULL STACK
## Estructura de Carpetas del Frontend – Tornalyx SGDM

---

## Estructura Completa

```
frontend/
├── assets/
│   ├── img/
│   │   ├── logo.png
│   │   ├── logo-white.png
│   │   ├── hero-bg.jpg
│   │   └── og-image.jpg
│   └── icons/
│       ├── favicon.ico
│       └── icon-192.png
│
├── css/
│   ├── variables.css          ← Variables CSS globales (colores, tipografía, espaciados)
│   ├── main.css               ← Estilos base y utilidades comunes
│   ├── components.css         ← Estilos de componentes reutilizables
│   └── pages/
│       ├── home.css           ← Estilos específicos de la página de inicio
│       ├── torneos.css        ← Estilos de la página de torneos
│       └── dashboard.css      ← Estilos de los dashboards admin/organizador
│
├── js/
│   ├── main.js                ← Lógica global: navbar, modales, helpers
│   ├── torneos.js             ← Filtros y búsqueda de torneos
│   ├── dashboard.js           ← Lógica de navegación del dashboard (SPA-lite)
│   └── validations.js         ← Validación de formularios en el cliente
│
├── pages/
│   ├── index.html             ← Página de Inicio (pública)
│   ├── torneos.html           ← Buscar Torneos (pública)
│   ├── torneo-detalle.html    ← Detalle de un torneo (pública)
│   ├── login.html             ← Inicio de Sesión (pública)
│   ├── registro.html          ← Registro de usuario (pública)
│   ├── admin/
│   │   └── dashboard.html     ← Panel de Administración (privado - rol: admin)
│   ├── organizador/
│   │   └── dashboard.html     ← Panel Organizador (privado - rol: organizador)
│   └── participante/
│       └── perfil.html        ← Perfil del Participante (privado - rol: participante)
│
└── components/
    ├── navbar.html            ← Barra de navegación pública (reutilizable)
    ├── footer.html            ← Pie de página (reutilizable)
    ├── sidebar.html           ← Barra lateral de dashboards (reutilizable)
    ├── torneo-card.html       ← Tarjeta de torneo (componente de lista)
    └── modal.html             ← Estructura base de modales
```

---

## Descripción de Cada Carpeta

### `assets/`
Contiene todos los recursos estáticos del proyecto que no son código: imágenes, iconos, fuentes y multimedia.

- **`assets/img/`** — Imágenes del sistema: logo en sus variantes, fondos, y la imagen de Open Graph para redes sociales. No se incluye contenido generado por usuarios (eso va en el backend).
- **`assets/icons/`** — Iconos del sitio: favicon (16×16 y 32×32 px) e íconos para instalación en móvil (PWA-ready). Los íconos de interfaz (flechas, menú, etc.) se sirven mediante CSS o una librería como Feather Icons.

---

### `css/`
Todos los archivos de estilos del proyecto, organizados por responsabilidad.

- **`variables.css`** — Define los tokens de diseño del sistema: paleta de colores (`--color-primary`, `--color-bg`, etc.), tipografía (`--font-base`), tamaños de espaciado y breakpoints. Importado primero en todos los demás CSS.
- **`main.css`** — Reset CSS, estilos base (body, headings, links), clases de utilidad global (`.container`, `.btn`, `.badge`, `.card`) y el diseño del layout principal. Es el archivo más general.
- **`components.css`** — Estilos de los componentes reutilizables que aparecen en múltiples páginas: navbar, footer, sidebar, cards de torneos, modales, formularios.
- **`pages/`** — Subcarpeta con estilos específicos de cada página, importados solo donde se usan, para evitar estilos innecesarios en páginas que no los necesitan.

---

### `js/`
Scripts JavaScript en Vanilla JS (sin frameworks), organizados por funcionalidad.

- **`main.js`** — Script cargado en todas las páginas. Maneja: apertura/cierre del menú hamburger en mobile, inicialización de tooltips, sistema de toast notifications, y funciones de utilidad globales (formatear fechas, truncar texto).
- **`torneos.js`** — Lógica de la página de torneos: filtrado dinámico por categoría/estado/fecha (sin recarga), actualización del contador de resultados, y renderizado de paginación.
- **`dashboard.js`** — Navegación tipo SPA dentro del dashboard: al hacer clic en un ítem del sidebar, se oculta la sección activa y se muestra la nueva, actualizando el estado en la URL con `history.pushState`. Esto evita recargas dentro del panel.
- **`validations.js`** — Validación de formularios en tiempo real: longitud de contraseña, formato de email, campos requeridos. Se dispara en el evento `input` para feedback inmediato antes del submit.

---

### `pages/`
Contiene todos los archivos HTML del proyecto, organizados por zona de acceso.

- **`index.html`** — Página de inicio. Contiene: hero con CTA, sección de torneos destacados (con cards), estadísticas del sistema, y sección de llamada a registro.
- **`torneos.html`** — Buscador de torneos con filtros por tipo de deporte, estado y sistema de juego. Muestra un grid de cards con información resumida de cada torneo.
- **`torneo-detalle.html`** — Página pública de un torneo específico: datos generales, lista de equipos/participantes, tabla de posiciones o bracket visual, e historial de resultados.
- **`login.html`** — Formulario de inicio de sesión con email y contraseña. Incluye "recordar sesión" y enlace a recuperación de contraseña.
- **`registro.html`** — Formulario de registro en dos pasos: primero datos personales, luego selección de rol (participante u organizador).
- **`admin/dashboard.html`** — Panel privado de administración con sidebar y secciones dinámicas: resumen global (KPIs), gestión de usuarios, gestión de torneos, reportes y configuración.
- **`organizador/dashboard.html`** — Panel privado del organizador: lista de sus torneos, creación y edición de torneos, carga de resultados.
- **`participante/perfil.html`** — Perfil privado del participante: datos personales, estadísticas de rendimiento, historial de torneos e inscripción a nuevos torneos.

---

### `components/`
Fragmentos HTML reutilizables que se incluyen en múltiples páginas. En el contexto de este prototipo estático representan wireframes de los componentes; en el backend PHP se incluirán con `include`/`require`.

- **`navbar.html`** — Barra de navegación del área pública con logo, links principales y botones de login/registro. En mobile se transforma en un menú desplegable.
- **`footer.html`** — Pie de página con información del sistema, links a políticas y créditos del equipo.
- **`sidebar.html`** — Barra lateral colapsable para los dashboards privados. Contiene links de navegación, nombre del usuario y botón de cierre de sesión.
- **`torneo-card.html`** — Componente de tarjeta para mostrar un torneo en el listado: incluye imagen, nombre, deporte, estado y botón de acción.
- **`modal.html`** — Estructura base de ventana modal con overlay: se instancia dinámicamente con JavaScript para formularios de creación/edición rápida.

---

## Principio Mobile First

Todos los estilos se escriben primero para pantallas móviles (min-width: 320px) y se amplían con media queries para pantallas más grandes:

```css
/* Base: Mobile */
.container { padding: 1rem; }

/* Tablet */
@media (min-width: 768px) {
  .container { padding: 2rem; max-width: 960px; margin: auto; }
}

/* Desktop */
@media (min-width: 1024px) {
  .container { max-width: 1280px; }
}
```

---

## Convenciones de Nomenclatura

| Tipo | Convención | Ejemplo |
|------|-----------|---------|
| Archivos HTML | kebab-case | `torneo-detalle.html` |
| Archivos CSS | kebab-case | `variables.css` |
| Archivos JS | camelCase | `validations.js` |
| Clases CSS | BEM (Block__Element--Modifier) | `torneo-card__titulo--destacado` |
| IDs HTML | camelCase | `filtroDeporte` |
| Variables CSS | `--prefijo-nombre` | `--color-primary` |
