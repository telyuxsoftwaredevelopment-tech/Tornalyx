# TAREA 8 – TUTORÍA UTULAB
## Fundamentación de Diseño del Sistema Tornalyx SGDM

---

## Introducción

El presente documento justifica las decisiones de diseño técnico y de interfaz tomadas para el Sistema de Gestión Deportiva Modular (SGDM) Tornalyx. Cada decisión está fundamentada en criterios técnicos, educativos, de accesibilidad y de viabilidad para el contexto del Bachillerato Tecnológico en Tecnologías de la Información.

---

## 1. Justificación de la Arquitectura MVC (Modelo-Vista-Controlador)

### ¿Qué es MVC?
MVC es un patrón de diseño de software que separa una aplicación en tres capas lógicas:
- **Modelo:** acceso y manipulación de datos (MySQL, clases PHP de entidades).
- **Vista:** presentación al usuario (archivos HTML, plantillas PHP).
- **Controlador:** lógica de negocio, intermediario entre modelo y vista (clases PHP).

### Justificación para Tornalyx

**1. Mantenibilidad del código:**
En un sistema con múltiples módulos (usuarios, torneos, equipos, resultados), separar la lógica de la presentación permite que un desarrollador trabaje en el diseño de la interfaz sin afectar la lógica de negocio, y viceversa. En el equipo Telyux, Ana puede trabajar en las Vistas mientras María trabaja en los Modelos de forma independiente.

**2. Escalabilidad:**
A medida que Tornalyx crezca con nuevas funcionalidades (sistema de chat, notificaciones, rankings), la arquitectura MVC facilita agregar nuevos módulos sin reescribir el código existente.

**3. Testabilidad:**
Los Modelos pueden ser testeados de forma unitaria sin necesidad de una interfaz gráfica. Esto facilita la validación de la lógica de negocio.

**4. Estándar de la industria:**
Los frameworks PHP más utilizados en el mercado laboral (Laravel, Symfony, CodeIgniter) implementan MVC. Aprender este patrón en el proyecto prepara a los estudiantes para el mundo profesional.

**5. Cumplimiento del enunciado:**
El enunciado del proyecto indica explícitamente el uso de arquitectura MVC como requisito obligatorio.

### Ejemplo de estructura de carpetas MVC en Tornalyx
```
tornalyx/
├── app/
│   ├── Models/          ← Clases de acceso a datos (Usuario, Torneo, Equipo)
│   ├── Controllers/     ← Lógica de negocio (AuthController, TorneoController)
│   └── Views/           ← Plantillas HTML/PHP
├── config/              ← Configuración de BD y rutas
├── public/              ← Punto de entrada (index.php), assets CSS/JS
└── core/                ← Router, conexión BD, funciones base
```

---

## 2. Justificación de Mobile First

### ¿Qué es Mobile First?
Mobile First es un enfoque de diseño web donde los estilos CSS se escriben primero para pantallas pequeñas (smartphones) y luego se amplían progresivamente para pantallas más grandes (tablets, escritorio) mediante media queries.

```css
/* ✅ Mobile First: estilos base para mobile */
.cards-grid { grid-template-columns: 1fr; gap: 1rem; }

/* Se agrega para tablet y desktop */
@media (min-width: 640px)  { .cards-grid { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .cards-grid { grid-template-columns: 1fr 1fr 1fr; } }
```

### Justificación para Tornalyx

**1. Estadísticas de uso en Uruguay:**
Según datos de StatCounter (2025), más del 55% del tráfico web en Uruguay proviene de dispositivos móviles. Los usuarios de Tornalyx (jóvenes deportistas) tienen alta probabilidad de acceder al sistema desde sus smartphones para consultar resultados o su perfil.

**2. Carga más rápida en redes móviles:**
Al diseñar primero para mobile, se prioriza la eficiencia: solo se cargan los estilos y recursos necesarios para la pantalla pequeña. Las capas adicionales para desktop se agregan después.

**3. Mejor experiencia de usuario:**
Una interfaz pensada desde mobile garantiza que los elementos táctiles (botones, formularios) tienen el tamaño mínimo recomendado (44x44 píxeles según WCAG 2.1 — criterio de "Target Size").

**4. SEO (posicionamiento en buscadores):**
Google utiliza el índice "mobile-first" desde 2019. Los sitios que no son mobile-friendly tienen penalización en el ranking de búsqueda, lo que afecta la visibilidad pública de los torneos en Tornalyx.

**5. Accesibilidad:**
El diseño Mobile First fuerza a priorizar el contenido más importante y eliminar lo superfluo, lo que resulta en interfaces más limpias y accesibles para todos los usuarios, incluyendo personas con discapacidades visuales que usan lectores de pantalla.

---

## 3. Justificación del Uso de PHP

### ¿Por qué PHP y no otro lenguaje de backend?

| Criterio | PHP | Node.js | Python (Django) |
|----------|-----|---------|-----------------|
| Requerido en el programa educativo | ✅ Sí | ❌ No | ❌ No |
| Integración nativa con Apache+MySQL | ✅ Excelente | ⚠️ Requiere config. adicional | ⚠️ Requiere config. adicional |
| Curva de aprendizaje para el equipo | ✅ Ya conocido | ⚠️ Moderada | ⚠️ Moderada |
| Hosting compartido económico | ✅ Muy disponible | ❌ Requiere VPS | ❌ Requiere VPS |
| Documentación en español | ✅ Extensa | ✅ Extensa | ✅ Extensa |

**Justificación técnica adicional:**

1. **PHP es el lenguaje más usado en la web:** según W3Techs (2025), el 76.9% de todos los sitios web con lenguaje de servidor conocido usan PHP. WordPress, el CMS más popular del mundo, está construido en PHP.

2. **PDO y prepared statements:** PHP dispone de PDO (PHP Data Objects), una interfaz segura y consistente para acceder a bases de datos que incluye soporte nativo para sentencias preparadas, la principal defensa contra SQL Injection.

3. **Sesiones nativas:** PHP tiene manejo de sesiones integrado con `session_start()`, simplificando la implementación de autenticación sin dependencias externas.

4. **Composable con HTML:** los archivos `.php` permiten mezclar HTML y código PHP de forma natural, lo que facilita la implementación de las Vistas en la arquitectura MVC sin un motor de plantillas adicional.

---

## 4. Justificación del Uso de MySQL

### ¿Por qué MySQL para la base de datos?

**1. Sistema de Gestión de Bases de Datos Relacional (SGBD Relacional):**
Tornalyx gestiona datos con relaciones complejas entre sí: un torneo tiene muchos equipos, un equipo tiene muchos jugadores, un partido tiene dos equipos y un resultado. Los SGBD relacionales como MySQL modelan estas relaciones de forma natural y eficiente con tablas relacionadas por claves foráneas.

**2. ACID compliance:**
MySQL garantiza transacciones ACID (Atomicidad, Consistencia, Aislamiento, Durabilidad). Esto es crítico para Tornalyx: si se registra un resultado de partido, la actualización de la tabla de posiciones debe ocurrir en la misma transacción atómica, sin posibilidad de estados inconsistentes.

**3. Integración perfecta con PHP:**
La combinación PHP + MySQL es la más documentada de la industria. La extensión PDO para MySQL está optimizada y disponible en cualquier instalación estándar de PHP.

**4. Requisito del enunciado:**
El enunciado del proyecto especifica MySQL como base de datos obligatoria.

**5. Gratuidad y licencia:**
MySQL Community Edition es gratuita y de código abierto (licencia GNU GPL), lo que elimina costos de licenciamiento.

**6. Motor InnoDB:**
Tornalyx usará el motor InnoDB de MySQL (por defecto desde MySQL 5.5), que soporte claves foráneas, transacciones y bloqueo a nivel de fila, características esenciales para la integridad de los datos de torneos.

---

## 5. Justificación del Sistema Modular

### ¿Qué significa que Tornalyx sea "modular"?

Un sistema modular está compuesto por módulos independientes y reemplazables, cada uno responsable de una función específica, que se comunican entre sí mediante interfaces bien definidas.

**Módulos de Tornalyx:**
- Módulo de Autenticación (login, registro, sesiones)
- Módulo de Torneos (crear, editar, gestionar)
- Módulo de Equipos (inscripciones, miembros)
- Módulo de Resultados (carga, cálculo de posiciones)
- Módulo de Consulta Pública (sin autenticación)
- Módulo de Administración (panel de control)

### Justificación de la modularidad

**1. Independencia de desarrollo:**
Cada módulo puede ser desarrollado y probado de forma independiente. El módulo de Resultados puede funcionar aunque el módulo de Administración no esté terminado.

**2. Reusabilidad:**
El módulo de Autenticación puede reutilizarse en futuros proyectos de Telyux Software Development sin modificaciones.

**3. Mantenibilidad:**
Si la lógica de cálculo del Sistema Suizo necesita corregirse, solo se modifica el módulo de Resultados, sin riesgo de afectar el módulo de Torneos o de Autenticación.

**4. Escalabilidad incremental:**
Tornalyx comenzó con módulos básicos en la primera entrega y puede incorporar nuevos módulos (chat en tiempo real, estadísticas avanzadas, rankings históricos) sin reescribir el sistema base.

---

## 6. Justificación del Diseño Responsive

### ¿Qué es el diseño responsive?

Un diseño responsive es aquel que adapta su presentación visual y layout al tamaño y resolución de la pantalla del dispositivo del usuario, sin necesidad de una versión separada del sitio para mobile y desktop.

Tornalyx usa un único conjunto de archivos HTML/CSS que se adapta a:
- **Mobile:** 320px – 639px (smartphone)
- **Tablet:** 640px – 1023px
- **Desktop:** 1024px en adelante

### Justificación del diseño responsive en Tornalyx

**1. Base de usuarios diversa:**
Los usuarios de Tornalyx son deportistas, organizadores y espectadores. Los espectadores consultarán resultados desde donde puedan (smartphone durante el partido), mientras que los administradores trabajarán preferentemente desde una computadora de escritorio.

**2. Una sola base de código:**
Mantener una versión mobile y una versión desktop separadas duplica el esfuerzo de desarrollo y mantenimiento. El diseño responsive resuelve ambas necesidades con un único código.

**3. Accesibilidad (WCAG 2.2):**
La guía WCAG 2.2 (Web Content Accessibility Guidelines) del W3C recomienda que el contenido web sea accesible en múltiples tamaños de pantalla, incluyendo el redimensionamiento hasta el 400% sin pérdida de funcionalidad.

**4. Consistencia de la experiencia:**
El mismo usuario puede usar Tornalyx desde su teléfono para ver el resultado de un partido y luego desde su computadora para cargar el resultado como organizador. La experiencia visual coherente entre dispositivos genera confianza en la plataforma.

**5. Variables CSS para consistencia:**
Tornalyx usa CSS Custom Properties (variables CSS) para definir una paleta de colores, tipografía y espaciados únicos aplicados consistentemente en todas las páginas y tamaños de pantalla, garantizando la identidad visual del sistema en cualquier dispositivo.

---

## 7. Resumen de decisiones de diseño

| Decisión | Alternativas consideradas | Por qué se eligió esta |
|---------|--------------------------|------------------------|
| Arquitectura MVC | Arquitectura monolítica sin separación, microservicios | Mantenibilidad, estándar educativo, escalabilidad |
| Mobile First | Desktop First, diseño fijo | Estadísticas de uso móvil, SEO, accesibilidad |
| PHP | Node.js, Python | Requisito educativo, integración LAMP, conocimiento previo del equipo |
| MySQL | PostgreSQL, SQLite, MongoDB | Requisito educativo, ACID, integración PHP, relaciones complejas |
| Sistema modular | Monolito sin separación de módulos | Independencia de desarrollo, reusabilidad, mantenibilidad |
| Diseño responsive | Versiones separadas mobile/desktop | Una base de código, accesibilidad, experiencia consistente |
| CSS puro (sin framework) | Bootstrap, Tailwind CSS | Requisito educativo, demostración de competencia en CSS |
| Vanilla JavaScript | React, Vue, jQuery | Sin dependencias externas, mejor aprendizaje de los fundamentos |

---

*Documento elaborado por Telyux Software Development para la Primera Entrega del Proyecto SGDM – Tornalyx.*
*Bachillerato Tecnológico en Tecnologías de la Información | UTU Montevideo | Junio 2026.*
