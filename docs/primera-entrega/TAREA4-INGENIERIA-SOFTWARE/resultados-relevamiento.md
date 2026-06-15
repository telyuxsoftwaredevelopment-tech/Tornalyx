# Resultados del Relevamiento de Datos
## Tornalyx SGDM | Ingeniería de Software – Primera Entrega

---

## Parte 1 – Resultados de Entrevistas Semi-Estructuradas

### Resumen de entrevistas realizadas

| N° | Perfil entrevistado | Fecha | Duración |
|----|--------------------|----|---------|
| E1 | Organizador de liga de fútbol amateur (12 equipos) | ___/06/2026 | 35 min |
| E2 | Organizador de torneo de ajedrez escolar | ___/06/2026 | 28 min |
| E3 | Docente que organiza competencias internas | ___/06/2026 | 22 min |
| E4 | Participante de torneos de esports (League of Legends) | ___/06/2026 | 30 min |

---

### Transcripciones resumidas

**Entrevista E1 – Organizador de liga de fútbol amateur**

> "Uso Excel para todo. Tengo una planilla con los partidos, los goles, las tarjetas, la tabla de posiciones. El problema es que cuando actualizo un resultado tengo que tocar 4 cosas distintas y a veces me olvido de alguna. Más de una vez publiqué una tabla mal y los capitanes me llamaron enojados."

> "Lo que más me falta es algo que genere el calendario automático. Hacer todos contra todos a mano para 12 equipos con cancha fija los sábados es un dolor de cabeza."

> "Si tuviese una app así, lo primero que necesito es que se pueda ver desde el celular. Los jugadores no van a abrir una computadora para ver si juegan el sábado."

**Hallazgos clave E1:** Problema de consistencia de datos (múltiples fuentes), necesidad de generación automática de fixtures, acceso móvil fundamental.

---

**Entrevista E2 – Organizador de torneo de ajedrez escolar**

> "En ajedrez usamos el sistema suizo, que es bastante particular. Nadie tiene planillas para eso. Uso un programa viejo de Windows que ni siquiera funciona bien en Windows 11."

> "Me gustaría poder publicar los resultados de cada ronda online para que los chicos puedan ver cómo van sin tener que preguntarme a mí."

> "Lo de los roles también me parece importante. Que el árbitro pueda cargar resultados pero no pueda cambiar la configuración del torneo."

**Hallazgos clave E2:** Soporte específico para sistema suizo, necesidad de publicación pública de resultados, sistema de roles con permisos diferenciados.

---

**Entrevista E3 – Docente organizador**

> "Yo hago torneos internos entre cursos: fútbol sala, ajedrez, juegos de mesa. No son torneos grandes, pero igual es complicado manejar quién juega contra quién."

> "El tema principal para mí es la simplicidad. Tiene que ser algo que cualquier docente pueda usar sin capacitación. Si es complicado, no lo van a usar."

> "Estaría bueno que los alumnos puedan inscribirse solos online y que yo solo tenga que aprobar."

**Hallazgos clave E3:** Simplicidad de interfaz prioritaria, auto-inscripción de participantes, aprobación por parte del organizador.

---

**Entrevista E4 – Participante de esports**

> "En esports usamos Challonge o Toornament. Están bien pero son en inglés y a veces se cuelgan. Estaría bueno tener algo parecido en español."

> "Lo que más valoro como participante es poder ver mi historial: cuántos torneos jugué, cómo me fue, contra quién jugué. Algo tipo perfil de jugador."

> "Las notificaciones son importantes. Que me llegue un aviso cuando se publique mi próximo partido o cuando actualicen un resultado."

**Hallazgos clave E4:** Referencia a Challonge/Toornament como competidores, perfil de jugador con historial, notificaciones automáticas (funcionalidad opcional).

---

### Conclusiones de las entrevistas

1. La gestión manual con planillas genera errores frecuentes y frustración.
2. La generación automática de fixtures es la funcionalidad más demandada.
3. El acceso móvil (diseño responsive) es indispensable.
4. El sistema suizo requiere soporte específico (no es trivial).
5. Los roles y permisos diferenciados son necesarios desde el inicio.
6. La simplicidad de la interfaz es crítica para la adopción.
7. El perfil de jugador con historial es muy valorado por los participantes.

---

## Parte 2 – Resultados de Cuestionario en Línea

**Total de respuestas:** [Completar con respuestas reales]
**Período de recolección:** ___/06/2026 al ___/06/2026

---

### Pregunta 1 – Relación con torneos

| Opción | Respuestas | % |
|--------|------------|---|
| Organizo torneos | [N] | [%] |
| Participo como jugador | [N] | [%] |
| Espectador | [N] | [%] |
| Docente organizador | [N] | [%] |
| Otro | [N] | [%] |

### Pregunta 4 – Herramientas actuales

| Herramienta | Respuestas | % |
|-------------|------------|---|
| Excel / Google Sheets | [N] | [%] |
| Papel | [N] | [%] |
| App específica | [N] | [%] |
| WhatsApp / RRSS | [N] | [%] |
| Sin sistema definido | [N] | [%] |

### Pregunta 5 – Satisfacción con herramientas actuales

**Promedio:** [X.X] / 5

### Pregunta 6 – Funcionalidades más importantes (top 3)

| Funcionalidad | Votos | % |
|---------------|-------|---|
| Generación automática de partidos | [N] | [%] |
| Resultados en tiempo real | [N] | [%] |
| Tabla de posiciones automática | [N] | [%] |
| Inscripción online | [N] | [%] |
| Acceso desde celular | [N] | [%] |
| Historial y estadísticas | [N] | [%] |
| Notificaciones | [N] | [%] |
| Panel organizador | [N] | [%] |

### Pregunta 7 – Dispositivo preferido

| Dispositivo | Respuestas | % |
|-------------|------------|---|
| Principalmente celular | [N] | [%] |
| Principalmente computadora | [N] | [%] |
| Ambos indistintamente | [N] | [%] |

### Pregunta 8 – Importancia de facilidad de uso

**Promedio:** [X.X] / 5

---

### Respuestas abiertas destacadas (Pregunta 9)

> "[Completar con respuestas reales del formulario]"

> "[...]"

> "[...]"

---

## Conclusiones generales del relevamiento

A partir de ambas técnicas se identifican los siguientes **requisitos prioritarios** para el SGDM:

| Prioridad | Requisito | Fuente |
|-----------|-----------|--------|
| Alta | Generación automática de fixtures (liga, eliminación directa, suizo) | E1, E2, Encuesta |
| Alta | Interfaz responsiva (Mobile First) | E1, E4, Encuesta |
| Alta | Tabla de posiciones actualizada automáticamente | E1, E3, Encuesta |
| Alta | Registro de resultados por organizador | E1, E2, E3 |
| Alta | Sistema de roles con permisos diferenciados | E2, E3 |
| Media | Perfil del participante con historial | E4, Encuesta |
| Media | Inscripción online de participantes | E3, Encuesta |
| Media | Vista pública de torneos sin login | E2, E4 |
| Baja | Notificaciones automáticas | E4, Encuesta |

---

*Instituto Tecnológico Superior "Arias-Balparda" | BT Tecnologías de la Información | 2026*
