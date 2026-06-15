# Documento ESRE – Especificación de Requerimientos de Software
## IEEE 29148 | Tornalyx SGDM | Primera Entrega

**Proyecto:** Tornalyx – Sistema de Gestión de Deportes y Modalidades (SGDM)
**Versión del documento:** 1.0
**Fecha:** Junio 2026
**Estado:** Borrador preliminar – Primera Entrega

---

## 1. Propósito

El presente documento tiene como propósito describir los requerimientos de software del sistema **Tornalyx SGDM**, una aplicación web destinada a la gestión integral de torneos, competencias y eventos deportivos. Esta especificación establece la base de entendimiento común entre el equipo de desarrollo, los docentes evaluadores y los potenciales usuarios del sistema.

El documento sigue las directrices de la norma **IEEE 29148:2018** (*Systems and Software Engineering — Life Cycle Processes — Requirements Engineering*) y constituye el artefacto central de la fase de análisis de la primera entrega del proyecto.

La especificación detalla:
- El contexto y la justificación del sistema.
- El alcance de las funcionalidades a desarrollar en las tres entregas del ciclo incremental.
- Las definiciones técnicas y terminológicas aplicables.
- Los perfiles de usuario y sus niveles de acceso al sistema.

---

## 2. Alcance

### 2.1 Nombre del sistema
**Tornalyx SGDM** (Sistema de Gestión de Deportes y Modalidades)

### 2.2 Descripción general
Tornalyx es una plataforma web que permite a organizadores, participantes y administradores gestionar torneos y competencias deportivas (o de otro tipo) de manera centralizada y automatizada. El sistema elimina la dependencia de planillas manuales y canales informales, proporcionando un único punto de verdad para la información de cada torneo.

### 2.3 Objetivos del sistema
1. Permitir la creación y configuración de torneos con distintos formatos de competencia.
2. Automatizar la generación de fixtures (calendario de partidos).
3. Permitir el registro de resultados y actualizar automáticamente la tabla de posiciones.
4. Dar visibilidad pública a los torneos y sus resultados.
5. Gestionar la inscripción de participantes y equipos.
6. Proveer paneles de control diferenciados según el rol del usuario.

### 2.4 Límites del sistema (qué NO incluye)
- El sistema no procesará pagos ni aranceles de inscripción.
- No incluirá comunicación en tiempo real por chat entre usuarios.
- No integrará redes sociales de manera automatizada.
- No gestionará infraestructura física (canchas, horarios de instalaciones deportivas).

### 2.5 Alcance por entrega (ciclo incremental)

| Entrega | Incremento | Contenido |
|---------|-----------|-----------|
| Primera | Incremento 1 | Relevamiento, documentación técnica, maquetado HTML/CSS/JS, wireframes |
| Segunda | Incremento 2 | Backend PHP, base de datos MySQL, autenticación, CRUD de torneos y participantes |
| Tercera | Incremento 3 | Generación de fixtures, tabla de posiciones, historial, despliegue Docker |

---

## 3. Definiciones, Acrónimos y Abreviaciones

### 3.1 Definiciones

| Término | Definición |
|---------|-----------|
| Torneo | Competencia organizada entre dos o más participantes o equipos con un conjunto de reglas, formato y sistema de clasificación definidos. |
| Fixture | Calendario de partidos generado automáticamente a partir de los participantes inscriptos en un torneo. |
| Tabla de posiciones | Clasificación de participantes o equipos según su desempeño acumulado en el torneo. |
| Sistema de liga | Formato de torneo donde todos los participantes se enfrentan entre sí (todos contra todos). La posición final se determina por puntos acumulados. |
| Sistema de eliminación directa (knockout) | Formato donde un participante queda eliminado tras perder un partido. |
| Sistema suizo | Formato en el que los participantes no son eliminados; en cada ronda se enfrentan quienes tienen puntuaciones similares. |
| Inscripción | Proceso por el cual un participante o equipo solicita y obtiene lugar en un torneo. |
| Resultado | Datos del desenlace de un partido: marcador, ganador, puntos, etc. |
| Dashboard | Panel de control de usuario con información y acciones disponibles según su rol. |
| Sesión | Período de acceso autenticado de un usuario al sistema. |
| MVC | Patrón de diseño Modelo-Vista-Controlador. Separa la lógica de negocio (Modelo), la presentación (Vista) y el control de flujo (Controlador). |

### 3.2 Acrónimos

| Acrónimo | Significado |
|----------|-------------|
| SGDM | Sistema de Gestión de Deportes y Modalidades |
| MVC | Modelo-Vista-Controlador |
| HTML | HyperText Markup Language |
| CSS | Cascading Style Sheets |
| JS | JavaScript |
| PHP | PHP: Hypertext Preprocessor |
| MySQL | My Structured Query Language |
| BD / DB | Base de datos / Database |
| CRUD | Create, Read, Update, Delete |
| UI | User Interface (Interfaz de Usuario) |
| UX | User Experience (Experiencia de Usuario) |
| API | Application Programming Interface |
| IEEE | Institute of Electrical and Electronics Engineers |
| ESRE | Especificación de Software de Requisitos del Sistema |
| BT | Bachillerato Técnico |
| OWASP | Open Web Application Security Project |
| SQL | Structured Query Language |
| XSS | Cross-Site Scripting (tipo de vulnerabilidad web) |
| CSRF | Cross-Site Request Forgery (tipo de vulnerabilidad web) |

### 3.3 Abreviaciones

| Abreviación | Significado |
|-------------|-------------|
| Org. | Organizador |
| Adm. | Administrador |
| Part. | Participante |
| s/l | sin login (acceso público) |
| R/W | Lectura y escritura (Read/Write) |
| R | Solo lectura (Read-only) |
| N/A | No aplica |

---

## 4. Limitaciones del Sistema

### 4.1 Limitaciones técnicas

1. **Lenguajes definidos por cátedra:** El sistema debe desarrollarse obligatoriamente en HTML5, CSS3 y JavaScript (Vanilla) para el frontend, y PHP con MySQL para el backend, sin frameworks adicionales de JavaScript ni PHP.

2. **Sin tiempo real (WebSockets):** La aplicación no implementará comunicación en tiempo real mediante WebSockets. Las actualizaciones de resultados serán visibles al recargar la página.

3. **Entorno de desarrollo local:** El sistema se desarrolla y prueba inicialmente en entorno local (XAMPP / WAMP / Docker). El despliegue en servidor remoto es parte del tercer incremento.

4. **Compatibilidad de navegadores:** Se garantiza compatibilidad con las versiones actuales de Chrome, Firefox y Edge. No se garantiza compatibilidad con Internet Explorer.

### 4.2 Limitaciones del proyecto

5. **Equipo pequeño:** El equipo de desarrollo consta de 4 integrantes con dedicación parcial (estudiantes). Las funcionalidades avanzadas (notificaciones, analytics, exportación de datos) quedan fuera del alcance de las tres entregas.

6. **Sin usuarios reales en primera entrega:** En la primera entrega el sistema no estará conectado a una base de datos real. Las interfaces presentadas son maquetas funcionales en HTML/CSS/JS.

7. **Sin procesamiento de imágenes:** El sistema aceptará URLs de imágenes para logos/fotos pero no gestionará la carga directa de archivos de imagen en la primera y segunda entrega.

### 4.3 Limitaciones de seguridad (primera entrega)

8. En la primera entrega no se implementa autenticación real. Se presenta únicamente la interfaz de login y registro como maqueta funcional con validaciones JavaScript en el lado cliente.

---

## 5. Identificación de Roles y Perfiles de Usuario

### 5.1 Descripción de roles

El sistema define cuatro perfiles de usuario con distintos niveles de acceso y responsabilidades:

---

#### Perfil 1 – Visitante (sin registro)

**Descripción:** Usuario anónimo que accede al sistema sin autenticarse. Solo puede consultar información pública.

**Características:**
- No requiere cuenta ni contraseña.
- Accede desde cualquier dispositivo.
- No puede modificar ningún dato.

---

#### Perfil 2 – Participante

**Descripción:** Usuario registrado que participa en torneos como jugador individual o como integrante de un equipo. Puede gestionar su propio perfil e inscribirse en torneos.

**Características:**
- Requiere cuenta con email y contraseña.
- Puede ser jugador individual o capitán de equipo.
- Solo puede modificar su propio perfil y sus equipos.

---

#### Perfil 3 – Organizador

**Descripción:** Usuario registrado con permisos para crear y gestionar torneos. Es el responsable de configurar el formato, inscribir participantes, registrar resultados y publicar información.

**Características:**
- Requiere cuenta verificada.
- Puede delegar la carga de resultados a un árbitro (funcionalidad de incremento 2).
- No puede acceder a la administración global del sistema.

---

#### Perfil 4 – Administrador

**Descripción:** Superusuario del sistema con acceso total. Gestiona cuentas de usuario, modera contenido, configura el sistema y tiene visibilidad sobre toda la información.

**Características:**
- Cuenta creada directamente en la base de datos (no por registro público).
- Accede a un panel de administración exclusivo.
- Puede suspender, editar o eliminar cualquier entidad del sistema.

---

### 5.2 Tabla de perfiles y niveles de acceso

| Módulo / Funcionalidad | Visitante | Participante | Organizador | Administrador |
|------------------------|-----------|--------------|-------------|---------------|
| **Ver listado de torneos públicos** | R | R | R | R/W |
| **Ver detalle de torneo** | R | R | R | R/W |
| **Ver tabla de posiciones** | R | R | R | R/W |
| **Ver resultados de partidos** | R | R | R | R/W |
| **Registrarse en el sistema** | ✅ | N/A | N/A | N/A |
| **Iniciar sesión** | N/A | ✅ | ✅ | ✅ |
| **Ver y editar perfil propio** | N/A | R/W | R/W | R/W |
| **Inscribirse en un torneo** | N/A | ✅ | N/A | ✅ |
| **Crear y gestionar equipo** | N/A | R/W | N/A | R/W |
| **Ver historial de participaciones** | N/A | R (propio) | R/W | R/W |
| **Crear torneo** | N/A | N/A | ✅ | ✅ |
| **Configurar formato de torneo** | N/A | N/A | R/W | R/W |
| **Gestionar inscripciones al torneo** | N/A | N/A | R/W | R/W |
| **Generar fixture del torneo** | N/A | N/A | ✅ | ✅ |
| **Registrar resultados de partidos** | N/A | N/A | R/W | R/W |
| **Actualizar/publicar torneo** | N/A | N/A | R/W (propio) | R/W |
| **Eliminar torneo** | N/A | N/A | R/W (propio) | R/W |
| **Gestionar usuarios del sistema** | N/A | N/A | N/A | R/W |
| **Suspender/activar cuentas** | N/A | N/A | N/A | ✅ |
| **Ver logs y estadísticas globales** | N/A | N/A | N/A | R |
| **Configuración global del sistema** | N/A | N/A | N/A | R/W |

**R** = Solo lectura | **R/W** = Lectura y escritura | **✅** = Acción permitida | **N/A** = No aplica

---

### 5.3 Resumen de niveles de acceso

| Perfil | Nivel | Requiere login | Ámbito de acción |
|--------|-------|---------------|-----------------|
| Visitante | Público | No | Solo lectura, información pública |
| Participante | Básico | Sí | Propio perfil, inscripciones, equipos propios |
| Organizador | Intermedio | Sí | Torneos propios: creación, fixture, resultados |
| Administrador | Total | Sí | Todo el sistema sin restricciones |

---

*Instituto Tecnológico Superior "Arias-Balparda" | BT Tecnologías de la Información | 2026*
*Norma de referencia: IEEE 29148:2018 – Systems and Software Engineering — Life Cycle Processes — Requirements Engineering*
