# Roles dentro del Equipo de Proyecto
## Tornalyx SGDM | Ingeniería de Software – Primera Entrega

---

## Roles definidos

### Rol 1 – Líder de Proyecto

**Responsable:** [Integrante 1]

**Descripción:**
Coordina el trabajo del equipo, comunica los avances al cuerpo docente y es el referente en la toma de decisiones técnicas importantes. Participa activamente en el desarrollo del sistema, principalmente en la arquitectura y el frontend.

**Actividades principales:**
- Presidir las reuniones del grupo y redactar las actas.
- Asignar tareas y verificar su cumplimiento.
- Mantener actualizado el cronograma (Gantt).
- Verificar que el repositorio Git esté organizado.
- Presentar el proyecto en las instancias formales.
- Resolver conflictos internos o escalarlos al docente.

---

### Rol 2 – Desarrollador Backend / Base de Datos

**Responsable:** [Integrante 2]

**Descripción:**
Diseña e implementa la capa de servidor del sistema: estructura de base de datos MySQL, modelos PHP (MVC), controladores y rutas. Es responsable de la integridad y consistencia de los datos.

**Actividades principales:**
- Diseñar el modelo entidad-relación y el modelo relacional.
- Implementar la base de datos en MySQL.
- Desarrollar los modelos PHP siguiendo el patrón MVC.
- Implementar las rutas del controlador.
- Validar datos en el backend (sanitización, prepared statements).
- Gestionar la autenticación y sesiones PHP.

---

### Rol 3 – Desarrollador Frontend / UI-UX

**Responsable:** [Integrante 3]

**Descripción:**
Diseña e implementa la interfaz de usuario. Es responsable de la experiencia visual, la accesibilidad, el diseño responsivo y la integración del frontend con el backend.

**Actividades principales:**
- Crear mockups y wireframes de las vistas del sistema.
- Implementar el diseño en HTML, CSS y JavaScript (Vanilla JS).
- Asegurar diseño responsivo Mobile First.
- Integrar vistas con los datos del backend PHP.
- Mantener el sistema de diseño (variables CSS, componentes).
- Validar formularios en el frontend.

---

### Rol 4 – Administrador de Sistemas / Seguridad

**Responsable:** [Integrante 4]

**Descripción:**
Gestiona la infraestructura del proyecto: servidor web, configuración de servicios, scripts de administración, Docker y medidas de ciberseguridad. Asegura que el sistema sea desplegable y operativamente seguro.

**Actividades principales:**
- Configurar el servidor Apache/Nginx.
- Desarrollar scripts Bash para administración del sistema.
- Configurar Docker y docker-compose para el despliegue.
- Implementar controles de ciberseguridad (OWASP Top 10).
- Definir políticas de contraseñas y acceso.
- Documentar la infraestructura técnica.

---

## Roles secundarios (compartidos)

| Rol secundario | Responsable principal | Descripción |
|----------------|----------------------|-------------|
| Documentador técnico | Todos (rotativo) | Redacción de docs técnicos y manuales |
| Responsable de pruebas | [Integrante 2 o 3] | Ejecutar y documentar casos de prueba |
| Redactor de actas | [Integrante 1] | Redacción de actas de reunión |
| Responsable de presentación | Rotativo por entrega | Exponer el trabajo ante los docentes |

---

## Matriz de responsabilidades (RACI simplificada)

| Actividad | Líder | Backend | Frontend | Sistemas |
|-----------|-------|---------|----------|----------|
| Análisis de requisitos | R | C | C | C |
| Diseño de BD | C | R | I | I |
| Maquetado HTML/CSS | I | I | R | I |
| Implementación PHP | C | R | I | I |
| Scripts Bash | I | I | I | R |
| Documentación técnica | A | C | C | C |
| Pruebas | A | R | R | C |
| Configuración Docker | C | C | I | R |
| Defensa del proyecto | R | C | C | C |

**R** = Responsable | **A** = Aprobador | **C** = Consultado | **I** = Informado

---

*Instituto Tecnológico Superior "Arias-Balparda" | BT Tecnologías de la Información | 2026*
