# ACTA DE REUNIÓN N° 004
## Telyux Software Development – Proyecto Tornalyx SGDM

---

| Campo | Detalle |
|-------|---------|
| **Número de acta** | 004 |
| **Fecha** | Martes, 7 de abril de 2026 |
| **Hora de inicio** | 14:30 hrs |
| **Hora de finalización** | 16:00 hrs |
| **Modalidad** | Presencial – Aula TI-03, UTU Montevideo |
| **Tutor presente** | Prof. Alejandro Méndez |
| **Redactó el acta** | Luis Rodríguez |

---

## Participantes

| Nombre | Presente |
|--------|:--------:|
| Carlos García | ✅ |
| María López | ✅ |
| Juan Pérez | ❌ Ausente (notificó con anticipación — enfermedad) |
| Ana Sosa | ✅ |
| Luis Rodríguez | ✅ |
| Prof. Alejandro Méndez | ✅ |

*Nota: Juan Pérez informó su ausencia por WhatsApp el día anterior. Las tareas a su cargo fueron redistributivas para garantizar el avance del proyecto.*

---

## Orden del día

1. Revisión de correcciones de los wireframes (puntos pendientes de acta 003).
2. Presentación de los dashboards Admin y Organizador.
3. Revisión del progreso de la Tarea 7 (Ciberseguridad).
4. Discusión sobre el script Bash de gestión de usuarios.
5. Planificación para la semana final antes de la entrega.

---

## Desarrollo de la reunión

### Punto 1: Revisión de correcciones de wireframes

Carlos y Ana reportaron el estado de las correcciones indicadas en la reunión anterior:

| Corrección | Responsable | Estado |
|-----------|------------|--------|
| JavaScript menú hamburger | Carlos | ✅ Completado y funcional |
| Indicador fortaleza de contraseña | Ana | ✅ Completado (barra de colores) |
| Breadcrumb en torneo-detalle | Ana | ✅ Completado |
| Dashboard Admin (wireframe) | María | ✅ Completado |

El tutor revisó los wireframes corregidos y los aprobó sin observaciones adicionales.

### Punto 2: Presentación de Dashboards

María presentó el wireframe del Dashboard de Administración con:
- Sidebar con navegación por secciones (SPA-lite con JavaScript).
- KPIs de resumen: usuarios, torneos, equipos, partidos.
- Tabla de usuarios con opciones de editar/bloquear.
- Gestión de torneos con cards.

Ana presentó el Dashboard del Organizador con:
- Panel de mis torneos con barra de progreso por torneo.
- Formulario de creación de torneo.
- Carga de resultados.
- Tabla de posiciones.

El tutor destacó positivamente la consistencia visual entre los dashboards y la aplicación del sistema de tabs y sidebar colapsable. **Ambos dashboards aprobados.**

### Punto 3: Progreso Tarea 7 – Ciberseguridad

Luis presentó el avance de la documentación de ciberseguridad:
- Amenazas específicas (SQL Injection, XSS, CSRF, fuerza bruta): **90% completado**.
- Vulnerabilidades por capa: **80% completado**.
- Tabla de controles de acceso: **100% completado**.
- Política de contraseñas: **70% completado**.
- Políticas de seguridad (5 políticas): **50% completado**.

El tutor observó que la documentación tiene buen nivel técnico y solicitó que cada amenaza incluya un ejemplo de código tanto del ataque como de la mitigación en PHP. Luis confirmó que los ejemplos de código ya están incluidos.

### Punto 4: Script Bash

Luis presentó el avance del script Bash de gestión de usuarios (`gestion_usuarios.sh`). Funcionalidades implementadas:
- Menú interactivo con colores.
- Crear usuario (con selección de rol y grupos).
- Eliminar usuario (con confirmación).
- Bloquear/desbloquear usuario.
- Listar usuarios con estado.
- Registro de acciones en log.

El tutor pidió verificar que el script:
1. Tenga comentarios explicativos en cada línea.
2. Verifique permisos de root al inicio.
3. Valide los nombres de usuario antes de crearlos.

Luis confirmó que todos estos puntos ya están implementados y mostró el código al tutor.

**Script aprobado.**

### Punto 5: Planificación para la semana final

Con la entrega fijada para el 30 de abril de 2026, el equipo definió la hoja de ruta de las últimas dos semanas:

| Semana | Actividades |
|--------|------------|
| 7-14 abril | Completar Tarea 7 al 100%, completar actas restantes, fundamentación de diseño |
| 15-25 abril | Revisión interna de toda la documentación, correcciones finales |
| 26-29 abril | Compilación del documento final, presentación de defensa |
| 30 abril | **Entrega oficial** |

---

## Decisiones tomadas

1. ✅ **Correcciones de wireframes aprobadas** por el tutor.
2. ✅ **Dashboards Admin y Organizador aprobados**.
3. ✅ **Script Bash aprobado** — requiere revisión de comentarios.
4. ✅ **Cronograma final aprobado** — entrega el 30 de abril.
5. ✅ Se redistribuyen tareas de Juan (ausente) entre Carlos y Luis.

---

## Próximas tareas

| Responsable | Tarea | Fecha límite |
|-------------|-------|--------------|
| Luis | Completar Tarea 7 al 100% | 14 abril 2026 |
| María | Wireframe Perfil Participante | 14 abril 2026 |
| Carlos | Verificar funcionamiento de todos los links entre páginas | 14 abril 2026 |
| Ana | Iniciar concepto de logos | 14 abril 2026 |
| Carlos (por Juan) | Completar actas 4, 5 y 6 | 20 abril 2026 |
| Todos | Revisión cruzada de la documentación | 22 abril 2026 |

---

**Próxima reunión:** Martes 14 de abril de 2026, 14:30 hrs, Aula TI-03.

---

*Firma del responsable del acta: Luis Rodríguez*
*Firma del tutor: Prof. Alejandro Méndez*
