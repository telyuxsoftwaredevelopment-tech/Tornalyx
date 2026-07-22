/* =====================================================
   TORNALYX – admin.js
   Panel de administración: llena las tarjetas KPI y el
   feed de actividad reciente con GET /api/admin/stats.
   ===================================================== */

'use strict';

/* IIFE: evita colisiones de `const` de nivel superior con organizador.js,
   que se carga en la misma página (pestaña Torneos del admin). */
(function () {

const { Api, Utils, Toast, Modal } = window.Tornalyx;

/* Punto y color del feed según el tipo de evento. */
const DOT_POR_TIPO = {
  torneo:    'dot-green',
  resultado: 'dot-yellow',
  usuario:   'dot-muted'
};

/* Color del badge y etiqueta legible de cada rol. */
const ROL_BADGE = {
  participante:  { badge: 'badge-blue',   label: 'Participante'  },
  organizador:  { badge: 'badge-yellow', label: 'Organizador'   },
  administrador: { badge: 'badge-red',    label: 'Administrador' }
};

/* Punto y etiqueta de cada estado de usuario. */
const ESTADO_USUARIO = {
  activo:     { dot: 'dot-green',  label: 'Activo'     },
  suspendido: { dot: 'dot-muted',  label: 'Suspendido' },
  pendiente:  { dot: 'dot-yellow', label: 'Pendiente'  }
};

/* Todos los usuarios cargados (se filtran en el cliente). */
let usuarios = [];

/**
 * Escribe un número en una tarjeta KPI, formateado con separador de miles.
 * @param {string} id
 * @param {number} valor
 */
function setKpi(id, valor) {
  const el = document.getElementById(id);
  if (el) el.textContent = Number(valor || 0).toLocaleString('es-UY');
}

/**
 * Escribe el texto secundario (delta) de una tarjeta KPI.
 * @param {string} id
 * @param {string} texto
 */
function setDelta(id, texto) {
  const el = document.getElementById(id);
  if (el) el.textContent = texto;
}

/** Vuelca los contadores en las tarjetas KPI. */
function renderKpis(stats) {
  setKpi('kpiUsuarios', stats.usuarios);
  const nuevos = Number(stats.usuarios_nuevos_semana || 0);
  setDelta('kpiUsuariosDelta', nuevos > 0 ? `+${nuevos} esta semana` : 'Sin altas esta semana');

  setKpi('kpiTorneos', stats.torneos_activos);
  setDelta('kpiTorneosDelta', `${Number(stats.torneos_total || 0)} en total`);

  setKpi('kpiEquipos', stats.equipos);
  setDelta('kpiEquiposDelta', 'Equipos aprobados');

  setKpi('kpiPartidos', stats.partidos);
  const semana = Number(stats.partidos_semana || 0);
  setDelta('kpiPartidosDelta', semana > 0 ? `+${semana} esta semana` : 'Sin partidos esta semana');
}

/**
 * HTML de una fila del feed de actividad.
 * @param {Object} ev Evento de /api/admin/stats.
 * @returns {string}
 */
function actividadRowHtml(ev) {
  const esc = Utils.escapeHtml;
  const dot = DOT_POR_TIPO[ev.tipo] || 'dot-muted';
  return `
    <div style="display:flex;align-items:center;gap:var(--space-3);font-size:var(--font-size-sm)">
      <span class="dot ${dot}"></span>
      <span style="color:var(--muted)">${esc(ev.texto)}:</span>
      <span style="color:var(--ink);font-weight:500">${esc(ev.detalle)}</span>
      <span style="margin-left:auto;color:var(--muted-2);font-family:var(--mono);font-size:11px">${esc(Utils.timeAgo(ev.created_at))}</span>
    </div>`;
}

/** Dibuja el feed de actividad reciente. */
function renderActividad(actividad) {
  const cont = document.getElementById('actividadReciente');
  if (!cont) return;
  cont.innerHTML = actividad.length
    ? actividad.map(actividadRowHtml).join('')
    : '<div style="color:var(--muted);font-size:var(--font-size-sm)">Sin actividad reciente.</div>';
}

/**
 * Formatea la fecha de registro (created_at SQL) como dd/mm/aaaa.
 * @param {string} fechaSql
 * @returns {string}
 */
function fechaRegistro(fechaSql) {
  if (!fechaSql) return '—';
  const d = new Date(String(fechaSql).replace(' ', 'T'));
  return isNaN(d) ? '—' : d.toLocaleDateString('es-UY', {
    day: '2-digit', month: '2-digit', year: 'numeric'
  });
}

/**
 * HTML de una fila de la tabla de usuarios.
 * @param {Object} u
 * @returns {string}
 */
function usuarioRowHtml(u) {
  const esc    = Utils.escapeHtml;
  const rol    = ROL_BADGE[u.rol] || { badge: 'badge-muted', label: u.rol || '—' };
  const estado = ESTADO_USUARIO[u.estado] || { dot: 'dot-muted', label: u.estado || '—' };
  const nombre = [u.nombre, u.apellido].filter(Boolean).join(' ');

  return `
    <tr>
      <td>${esc(nombre)}</td>
      <td style="color:var(--muted-2)">${esc(u.email)}</td>
      <td><span class="badge ${rol.badge}">${esc(rol.label)}</span></td>
      <td><span class="dot ${estado.dot}"></span>${esc(estado.label)}</td>
      <td style="font-family:var(--mono);font-size:12px">${esc(fechaRegistro(u.created_at))}</td>
      <td><a href="#" data-editar="${u.id}" style="color:var(--red-bright);font-size:12px">Editar</a></td>
    </tr>`;
}

/** Aplica el filtro de rol + búsqueda y redibuja la tabla de usuarios. */
function renderUsuarios() {
  const body = document.getElementById('usuariosBody');
  if (!body) return;

  const rol = (document.getElementById('usuariosRol')?.value || '');
  const q   = (document.getElementById('usuariosBuscar')?.value || '').toLowerCase().trim();

  const filtrados = usuarios.filter(u => {
    if (rol && u.rol !== rol) return false;
    if (!q) return true;
    const texto = `${u.nombre} ${u.apellido} ${u.email}`.toLowerCase();
    return texto.includes(q);
  });

  body.innerHTML = filtrados.length
    ? filtrados.map(usuarioRowHtml).join('')
    : '<tr><td colspan="6" style="text-align:center;color:var(--muted)">No hay usuarios que coincidan.</td></tr>';

  /* Cada enlace "Editar" abre el modal con los datos de ese usuario. */
  body.querySelectorAll('[data-editar]').forEach(a =>
    a.addEventListener('click', e => {
      e.preventDefault();
      abrirModalUsuario(usuarios.find(u => u.id === Number(a.dataset.editar)));
    })
  );

  const footer = document.getElementById('usuariosFooter');
  if (footer) {
    footer.textContent = filtrados.length === usuarios.length
      ? `${usuarios.length} usuario(s)`
      : `Mostrando ${filtrados.length} de ${usuarios.length} usuario(s)`;
  }
}

/** Carga la lista de usuarios y engancha los controles de filtrado. */
async function cargarUsuarios() {
  const body = document.getElementById('usuariosBody');
  if (!body) return;

  body.innerHTML = '<tr><td colspan="6" style="color:var(--muted)">Cargando usuarios…</td></tr>';
  try {
    const data = await Api.get('/api/admin/usuarios');
    usuarios = data.usuarios || [];
    renderUsuarios();
  } catch (err) {
    body.innerHTML = `<tr><td colspan="6" style="color:var(--muted)">${Utils.escapeHtml(err.message)}</td></tr>`;
    const footer = document.getElementById('usuariosFooter');
    if (footer) footer.textContent = '';
  }
}

/**
 * Abre el modal de usuario. Sin argumento → modo alta; con un usuario → modo
 * edición, con el formulario precargado.
 * @param {Object} [u]
 */
function abrirModalUsuario(u) {
  const form = document.getElementById('usuarioForm');
  if (!form) return;
  form.reset();
  limpiarErroresUsuario();

  const editando = Boolean(u && u.id);
  document.getElementById('usuarioModalTitle').textContent = editando ? 'Editar usuario' : 'Nuevo usuario';
  document.getElementById('usuarioSubmit').textContent     = editando ? 'Guardar cambios' : 'Crear usuario';
  document.getElementById('usuarioPasswordHint').textContent = editando
    ? 'Dejala en blanco para conservar la contraseña actual.'
    : 'Mínimo 8 caracteres, con mayúsculas, minúsculas y números.';

  document.getElementById('usuarioId').value       = editando ? u.id : '';
  if (editando) {
    document.getElementById('usuarioNombre').value   = u.nombre || '';
    document.getElementById('usuarioApellido').value = u.apellido || '';
    document.getElementById('usuarioEmail').value    = u.email || '';
    document.getElementById('usuarioRolCampo').value = u.rol || 'participante';
    document.getElementById('usuarioEstado').value   = u.estado || 'activo';
    /* fecha_nac viene como 'YYYY-MM-DD'; el input date la toma directo. */
    document.getElementById('usuarioFechaNac').value = (u.fecha_nac || '').slice(0, 10);
  }

  Modal.open('usuarioModal');
  document.getElementById('usuarioNombre').focus();
}

/** Quita las marcas de error de todos los campos del formulario de usuario. */
function limpiarErroresUsuario() {
  document.querySelectorAll('#usuarioForm .form-control').forEach(el => { el.style.borderColor = ''; });
}

/**
 * Marca un campo del formulario de usuario como erróneo.
 * @param {string} campo
 */
function marcarErrorUsuario(campo) {
  const el = document.querySelector(`#usuarioForm [name="${campo}"]`);
  if (!el) return;
  el.style.borderColor = 'var(--red)';
  el.focus();
  el.addEventListener('input', () => { el.style.borderColor = ''; }, { once: true });
}

/** Inserta o actualiza un usuario en la lista en memoria y redibuja. */
function upsertUsuario(u) {
  const i = usuarios.findIndex(x => x.id === u.id);
  if (i >= 0) usuarios[i] = u;
  else        usuarios.unshift(u);
  renderUsuarios();
}

/** Envía el formulario del modal a crear o actualizar según el modo. */
function initUsuarioForm() {
  const form = document.getElementById('usuarioForm');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    limpiarErroresUsuario();

    const editando = document.getElementById('usuarioId').value !== '';
    const url = editando ? '/api/admin/usuario/actualizar' : '/api/admin/usuario/crear';
    const campos = Object.fromEntries(new FormData(form).entries());

    const submit = document.getElementById('usuarioSubmit');
    const original = submit.textContent;
    submit.disabled = true;
    submit.textContent = editando ? 'Guardando…' : 'Creando…';

    try {
      const data = await Api.post(url, campos);
      Toast.success(data.mensaje || 'Listo.');
      if (data.usuario) upsertUsuario(data.usuario);
      else await cargarUsuarios();
      Modal.close('usuarioModal');
    } catch (err) {
      Toast.error(err.message);
      if (err.data && err.data.campo) marcarErrorUsuario(err.data.campo);
    } finally {
      submit.disabled = false;
      submit.textContent = original;
    }
  });
}

/** Carga las estadísticas del sistema y pinta KPIs + actividad. */
async function cargarStats() {
  try {
    const data = await Api.get('/api/admin/stats');
    renderKpis(data.stats || {});
    renderActividad(data.actividad || []);
  } catch (err) {
    const cont = document.getElementById('actividadReciente');
    if (cont) {
      cont.innerHTML = `<div style="color:var(--muted);font-size:var(--font-size-sm)">${Utils.escapeHtml(err.message)}</div>`;
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  /* Solo corre en la vista del panel de administración. */
  if (document.getElementById('kpiUsuarios')) cargarStats();

  if (document.getElementById('usuariosBody')) {
    cargarUsuarios();
    initUsuarioForm();
    document.getElementById('usuariosRol')?.addEventListener('change', renderUsuarios);
    document.getElementById('nuevoUsuarioBtn')?.addEventListener('click', () => abrirModalUsuario());
    const buscar = document.getElementById('usuariosBuscar');
    if (buscar) buscar.addEventListener('input', Utils.debounce(renderUsuarios, 200));
  }
});

})();
