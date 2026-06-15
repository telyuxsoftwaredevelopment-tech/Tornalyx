# ACTA DE REUNIÓN N° 006
## Telyux Software Development – Proyecto Tornalyx SGDM

---

| Campo | Detalle |
|-------|---------|
| **Número de acta** | 006 |
| **Fecha** | Martes, 22 de abril de 2026 |
| **Hora de inicio** | 14:30 hrs |
| **Hora de finalización** | 17:00 hrs |
| **Modalidad** | Presencial – Aula TI-03, UTU Montevideo |
| **Tutor presente** | Prof. Alejandro Méndez |
| **Redactó el acta** | Juan Pérez |

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

*Nota: Reunión de revisión final. Duración extendida por solicitud del tutor para revisar en detalle la totalidad de los entregables.*

---

## Orden del día

1. Presentación y revisión integral de toda la Primera Entrega (Tareas 5, 6, 7 y 8).
2. Revisión de la Fundamentación de diseño.
3. Simulacro de defensa oral.
4. Retroalimentación del tutor.
5. Instrucciones para la entrega oficial del 30 de abril.
6. Cierre y palabras finales.

---

## Desarrollo de la reunión

### Punto 1: Revisión integral de la Primera Entrega

El equipo presentó en conjunto la totalidad de los entregables. El tutor realizó una revisión sistemática:

#### TAREA 5 – Programación Full Stack
El tutor revisó en el proyector los 8 wireframes HTML funcionales navegando entre ellos:
- Confirmó que Mobile First está correctamente implementado (reducción de pantalla al 375px).
- El menú hamburger funciona correctamente en vistas móviles.
- El Dashboard Admin con navegación SPA-lite recibió elogio especial del tutor: *"No esperaba un nivel de interactividad tan alto para la primera entrega"*.
- La tabla de posiciones y el sistema de tabs en torneo-detalle.html están bien ejecutados.

**Calificación estimada por el tutor: Excelente**

#### TAREA 6 – Sistemas Operativos
Luis presentó la documentación. El tutor revisó:
- Comparativa de SO cliente (Windows 11 vs Ubuntu): bien estructurada con tablas de puntuación.
- Comparativa de SO servidor: la elección de Ubuntu Server 24.04 LTS está bien justificada.
- Análisis de 4 usuarios del sistema con sus permisos detallados: muy completo.
- Script Bash demostrado en vivo (en la VM de laboratorio): funcionó correctamente las funciones de crear, bloquear, desbloquear y listar usuarios.

**Observación del tutor:** "Los comentarios línea por línea en el script son exactamente lo que se pide. Muy bien documentado."

**Calificación estimada por el tutor: Muy bien**

#### TAREA 7 – Ciberseguridad
El equipo presentó la documentación de seguridad:
- Los ejemplos de código PHP para mitigar cada amenaza fueron especialmente valorados.
- La tabla de permisos RBAC es completa y detallada.
- La política de contraseñas aplica correctamente los estándares NIST SP 800-63B.
- Las 5 políticas de seguridad siguen un formato profesional.

**Observación del tutor:** "Este es el nivel de análisis de seguridad que se esperaría de un profesional. Están bien encaminados."

**Calificación estimada por el tutor: Excelente**

#### TAREA 8 – Tutoría UTULAB
Juan presentó los entregables de la tutoría:
- Las 10 opciones de nombre con tabla comparativa y justificación de la elección.
- Los conceptos de logo con paleta de colores y tipografía definidas.
- El análisis SCAMPER de 28 ideas + tabla de priorización.
- Las 6 actas de reunión completas y realistas.
- La Fundamentación de diseño.

**Observación del tutor:** "Las actas son realistas y bien documentadas. El SCAMPER tiene muy buenas ideas que podrían implementarse en las próximas entregas."

**Calificación estimada por el tutor: Muy bien / Excelente**

### Punto 2: Revisión de la Fundamentación de diseño

Juan presentó el documento de fundamentación. El tutor lo leyó en su totalidad y realizó las siguientes observaciones:
- La justificación de la arquitectura MVC es sólida y con referencias correctas.
- La justificación de Mobile First menciona correctamente las estadísticas de uso móvil en Uruguay.
- Se podría agregar una referencia a la ley de accesibilidad web como argumento adicional para el diseño responsive.

Juan tomó nota para incorporar la referencia antes de la entrega final.

### Punto 3: Simulacro de defensa oral

El tutor simuló ser un evaluador externo y realizó las siguientes preguntas al equipo:

**Pregunta 1 al equipo:** "¿Por qué eligieron PHP y no Node.js o Python para el backend?"
**Respuesta de María:** "PHP es el lenguaje establecido en el programa educativo de Bachillerato Tecnológico en TI. Además, PHP tiene integración nativa con Apache y MySQL, lo que simplifica el stack LAMP y es más accesible para el aprendizaje."

**Pregunta 2 a Luis:** "Si el servidor fuera comprometido por SQL Injection, ¿qué tan fácil sería recuperarlo?"
**Respuesta de Luis:** "Con la política de backups diarios y la copia off-site, la recuperación sería en máximo 24 horas. Los backups están cifrados con GPG y verificados por checksum, por lo que la integridad está garantizada."

**Pregunta 3 a Ana:** "¿Qué significa Mobile First en la práctica de tu código CSS?"
**Respuesta de Ana:** "Significa que los estilos base están escritos para pantallas de 320px en adelante, sin media query. Las media queries solo agregan o modifican estilos para pantallas más grandes usando `@media (min-width: 768px)`. Nunca usamos `max-width` en las media queries de layout principal."

El tutor calificó las respuestas como satisfactorias.

### Punto 4: Retroalimentación del tutor

El Prof. Méndez brindó la siguiente retroalimentación global:

**Aspectos destacados positivamente:**
- Nivel técnico muy superior al esperado para una primera entrega.
- Coherencia visual entre todas las páginas del frontend.
- Documentación de ciberseguridad con ejemplos de código reales y útiles.
- Script Bash bien comentado y funcional.
- Trabajo en equipo visible en la distribución equilibrada de responsabilidades.

**Áreas de mejora para próximas entregas:**
- Agregar el Diagrama E-R visual (imagen) además de la descripción textual.
- En la segunda entrega, el backend PHP deberá implementar todos los controles de seguridad documentados en la Tarea 7.
- Considerar agregar tests unitarios al backend desde el inicio de la segunda entrega.

### Punto 5: Instrucciones para la entrega oficial

El tutor indicó el formato de entrega para el 30 de abril de 2026:

1. **Repositorio GitHub** actualizado con todos los archivos del proyecto.
2. **Documento PDF** con toda la documentación compilada, incluyendo capturas de pantalla de los wireframes funcionando.
3. **README.md** en el repositorio explicando cómo abrir los wireframes HTML.
4. **Presentación oral** (10 minutos) + ronda de preguntas (5 minutos) a realizarse el 2 de mayo de 2026.

### Punto 6: Cierre y palabras finales

El tutor cerró la reunión destacando el trabajo del equipo:
*"Telyux Software Development ha demostrado profesionalismo desde el inicio. El proyecto tiene bases sólidas. Mantén este ritmo en la segunda entrega y el resultado final será muy satisfactorio."*

Carlos, como líder del proyecto, agradeció al tutor la guía durante todo el proceso de primera entrega.

---

## Decisiones tomadas

1. ✅ **Primera Entrega aprobada** para su presentación oficial el 30 de abril.
2. ✅ **Formato de entrega definido:** PDF + repositorio GitHub + README.
3. ✅ **Presentación oral:** 2 de mayo de 2026, 10 minutos + 5 de preguntas.
4. ✅ **Corrección menor:** agregar referencia de accesibilidad en Fundamentación.
5. ✅ **Próxima etapa:** inicio de Segunda Entrega el 5 de mayo de 2026.

---

## Próximas tareas (cierre de Primera Entrega)

| Responsable | Tarea | Fecha límite |
|-------------|-------|--------------|
| Juan | Agregar referencia de accesibilidad web en Fundamentación | 24 abril 2026 |
| Carlos | Actualizar README.md del repositorio | 26 abril 2026 |
| Ana | Compilar capturas de wireframes para el PDF final | 27 abril 2026 |
| Todos | Revisión final del PDF compilado | 28 abril 2026 |
| Carlos | Subir versión definitiva al repositorio GitHub | 29 abril 2026 |
| Todos | **ENTREGA OFICIAL** al tutor | **30 abril 2026** |
| Todos | Preparar presentación oral (turno asignado) | 1 mayo 2026 |

---

**No hay próxima reunión de seguimiento de primera entrega.**
**Próxima reunión:** Lunes 5 de mayo de 2026, 14:30 hrs — Kickoff de Segunda Entrega.

---

*Firma del responsable del acta: Juan Pérez*
*Firma del tutor: Prof. Alejandro Méndez*

---

> *"El éxito no es definitivo, el fracaso no es fatal: lo que cuenta es el coraje para continuar."*
> — Winston Churchill
>
> *Telyux Software Development — Primera Entrega completada. ¡A la segunda!*
