# TAREA 8 – TUTORÍA UTULAB
## Concepto Visual de Logos

---

## 1. Logo del Grupo: Telyux Software Development

### 1.1 Concepto visual

El logo de Telyux Software Development combina elementos del mundo tecnológico con la idea de "propósito" y "conexión" que evocan las sílabas del nombre.

**Descripción del diseño:**

**Símbolo (ícono):** Un hexágono (figura geométrica perfecta, asociada en tecnología con las APIs, redes y estructuras de datos eficientes) que contiene en su interior las letras "TX" estilizadas. La "T" forma una estructura de árbol (árbol de decisiones, estructura de datos) y la "X" representa la intersección de caminos — el punto donde el código y el mundo real se encuentran.

**El hexágono tiene un corte diagonal** en su vértice superior derecho, creando una "chispa" o "acento" que simboliza la innovación que surge del núcleo sólido del equipo.

**Tipografía:** Sans-serif geométrica moderna (similar a "Space Grotesk" o "Exo 2"). Nombre "Telyux" en peso Bold, "Software Development" en peso Light debajo, con mayor tracking (espaciado entre letras).

**Paleta de colores:**
- Color principal: `#1a56db` (azul profundo — confianza, tecnología, profesionalismo)
- Color acento: `#0e9f6e` (verde — innovación, crecimiento, código ejecutado con éxito)
- Color de texto: `#111827` (casi negro — legibilidad máxima)
- Versión dark/negativa: texto e ícono en `#ffffff` sobre fondo `#0f172a`

**Variantes del logo:**
1. **Logo completo** (horizontal): ícono hexagonal a la izquierda + nombre completo a la derecha.
2. **Logo vertical**: ícono arriba + nombre abajo (para redes sociales y badges).
3. **Isotipo** (solo el ícono): para favicon, app icon y usos donde el espacio es reducido.
4. **Logo monochrome**: versión en un solo color para impresión o fondos de color.

**Usos correctos:**
- Sobre fondos blancos o claros: versión en color.
- Sobre fondos oscuros: versión negativa (blanca).
- Tamaño mínimo: 120px de ancho para logo completo, 32px para isotipo.
- Espacio de respiro: mínimo el 50% del ancho del ícono en todos sus lados.

**Usos incorrectos:**
- No distorsionar las proporciones del logo.
- No cambiar los colores por colores no definidos en la paleta.
- No aplicar sombras, degradados o efectos 3D al logo.
- No colocar el logo sobre fondos con patrón que dificulten su lectura.

---

## 2. Logo de la Aplicación: Tornalyx SGDM

### 2.1 Concepto visual

El logo de Tornalyx debe comunicar inmediatamente su propósito: gestión de torneos deportivos. A diferencia del logo de la empresa (más abstracto y corporativo), el logo de Tornalyx es más dinámico y evocador de la acción y la competencia.

**Descripción del diseño:**

**Símbolo (ícono):** Un trofeo estilizado y minimalista, dibujado con trazo limpio (line art de 2-3px). El trofeo no es realista sino geométrico — sus asas son formadas por un símbolo que recuerda a los corchetes de código `{ }`, conectando el mundo deportivo con el tecnológico.

En la base del trofeo, en lugar del texto habitual, hay una pequeña barra de carga al estilo de progreso (progress bar), con el 70% completado — simbolizando que los torneos siempre están en marcha.

**Alternativa compacta:** La letra "T" con un trofeo integrado en la barra horizontal superior de la T, como si la letra "sostuviera" el trofeo.

**Tipografía:** "Tornalyx" en una sans-serif de alto impacto, con la letra "T" inicial ligeramente agrandada (cap first) y el texto en peso ExtraBold. "SGDM" aparece como subtítulo en mayúsculas pequeñas (small caps) con tracking amplio.

**Paleta de colores:**
- Primario: `#1a56db` (azul — identidad del sistema)
- Secundario: `#1e3a8a` (azul oscuro — profundidad)
- Acento dorado: `#f59e0b` (dorado/ámbar — trofeos, campeones, excelencia)
- Fondo oscuro: `#0f172a` (azul muy oscuro — navbar, dashboards)
- Blanco puro: `#ffffff` (contraste)

**Variantes de color de la aplicación:**
- `#1a56db` — acciones primarias (botones, links activos)
- `#0e9f6e` — estado positivo (activo, ganado, éxito)
- `#e02424` — estado de alerta (error, eliminado)
- `#d97706` — estado de advertencia (próximo, pendiente)

### 2.2 Iconografía del sistema

Dentro de la aplicación se usa un conjunto coherente de íconos basados en la librería **Feather Icons** o **Heroicons** (ambas de código abierto) con las siguientes adaptaciones:

| Módulo | Ícono | Uso |
|--------|-------|-----|
| Inicio/Dashboard | `home` | Pantalla principal |
| Torneos | `award` / `trophy` | Listado de torneos |
| Usuarios | `users` | Gestión de usuarios |
| Equipos | `shield` | Listado de equipos |
| Resultados | `clipboard` | Carga de resultados |
| Estadísticas | `bar-chart-2` | Reportes y gráficos |
| Configuración | `settings` | Ajustes del sistema |
| Login | `log-in` | Acceso al sistema |
| Logout | `log-out` | Cierre de sesión |

### 2.3 Aplicación en el sistema

**Favicon (16x16, 32x32, 48x48 px):**
El isotipo del trofeo simplificado en versión plana (flat), optimizado para ser reconocible a tamaños pequeños. Sobre fondo azul `#1a56db`.

**App Icon (192x192, 512x512 px — para PWA / acceso directo):**
El símbolo del trofeo en versión grande sobre fondo azul oscuro degradado (`#1e3a8a` → `#0f172a`), con esquinas redondeadas al 20% (estilo iOS/Android).

**Open Graph image (1200x630 px — para compartir en redes):**
Fondo oscuro con el logo completo de Tornalyx en blanco, más el slogan y una captura de pantalla del dashboard. Se usa cuando se comparte un enlace al sitio en redes sociales.

---

## 3. Sistema de Diseño Unificado (Design System)

Tanto el logo de Telyux Software Development como el de Tornalyx comparten:

| Elemento | Telyux SD | Tornalyx |
|----------|-----------|---------|
| Familia tipográfica | Space Grotesk / Inter | Inter |
| Estilo de ícono | Geométrico / Minimalista | Line art / Flat |
| Personalidad | Corporativa / Técnica | Dinámica / Deportiva |
| Color base | Azul `#1a56db` | Azul `#1a56db` |
| Color acento | Verde `#0e9f6e` | Dorado `#f59e0b` |
| Esquinas | Rígidas (hexágono) | Redondeadas |

Esta coherencia visual comunica que Tornalyx es un producto de Telyux Software Development, manteniendo la relación entre marca madre y producto.
