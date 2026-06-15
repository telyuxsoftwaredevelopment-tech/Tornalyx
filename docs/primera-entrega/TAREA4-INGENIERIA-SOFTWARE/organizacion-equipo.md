# Organización del Equipo de Proyecto
## Tornalyx SGDM | Ingeniería de Software – Primera Entrega

---

## Estructura según Mantei y Constantine

### Modelo de Mantei elegido: **Controlado Descentralizado (CD)**

Se optó por la estructura **Controlada Descentralizada** propuesta por Meilir Page-Jones y popularizada por Mantei (1981), en la cual:

- Existe un **líder de proyecto** que coordina y toma decisiones técnicas clave.
- El equipo trabaja de forma **semi-autónoma** en módulos asignados.
- Las decisiones de diseño se toman en conjunto, pero la coordinación final recae en el líder.
- La comunicación fluye tanto horizontal (entre miembros) como vertical (hacia el líder).

Esta estructura es la más adecuada para equipos pequeños (3-4 personas) con proyectos de mediano porte y tiempo acotado, como es el caso del SGDM.

---

## Justificación de la elección

| Criterio | Razón |
|---|---|
| Tamaño del equipo | 3-4 integrantes → estructura simple, sin jerarquías innecesarias |
| Complejidad del proyecto | Mediana → requiere coordinación pero no burocracia |
| Tiempo disponible | Acotado (3 entregas en ~5 meses) → decisiones ágiles |
| Nivel de experiencia | Similar entre los integrantes → permite trabajo paralelo |
| Modularidad del sistema | Alta → cada integrante puede trabajar en un módulo independiente |

Se descartó la estructura **Centralizada** (jerárquica) porque implica dependencia de un único punto de control, lo que frena el avance cuando el líder no está disponible. También se descartó la **Descentralizada pura** (sin líder) porque para un proyecto académico con entregas formales se necesita un responsable de coordinación y presentación.

---

## Estructura según Constantine

Se aplica también el modelo de **grupos pragmáticos** de Larry Constantine, que propone estructuras según la dinámica de trabajo:

**Tipo elegido: Grupo abierto (Open Team)**

Características:
- Alta comunicación entre todos los miembros.
- Roles flexibles que pueden rotar según la etapa del proyecto.
- Consenso en decisiones importantes.
- Adaptabilidad ante cambios de requisitos.

Este modelo es compatible con la naturaleza iterativa e incremental del ciclo de vida elegido.

---

## Integrantes del equipo

| N° | Nombre | Rol principal | Módulos asignados |
|----|--------|---------------|-------------------|
| 1  | [Integrante 1 – Líder] | Líder de proyecto / Fullstack | Frontend (index, torneos), Arquitectura MVC |
| 2  | [Integrante 2] | Backend / Base de datos | Módulos de liga y suizo, API PHP |
| 3  | [Integrante 3] | Frontend / UI-UX | Dashboards, componentes, CSS |
| 4  | [Integrante 4] | Seguridad / Sistemas | Scripts bash, Docker, Ciberseguridad |

> Los nombres se completan con los datos reales del grupo según la nota de conformación entregada al docente de Tutoría.

---

## Diagrama de la estructura

```
          ┌──────────────────────────┐
          │    LÍDER DE PROYECTO     │
          │   (Coordinación general) │
          └────────────┬─────────────┘
                       │
          ┌────────────┼────────────┐
          │            │            │
    ┌─────┴────┐ ┌─────┴────┐ ┌────┴─────┐
    │ Backend  │ │ Frontend │ │ Sistemas │
    │ + BD     │ │ + UI/UX  │ │ + Seg.   │
    └──────────┘ └──────────┘ └──────────┘

Comunicación horizontal entre todos los miembros (reuniones semanales)
```

---

*Documento elaborado para la Primera Entrega del Proyecto SGDM – Tornalyx*
*Instituto Tecnológico Superior "Arias-Balparda" | BT Tecnologías de la Información | 2026*
