# ACTA DE REUNIÓN N° 003
## Telyux Software Development – Proyecto Tornalyx SGDM

---

| Campo | Detalle |
|-------|---------|
| **Número de acta** | 003 |
| **Fecha** | Martes, 24 de marzo de 2026 |
| **Hora de inicio** | 14:30 hrs |
| **Hora de finalización** | 16:15 hrs |
| **Modalidad** | Presencial – Aula TI-03, UTU Montevideo |
| **Tutor presente** | Prof. Alejandro Méndez |
| **Redactó el acta** | Ana Sosa |

---

## Participantes

| Nombre | Presente |
|--------|:--------:|
| Carlos García | ✅ |
| María López | ✅ |
| Juan Pérez | ✅ |
| Ana Sosa | ✅ |
| Luis Rodríguez | ✅ |
| Prof. Alejandro Méndez | ✅ |

---

## Orden del día

1. Revisión de wireframes iniciales del frontend (páginas públicas).
2. Discusión sobre el sistema de autenticación y manejo de sesiones PHP.
3. Presentación de comparativa de sistemas operativos servidor (Luis).
4. Corrección de observaciones del tutor sobre el modelo de base de datos.
5. Definición del estándar de código del equipo.

---

## Desarrollo de la reunión

### Punto 1: Revisión de wireframes

Ana presentó los wireframes HTML funcionales de las páginas:
- `index.html` (Inicio): hero, estadísticas, torneos destacados.
- `torneos.html` (Buscar torneos): filtros y listado de cards.
- `login.html`: formulario con validación cliente.
- `registro.html`: formulario en 2 pasos con stepper.

El tutor revisó los wireframes en el proyector y realizó las siguientes observaciones:

1. **Positivo:** La implementación Mobile First es correcta y funcional.
2. **Corrección 1:** El menú hamburger no funciona en mobile — falta el JavaScript de toggle. Carlos tomará nota para corrección.
3. **Corrección 2:** El formulario de registro no tiene validación de fortaleza de contraseña visible. Ana la agregará con un indicador de barra de colores.
4. **Sugerencia:** Agregar un breadcrumb (migas de pan) en las páginas de detalle para mejorar la navegación.

**Decisión:** Wireframes aprobados con las correcciones indicadas. Plazo para correcciones: 31 de marzo.

### Punto 2: Sistema de autenticación

María presentó el flujo de autenticación propuesto:
- Login con email + contraseña.
- Sesiones PHP con `$_SESSION`.
- Roles almacenados en la tabla `usuarios.rol`.
- Middleware de verificación de rol en cada página protegida.

El tutor validó el enfoque y añadió el requisito de **tokens CSRF** en todos los formularios POST para la tarea de ciberseguridad (Tarea 7).

### Punto 3: Comparativa de SO servidor

Luis presentó la comparativa entre Ubuntu Server, Debian y AlmaLinux. Conclusión: **Ubuntu Server 24.04 LTS** fue elegido por su facilidad de configuración del stack LAMP, la amplia comunidad de soporte y la compatibilidad con la mayoría de proveedores VPS educativos.

El tutor aprobó la elección y recordó documentar la configuración del servidor como parte de la entrega.

### Punto 4: Modelo de base de datos

María mostró el modelo E-R actualizado con la entidad `categorias` agregada. El tutor sugirió también agregar un campo `estado` a la tabla `torneos` (valores: 'borrador', 'activo', 'finalizado', 'cancelado').

### Punto 5: Estándar de código

Se aprobaron los siguientes estándares para el proyecto:

| Tecnología | Estándar |
|-----------|---------|
| PHP | PSR-12 (estilo de código) |
| SQL | Snake_case para nombres de tablas y columnas |
| HTML | Semantic HTML5 |
| CSS | Metodología BEM para nombres de clases |
| JavaScript | Vanilla JS, ES6+, sin frameworks |
| Commits Git | Conventional Commits (`feat:`, `fix:`, `docs:`) |

---

## Decisiones tomadas

1. ✅ **Wireframes aprobados** con correcciones para el 31 de marzo.
2. ✅ **Sistema de autenticación validado** — sesiones PHP + tokens CSRF.
3. ✅ **Ubuntu Server 24.04 LTS elegido** como SO de servidor.
4. ✅ **Modelo E-R actualizado** — agregar campo `estado` en torneos.
5. ✅ **Estándares de código aprobados** para todo el equipo.

---

## Próximas tareas

| Responsable | Tarea | Fecha límite |
|-------------|-------|--------------|
| Carlos | Implementar JavaScript del menú hamburger | 27 marzo 2026 |
| Ana | Agregar indicador de fortaleza de contraseña | 31 marzo 2026 |
| Ana | Agregar breadcrumb en torneo-detalle.html | 31 marzo 2026 |
| María | Completar wireframe del Dashboard Admin | 31 marzo 2026 |
| Luis | Iniciar documentación de ciberseguridad (Tarea 7) | 31 marzo 2026 |
| Juan | Completar y pulir el análisis SCAMPER | 31 marzo 2026 |

---

**Próxima reunión:** Martes 31 de marzo de 2026, 14:30 hrs, Aula TI-03.

---

*Firma del responsable del acta: Ana Sosa*
*Firma del tutor: Prof. Alejandro Méndez*
