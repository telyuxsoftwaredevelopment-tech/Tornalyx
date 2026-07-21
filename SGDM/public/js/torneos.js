/* =====================================================
   TORNALYX – torneos.js
   Listado público de torneos (/torneos):
   carga desde el API y filtra en el cliente.
   ===================================================== */

'use strict';

const { Api, Utils } = window.Tornalyx;

/* Todos los torneos públicos traídos del API. */
let catalogo = [];

/**
 * Fondo de la cabecera de cada card, por disciplina.
 * @param {string} disciplina
 * @returns {string} valor CSS de background
 */
function fondoDisciplina(disciplina) {
  const paletas = [
    'linear-gradient(135deg,#1e3a8a,#2563eb)',
    'linear-gradient(135deg,#4c1d95,#7c3aed)',
    'linear-gradient(135deg,#065f46,#059669)',
    'linear-gradient(135deg,#92400e,#d97706)',
    'linear-gradient(135deg,#1f2937,#374151)',
    'linear-gradient(135deg,#0c4a6e,#0284c7)'
  ];
  /* Hash simple del nombre: la misma disciplina siempre recibe el mismo color. */
  const texto = String(disciplina || '');
  let hash = 0;
  for (let i = 0; i < texto.length; i++) hash = (hash + texto.charCodeAt(i)) % paletas.length;
  return paletas[hash];
}

/**
 * HTML de una card del listado público.
 * @param {Object} t
 * @returns {string}
 */
function cardHtml(t) {
  const esc     = Utils.escapeHtml;
  const estado  = TorneoUI.estado(t.estado);
  const cupos   = TorneoUI.participantes(t);
  const inicio  = TorneoUI.fecha(t.fecha_inicio);
  const fin     = TorneoUI.fecha(t.fecha_fin);
  const abierto = t.estado === 'inscripcion';
  const org     = [t.org_nombre, t.org_apellido].filter(Boolean).join(' ');

  const lineas = [];
  if (inicio) lineas.push(`📅 Inicio: ${inicio}`);
  else if (fin) lineas.push(`🏁 Finaliza: ${fin}`);
  lineas.push(`👥 ${cupos} de ${t.max_participantes} inscritos`);
  if (org) lineas.push(`🎽 Organiza: ${esc(org)}`);

  return `
    <article class="torneo-card"
             data-id="${t.id}"
             data-disciplina="${esc(t.disciplina)}"
             data-estado="${esc(t.estado)}"
             data-sistema="${esc(t.formato)}"
             data-nombre="${esc(String(t.nombre).toLowerCase())}"
             data-fecha="${esc(t.fecha_inicio || '')}">
      <div class="torneo-card__sport" style="background:${fondoDisciplina(t.disciplina)}">
        ${TorneoUI.icono(t.disciplina)}
      </div>
      <div class="torneo-card__body">
        <div class="torneo-card__meta">
          <span class="badge ${estado.badge}">${esc(estado.label)}</span>
          <span class="badge badge-blue">${esc(TorneoUI.formato(t.formato))}</span>
        </div>
        <h3 class="torneo-card__title">${esc(t.nombre)}</h3>
        <div class="torneo-card__info">${lineas.map(l => `<span>${l}</span>`).join('')}</div>
      </div>
      <div class="torneo-card__footer">
        <span class="${abierto ? 'status-next' : 'status-live'}">${esc(estado.label)}</span>
        <a href="/torneo-detalle?id=${t.id}" class="btn ${abierto ? 'btn-ghost' : 'btn-primary'} btn-sm">
          ${abierto ? 'Inscribirse' : 'Ver'}
        </a>
      </div>
    </article>`;
}

/** Dibuja el grid completo a partir del catálogo cargado. */
function renderGrid() {
  const grid = document.getElementById('torneosGrid');
  if (!grid) return;
  grid.innerHTML = catalogo.map(cardHtml).join('');
  aplicarFiltros();
}

/** Rellena el select de disciplinas con las que existen realmente. */
function renderDisciplinas() {
  const sel = document.getElementById('filterDeporte');
  if (!sel) return;
  const previo = sel.value;
  const unicas = [...new Set(catalogo.map(t => t.disciplina).filter(Boolean))].sort();
  sel.innerHTML = '<option value="">Todas</option>' +
    unicas.map(d => `<option value="${Utils.escapeHtml(d)}">${Utils.escapeHtml(d)}</option>`).join('');
  if (previo) sel.value = previo;
}

/** Muestra u oculta las cards según los filtros activos. */
function aplicarFiltros() {
  const valor = id => (document.getElementById(id)?.value || '').trim();
  const q          = valor('searchQ').toLowerCase();
  const disciplina = valor('filterDeporte');
  const estado     = valor('filterEstado');
  const sistema    = valor('filterSistema');

  let visibles = 0;
  document.querySelectorAll('#torneosGrid .torneo-card').forEach(card => {
    const show =
      (!q          || card.dataset.nombre.includes(q)) &&
      (!disciplina || card.dataset.disciplina === disciplina) &&
      (!estado     || card.dataset.estado     === estado) &&
      (!sistema    || card.dataset.sistema    === sistema);
    card.style.display = show ? '' : 'none';
    if (show) visibles++;
  });

  const countEl = document.querySelector('#resultsCount strong');
  if (countEl) countEl.textContent = visibles;

  const emptyEl = document.getElementById('emptyState');
  if (emptyEl) emptyEl.classList.toggle('hidden', visibles > 0);
}

/** Reordena las cards según el criterio elegido. */
function aplicarOrden() {
  const grid = document.getElementById('torneosGrid');
  const sel  = document.getElementById('sortBy');
  if (!grid || !sel) return;

  const cards = [...grid.querySelectorAll('.torneo-card')];
  const criterio = sel.value;
  cards.sort((a, b) => {
    if (criterio === 'nombre') {
      return a.dataset.nombre.localeCompare(b.dataset.nombre);
    }
    if (criterio === 'fecha') {
      /* Los torneos sin fecha de inicio van al final. */
      const fa = a.dataset.fecha || '9999-12-31';
      const fb = b.dataset.fecha || '9999-12-31';
      return fa.localeCompare(fb);
    }
    return 0;   // "Más recientes": ya vienen ordenados por el API.
  });
  cards.forEach(c => grid.appendChild(c));
}

/** Carga los torneos públicos y engancha los controles de filtrado. */
async function initTorneos() {
  const grid = document.getElementById('torneosGrid');
  if (!grid) return;

  grid.innerHTML = '<p style="color:var(--muted)">Cargando torneos…</p>';

  try {
    const data = await Api.get('/api/torneos');
    catalogo = data.torneos || [];
    renderDisciplinas();
    renderGrid();
  } catch (err) {
    grid.innerHTML = `<p style="color:var(--muted)">${Utils.escapeHtml(err.message)}</p>`;
    return;
  }

  document.getElementById('btnFiltrar')?.addEventListener('click', aplicarFiltros);
  ['filterDeporte', 'filterEstado', 'filterSistema'].forEach(id =>
    document.getElementById(id)?.addEventListener('change', aplicarFiltros)
  );

  const search = document.getElementById('searchQ');
  if (search) {
    search.addEventListener('input', Utils.debounce(aplicarFiltros, 250));
    search.addEventListener('keydown', e => { if (e.key === 'Enter') aplicarFiltros(); });
  }

  document.getElementById('sortBy')?.addEventListener('change', aplicarOrden);
}

document.addEventListener('DOMContentLoaded', initTorneos);
