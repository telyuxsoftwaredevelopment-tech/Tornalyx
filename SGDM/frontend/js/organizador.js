/* =====================================================
   TORNALYX – organizador.js
   Panel del organizador (y pestaña Torneos del admin):
   crear torneos contra el API y listar los propios.
   ===================================================== */

'use strict';

/* IIFE: evita colisiones de `const` de nivel superior con admin.js,
   que se carga en la misma página (panel de administración). */
(function () {

const { Api, Toast, Utils } = window.Tornalyx;

/* Torneos cargados, en el orden en que se muestran. */
let torneos = [];

/**
 * Muestra u oculta el formulario de creación cuando vive dentro de un panel
 * (panel del admin). En el panel del organizador el formulario está en su
 * propia sección y este helper no aplica.
 * @param {boolean} mostrar
 */
function mostrarCreateCard(mostrar) {
  const card = document.getElementById('createCard');
  if (!card) return;
  card.classList.toggle('hidden', !mostrar);
  if (mostrar) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/**
 * Abre el formulario de creación esté donde esté: como sección propia
 * (organizador) o como tarjeta plegable dentro del panel (admin).
 */
function abrirCreacion() {
  if (document.getElementById('panel-crear')) {
    window.setSection('crear');
  } else {
    mostrarCreateCard(true);
  }
}

/**
 * Clave (data-section) del panel que contiene la lista de torneos, para volver
 * a él tras crear uno. Organizador → "inicio"; admin → "torneos".
 * @returns {string}
 */
function seccionDeLista() {
  const panel = document.getElementById('misTorneos')?.closest('.section-panel');
  return panel ? panel.id.replace(/^panel-/, '') : 'inicio';
}

/**
 * Pinta el HTML de una tarjeta de torneo del panel.
 * @param {Object} t Torneo devuelto por el API.
 * @returns {string}
 */
function torneoItemHtml(t) {
  const esc      = Utils.escapeHtml;
  const estado   = TorneoUI.estado(t.estado);
  const progreso = TorneoUI.progreso(t);
  const cupos    = TorneoUI.participantes(t);
  const ocupacion = t.max_participantes
    ? Math.min(100, Math.round(cupos / t.max_participantes * 100))
    : 0;

  /* Mientras no haya partidos, la barra muestra el llenado de cupos: es el
     dato útil en un torneo que todavía está juntando inscripciones. */
  const midiendoPartidos = Number(t.partidos) > 0;
  const barraLabel = midiendoPartidos ? 'Progreso' : 'Inscripciones';
  const barraValor = midiendoPartidos
    ? `${progreso}%`
    : `${cupos}/${t.max_participantes}`;
  const barraAncho = midiendoPartidos ? progreso : ocupacion;

  return `
    <div class="torneo-item" data-id="${t.id}">
      <div class="torneo-item__head">
        <div>
          <div class="torneo-item__title">${TorneoUI.icono(t.disciplina)} ${esc(t.nombre)}</div>
          <div class="torneo-item__meta">${esc(TorneoUI.resumen(t))}</div>
        </div>
        <span class="badge ${estado.badge}">${esc(estado.label)}</span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-bottom:4px">
        <span style="font-size:12px;color:var(--muted)">${barraLabel}</span>
        <span style="font-size:12px;color:var(--muted-2);font-family:var(--mono)">${esc(barraValor)}</span>
      </div>
      <div class="progress-bar"><div class="progress-fill" style="width:${barraAncho}%"></div></div>
      <div style="margin-top:var(--space-3);display:flex;gap:var(--space-2)">
        <a class="btn btn-ghost btn-sm" href="/torneo-detalle?id=${t.id}">Ver detalle</a>
      </div>
    </div>`;
}

/** Vuelve a dibujar la lista de torneos y los KPIs. */
function renderTorneos() {
  const cont = document.getElementById('misTorneos');
  if (!cont) return;

  if (!torneos.length) {
    cont.innerHTML = `
      <div class="torneo-item" style="text-align:center;color:var(--muted)">
        Todavía no hay torneos.
        <a href="#" data-abrir-creacion style="color:var(--red-bright)">Creá el primero</a>.
      </div>`;
    /* El enlace recién creado necesita su handler para abrir el formulario. */
    cont.querySelectorAll('[data-abrir-creacion]').forEach(el =>
      el.addEventListener('click', e => { e.preventDefault(); abrirCreacion(); })
    );
  } else {
    cont.innerHTML = torneos.map(torneoItemHtml).join('');
  }

  renderKpis();
  renderSelectores();
}

/** Actualiza las tarjetas de métricas del encabezado. */
function renderKpis() {
  const activos = torneos.filter(t => t.estado === 'inscripcion' || t.estado === 'en_curso').length;
  const finalizados = torneos.filter(t => t.estado === 'finalizado').length;
  const participantes = torneos.reduce((n, t) => n + TorneoUI.participantes(t), 0);
  const jugados = torneos.reduce((n, t) => n + (Number(t.partidos_jugados) || 0), 0);
  const programados = torneos.reduce((n, t) => n + (Number(t.partidos) || 0), 0);

  const set = (id, valor) => {
    const el = document.getElementById(id);
    if (el) el.textContent = valor;
  };
  set('kpiActivos', activos);
  set('kpiActivosDelta', `${torneos.length} en total · ${finalizados} finalizado(s)`);
  set('kpiParticipantes', participantes);
  set('kpiParticipantesDelta', participantes === 1 ? '1 confirmado' : `${participantes} confirmados`);
  set('kpiPartidos', jugados);
  set('kpiPartidosDelta', `de ${programados} programados`);
}

/** Rellena los <select> que listan torneos del organizador. */
function renderSelectores() {
  document.querySelectorAll('[data-torneos-select]').forEach(sel => {
    const previo = sel.value;
    sel.innerHTML = torneos.length
      ? torneos.map(t => `<option value="${t.id}">${Utils.escapeHtml(t.nombre)}</option>`).join('')
      : '<option value="">Sin torneos todavía</option>';
    if (previo) sel.value = previo;
  });
}

/** Carga los torneos del usuario desde el API. */
async function cargarTorneos() {
  const cont = document.getElementById('misTorneos');
  if (cont) {
    cont.innerHTML = '<div class="torneo-item" style="color:var(--muted)">Cargando torneos…</div>';
  }
  try {
    const data = await Api.get('/api/torneos/mios');
    torneos = data.torneos || [];
    renderTorneos();
  } catch (err) {
    if (cont) {
      cont.innerHTML = `<div class="torneo-item" style="color:var(--muted)">${Utils.escapeHtml(err.message)}</div>`;
    }
  }
}

/**
 * Marca un campo del formulario como erróneo y lo enfoca.
 * @param {string} campo Nombre del campo devuelto por el API.
 */
function marcarError(campo) {
  const el = document.querySelector(`#createForm [name="${campo}"]`);
  if (!el) return;
  el.focus();
  el.style.borderColor = 'var(--red)';
  el.addEventListener('input', () => { el.style.borderColor = ''; }, { once: true });
}

/** Conecta el formulario de creación con POST /api/torneo/crear. */
function initCreateForm() {
  const form = document.getElementById('createForm');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    const original  = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
      submitBtn.disabled = true;      // Evita el doble envío (torneo duplicado).
      submitBtn.textContent = 'Creando…';
    }

    const campos = Object.fromEntries(new FormData(form).entries());

    try {
      const data = await Api.post('/api/torneo/crear', campos);
      Toast.success(data.mensaje || 'Torneo creado.');
      if (data.torneo) {
        torneos.unshift(data.torneo);   // El listado va por fecha de creación desc.
        renderTorneos();
      } else {
        await cargarTorneos();
      }
      form.reset();
      mostrarCreateCard(false);          // Repliega el formulario (panel del admin).
      window.setSection(seccionDeLista());
    } catch (err) {
      Toast.error(err.message);
      if (err.data && err.data.campo) marcarError(err.data.campo);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = original;
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('misTorneos') && !document.getElementById('createForm')) return;

  /* Botón "+ Nuevo torneo" del panel del admin: alterna el formulario. */
  document.querySelectorAll('[data-toggle-create]').forEach(btn =>
    btn.addEventListener('click', () => {
      const card = document.getElementById('createCard');
      mostrarCreateCard(card ? card.classList.contains('hidden') : true);
    })
  );

  initCreateForm();
  cargarTorneos();
});

})();
