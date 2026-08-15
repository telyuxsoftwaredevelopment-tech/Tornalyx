# Roles por torneo (organizador / participante / público)

## Contexto

Hoy la creación y gestión de torneos está protegida por un rol **global** de
cuenta (`usuarios.rol = 'organizador'`), asignado a mano por un administrador
desde `/admin/dashboard`. Un usuario común (`participante`) no puede crear
torneos.

El pedido es que **cualquier usuario logueado pueda crear torneos**, y que los
roles pasen a ser **por torneo**: quien lo crea es su organizador, quien se
inscribe es participante de ese torneo, y todo el resto del mundo es público.
Cada rol debe tener el mínimo privilegio necesario.

La base ya está parcialmente resuelta: `torneos.organizador_id` identifica al
dueño de cada torneo, e `inscripciones` identifica a sus participantes. Varios
controladores ya validan propiedad (`puedeGestionar()`, chequeos de
`organizador_id === Session::getUserId()`) por encima del rol global. El
cambio principal es **dejar de exigir el rol global `organizador`** como
gate adicional, y **construir las acciones de editar/eliminar torneo que hoy
no existen**.

## 1. Modelo de permisos

| Rol | Ámbito | Puede |
|---|---|---|
| Público | cualquiera (logueado o no) | Ver torneos publicados, posiciones, fixture, avisos. Nada de gestión. |
| Participante | por torneo — tiene una inscripción en ese torneo | Ver su inscripción, cancelarla (si el torneo no arrancó), confirmar asistencia a sus partidos. |
| Organizador | por torneo — `torneos.organizador_id` = su id | Editar ajustes, eliminar (sin actividad) o cancelar, aprobar/rechazar inscripciones, cargar resultados, generar fixture, publicar avisos — de ESE torneo. |
| Administrador | global de sitio | Todo lo anterior de organizador, sobre CUALQUIER torneo (mismo patrón que ya usa el código para resultados/inscripciones/avisos). Además, gestión de cuentas. |

El rol global `organizador` deja de existir como concepto. `usuarios.rol`
pasa a tener solo `'participante' | 'administrador'`.

## 2. Datos y migración

Nueva migración `SGDM/backend/database/migrations/change_rol_torneo.sql`
(statements separados, registrada en `schema_migrations` según el patrón ya
usado en el proyecto — ver `add_torneo_gestion.sql`):

```sql
UPDATE usuarios SET rol = 'participante' WHERE rol = 'organizador';
ALTER TABLE usuarios MODIFY rol ENUM('participante','administrador') NOT NULL DEFAULT 'participante';
```

No se toca `torneos` ni `inscripciones`: la pertenencia por torneo ya vive
ahí.

## 3. Backend — endpoints existentes que cambian de gate

Reemplazar `requireApiRole(['organizador','administrador'])` por
`requireApiRole(['participante','administrador'])` (es decir: "cualquier
logueado"), dejando intacto el chequeo de propiedad (`puedeGestionar()` o
equivalente) que ya hace cada acción:

- `TorneoController::store()` — crear torneo.
- `TorneoController::mios()` — listar "mis torneos".
- `InscripcionController::listar()`, `::resolver()`.
- `TorneoController::cargarResultado()`.
- `PartidoController` — generación de fixture y demás acciones de gestión
  (si algún método no valida dueño todavía, se le agrega el chequeo antes de
  aflojar el rol).
- `AvisoController::publicar()` (o equivalente de creación de aviso).

`AdminController::ROLES` pasa a `['participante', 'administrador']`.

## 4. Backend — endpoints nuevos

**`POST /api/torneo/{id}/editar`** → `TorneoController::actualizar()`
- Mismas validaciones de campo que `store()`.
- Requiere ser dueño del torneo o administrador.
- Reglas: `max_participantes` no puede bajar de los inscriptos aprobados
  actuales; `formato` no es editable si el torneo ya tiene partidos
  generados.

**`POST /api/torneo/{id}/eliminar`** → `TorneoController::eliminar()`
- Requiere ser dueño del torneo o administrador.
- Rechaza (con mensaje explicando por qué) si el torneo tiene inscripciones
  aprobadas o partidos generados, sugiriendo cancelarlo en su lugar.
- Si no tiene actividad: borrado real de la fila.

**`POST /api/torneo/{id}/cancelar`** → expone la `cambiarEstado('cancelado')`
que ya existe en el modelo `Torneo` pero hoy no tiene ruta HTTP propia.
Mismo control de propiedad que los anteriores.

## 5. Frontend / flujo

- **Registro** (`login.html`, `AuthController::processRegistro`): se quita el
  concepto de rol elegible al registrarse; toda alta nueva es
  `participante` (el backend deja de aceptar `'organizador'` como valor).
- **Nav** (`main.js::initAuthNav`): el panel deja de resolverse por
  `me.rol`; cualquier usuario logueado tiene acceso a "Mis torneos"
  (`/organizador/dashboard`). El acceso a `/admin/dashboard` sigue
  reservado a `administrador`, como panel aparte de gestión de cuentas.
- **Ruta `/organizador/dashboard`**: `Session::requireRole(['organizador','administrador'])`
  → `Session::requireRole(['participante','administrador'])`.
- **`organizador-dashboard.html`**: el badge fijo "organizador" deja de ser
  una identidad de cuenta. Si el usuario no organizó ningún torneo, el panel
  muestra un estado vacío con foco en "+ Crear torneo".
- **Vista de gestión de un torneo** (`organizador-gestion.js`): dos botones
  nuevos — "Editar ajustes" (reabre el formulario de creación precargado,
  llama al nuevo endpoint de edición) y "Eliminar torneo" (con
  confirmación; si el backend lo rechaza por tener actividad, ofrece
  "Cancelar torneo" en su lugar).
- **`/admin/dashboard`**: el selector de rol de usuarios se reduce a
  `participante` / `administrador` — ya no se asigna "organizador" a mano.

## Fuera de alcance de este spec

- El fix del botón/ícono de accesibilidad que tapa el acceso al perfil en
  el dashboard de admin.
- La animación del botón de crear cuenta / iniciar sesión.
- Mejoras generales de accesibilidad del flujo del sitio.

Estos tres puntos son cambios acotados y aislados (CSS/JS puntuales) que no
requieren spec propio; se implementan directamente después de este.
