# ACTA DE REUNIÓN N° 002
## Telyux Software Development – Proyecto Tornalyx SGDM

---

| Campo | Detalle |
|-------|---------|
| **Número de acta** | 002 |
| **Fecha** | Martes, 17 de marzo de 2026 |
| **Hora de inicio** | 14:30 hrs |
| **Hora de finalización** | 15:45 hrs |
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
| Luis Rodríguez | ⚠️ Tardanza (llegó a las 14:50) |
| Prof. Alejandro Méndez | ✅ |

---

## Orden del día

1. Seguimiento de tareas asignadas en reunión anterior.
2. Presentación y validación del Sitemap por Ana Sosa.
3. Discusión sobre tecnologías del frontend (Mobile First, CSS sin framework).
4. Revisión del modelo entidad-relación preliminar.
5. Próximos pasos.

---

## Desarrollo de la reunión

### Punto 1: Seguimiento de tareas

Se revisó el cumplimiento de las tareas asignadas en la reunión anterior:

| Tarea | Estado | Comentario |
|-------|--------|-----------|
| Crear repositorio en GitHub | ✅ Completada | Carlos creó `telyux-dev/tornalyx` en GitHub |
| Boceto inicial sitemap | ✅ Completada | Ana presentó el sitemap (ver Punto 2) |
| Investigación Tarea 8 | ⚠️ En progreso | Juan tiene el 60% de SCAMPER |
| Comparativas de SO | ⚠️ En progreso | Luis inició la investigación |
| Modelo de BD borrador | ✅ Completada | María presentó diseño preliminar |
| Leer enunciado completo | ✅ Completada | Todos confirmaron |

### Punto 2: Presentación del Sitemap

Ana Sosa presentó el sitemap inicial del sistema Tornalyx. Se identificaron 8 páginas principales:
- Zona pública: Inicio, Buscar torneos, Detalle torneo, Login, Registro.
- Zona privada: Dashboard Admin, Dashboard Organizador, Perfil Participante.

**Observaciones del tutor:** El Prof. Méndez sugirió asegurarse de que la consulta pública de resultados sea accesible sin login, para cumplir el requisito de "Consulta pública" del enunciado. Ana incorporará esta observación.

**Decisión:** Sitemap aprobado con la corrección mencionada.

### Punto 3: Tecnologías del Frontend

Se debatió si usar Bootstrap o Tailwind CSS para los estilos. La discusión central:
- **A favor de Bootstrap:** mayor velocidad de desarrollo inicial.
- **En contra:** el proyecto debe demostrar dominio propio de CSS.
- **Propuesta de Ana:** CSS puro con Mobile First, usando variables CSS (custom properties) para consistencia.

**Decisión del tutor:** el proyecto debe usar CSS sin frameworks externos para demostrar competencia en los fundamentos. Se puede usar Google Fonts para tipografía.

### Punto 4: Modelo Entidad-Relación

María presentó un modelo preliminar con las siguientes entidades:
`usuarios`, `torneos`, `equipos`, `participantes`, `resultados`, `partidos`, `inscripciones`.

El tutor indicó que el modelo está bien orientado y pidió agregar la entidad `categorias` para los tipos de deporte/disciplina.

---

## Decisiones tomadas

1. ✅ **Sitemap aprobado** con corrección de acceso público a resultados.
2. ✅ **CSS puro con Mobile First** — sin frameworks de CSS externos.
3. ✅ **Modelo E-R validado** — agregar entidad `categorias`.
4. ✅ **Repositorio GitHub activo** en `telyux-dev/tornalyx`.

---

## Próximas tareas

| Responsable | Tarea | Fecha límite |
|-------------|-------|--------------|
| Ana + Carlos | Iniciar wireframes HTML de páginas públicas | 24 marzo 2026 |
| María | Añadir entidad `categorias` al modelo E-R y subir a GitHub | 20 marzo 2026 |
| Luis | Terminar comparativa de SO cliente | 24 marzo 2026 |
| Juan | Completar análisis SCAMPER | 24 marzo 2026 |
| Todos | Revisar normativa de la primera entrega del tutor | 19 marzo 2026 |

---

**Próxima reunión:** Martes 24 de marzo de 2026, 14:30 hrs, Aula TI-03.

---

*Firma del responsable del acta: Juan Pérez*
*Firma del tutor: Prof. Alejandro Méndez*
