# Ciclo de Vida del Proyecto
## Tornalyx SGDM | Ingeniería de Software – Primera Entrega

---

## Ciclo de vida elegido: **Incremental e Iterativo**

### Descripción

El ciclo de vida **Incremental e Iterativo** divide el proyecto en incrementos (entregas parciales) que agregan funcionalidad al sistema de forma progresiva. Cada incremento pasa por las fases de análisis, diseño, implementación y prueba antes de integrarse al producto. Las iteraciones permiten refinar y corregir el trabajo de ciclos anteriores.

### Fundamentación de la elección

Se eligió este modelo por las siguientes razones:

**1. Compatibilidad con las entregas del proyecto**
El proyecto académico tiene tres entregas formales (27/07, 14/09 y 09/11). Esta estructura se alinea naturalmente con tres incrementos del sistema:
- **Incremento 1** (Primera entrega): Análisis, diseño y maquetado del frontend completo.
- **Incremento 2** (Segunda entrega): Backend PHP + base de datos + autenticación funcionando.
- **Incremento 3** (Entrega final): Sistema completo con módulos de torneo, Docker, pruebas y documentación.

**2. Permite entregar valor en cada etapa**
A diferencia del modelo en cascada (Waterfall), no se espera al final para tener algo funcional. Al concluir la primera entrega ya existe una interfaz navegable. Al concluir la segunda, ya funciona el sistema de usuarios. Esto permite detectar problemas temprano.

**3. Gestión del riesgo**
Al integrar y probar en cada incremento, los defectos se detectan en etapas tempranas cuando son más baratos de corregir. Los riesgos técnicos (como la integración PHP-MySQL o la configuración de Docker) se pueden enfrentar en el incremento 2 con tiempo suficiente.

**4. Flexibilidad ante cambios de requisitos**
Un proyecto de software real raramente mantiene sus requisitos fijos. El modelo iterativo permite incorporar ajustes o nuevos requisitos entre incrementos sin rehacer todo el trabajo. En particular, los módulos opcionales (exportación PDF, notificaciones) pueden añadirse en el tercer incremento sin afectar lo anterior.

**5. Adecuado para equipos pequeños**
Para un equipo de 3-4 personas, un modelo pesado como el Unificado (RUP) sería excesivo. El modelo incremental ofrece la estructura necesaria sin generar artefactos innecesarios.

---

## Comparativa con otros modelos descartados

| Modelo | Razón del descarte |
|--------|-------------------|
| **Cascada (Waterfall)** | Requiere que todos los requisitos estén definidos desde el inicio. No permite cambios entre fases. El producto final solo se ve al último momento. |
| **Espiral** | Muy orientado a análisis de riesgos formal y contractual. Más adecuado para proyectos grandes con clientes externos. |
| **Ágil (Scrum puro)** | Requiere un Product Owner y sprints cortos de 1-2 semanas con reuniones diarias. Difícil de aplicar en un contexto académico con horarios limitados. |
| **Prototipado** | Útil para validar UI, pero no define cómo construir el sistema completo. Se usa como técnica dentro del modelo elegido, no como ciclo de vida completo. |

---

## Fases del ciclo de vida aplicado al SGDM

```
┌─────────────────────────────────────────────────────────────┐
│                    INCREMENTO 1 (jun–jul 2026)              │
│  Análisis → Diseño → Maquetado HTML/CSS/JS → Documentación  │
│  Entrega: 27/07/2026                                        │
├─────────────────────────────────────────────────────────────┤
│                    INCREMENTO 2 (ago–sep 2026)              │
│  BD Relacional → Backend PHP → Auth → Módulo Usuarios       │
│  Entrega: 14/09/2026                                        │
├─────────────────────────────────────────────────────────────┤
│                    INCREMENTO 3 (oct–nov 2026)              │
│  Módulos de torneo → Docker → Testing → Manual usuario      │
│  Entrega final: 09/11/2026 | Defensa: 23-25/11/2026        │
└─────────────────────────────────────────────────────────────┘
```

---

## Fases dentro de cada incremento

Cada incremento sigue las siguientes fases internas:

1. **Revisión de requisitos** — Se revisan los requisitos del incremento y se priorizan las funcionalidades.
2. **Diseño** — Se diseñan los componentes del incremento (mockups, diagramas, esquemas de BD).
3. **Implementación** — Se codifica el incremento según el diseño.
4. **Prueba** — Se verifica el funcionamiento mediante pruebas manuales y/o automatizadas.
5. **Integración** — El incremento se integra al sistema existente.
6. **Revisión y ajuste** — Se evalúa el trabajo realizado y se ajusta la planificación del próximo incremento.

---

*Instituto Tecnológico Superior "Arias-Balparda" | BT Tecnologías de la Información | 2026*
