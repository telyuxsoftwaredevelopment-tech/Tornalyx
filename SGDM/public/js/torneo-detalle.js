/* =====================================================
   TORNALYX – torneo-detalle.js
   Página de detalle de un torneo (/torneo-detalle?id=N):
   carga desde GET /api/torneo/{id} y pinta cabecera,
   posiciones, resultados y equipos con datos reales.
   ===================================================== */

'use strict';

const { Api, Utils } = window.Tornalyx;
const esc = Utils.escapeHtml;

/** Lee y valida el id del torneo de la query string. */
function torneoId() {
  const id = new URLSearchParams(window.location.search).get('id');
  return /^\d+$/.test(id || '') ? id : null;
}

/** Bloque de estado vacío reutilizable. */
function vacio(mensaje) {
  return `<p class="doc-empty">${esc(mensaje)}</p>`;
}

/* ─── Cabecera (hero + breadcrumb) ─────────────────── */
function renderHero(t) {
  const estado = TorneoUI.estado(t.estado);

  document.title = 'Tornalyx | ' + t.nombre;
  document.getElementById('bcNombre').textContent = t.nombre;
  document.getElementById('bcNombre').style.color = 'var(--ink)';
  document.getElementById('heroNombre').textContent = t.nombre;

  const chip = txt =>
    `<span class="badge" style="background:rgba(255,255,255,.06);color:var(--muted);border:1px solid var(--line)">${txt}</span>`;
  document.getElementById('heroBadges').innerHTML = [
    `<span class="badge ${estado.badge}">${esc(estado.label)}</span>`,
    chip(`${TorneoUI.icono(t.disciplina)} ${esc(t.disciplina)}`),
    chip(esc(TorneoUI.formato(t.formato)))
  ].join('');

  const desc = document.getElementById('heroDesc');
  if (t.descripcion) {
    desc.textContent = t.descripcion;
    desc.style.display = '';
  }

  const org = [t.org_nombre, t.org_apellido].filter(Boolean).join(' ');
  const meta = [];
  const inicio = TorneoUI.fecha(t.fecha_inicio);
  const fin = TorneoUI.fecha(t.fecha_fin);
  if (inicio) meta.push(`📅 Inicio: ${esc(inicio)}`);
  if (fin) meta.push(`🏁 Finaliza: ${esc(fin)}`);
  if (org) meta.push(`🎽 Organiza: ${esc(org)}`);
  document.getElementById('heroMeta').innerHTML = meta.map(m => `<span>${m}</span>`).join('');

  // El botón de inscripción solo tiene sentido con inscripciones abiertas.
  const action = document.getElementById('heroAction');
  action.innerHTML = t.estado === 'inscripcion'
    ? '<a href="/login" class="btn btn-primary btn-lg" style="white-space:nowrap">Inscribirse</a>'
    : '';
}

/* ─── Info boxes ───────────────────────────────────── */
function renderInfo(t) {
  const participantes = TorneoUI.participantes(t);
  const jugados = Number(t.partidos_jugados) || 0;
  const total = Number(t.partidos) || 0;
  const progreso = TorneoUI.progreso(t);

  const boxes = [
    [`${participantes} / ${t.max_participantes}`, 'Participantes'],
    [total ? `${jugados} / ${total}` : '—', 'Partidos jugados'],
    [total ? `${progreso}%` : '—', 'Progreso']
  ];
  document.getElementById('infoGrid').innerHTML = boxes.map(([valor, label]) =>
    `<div class="info-box">
       <div class="info-box__value">${esc(String(valor))}</div>
       <div class="info-box__label">${esc(label)}</div>
     </div>`
  ).join('');
}

/* ─── Posiciones ───────────────────────────────────── */
function badgePos(indice) {
  const n = indice + 1;
  return n <= 3
    ? `<span class="pos-badge pos-${n}">${n}</span>`
    : String(n);
}

function renderPosiciones(rows) {
  const box = document.getElementById('tab-posiciones');
  if (!rows.length) {
    box.innerHTML = `<div class="card"><div class="card__body">${vacio('Aún no hay posiciones. Se calcularán a medida que se carguen resultados.')}</div></div>`;
    return;
  }
  const filas = rows.map((p, i) => `
    <tr>
      <td>${badgePos(i)}</td>
      <td><span class="equipo-tag">${esc(p.contendiente || '—')}</span></td>
      <td>${Number(p.pj)}</td><td>${Number(p.pg)}</td><td>${Number(p.pe)}</td><td>${Number(p.pp)}</td>
      <td>${Number(p.gf)}</td><td>${Number(p.gc)}</td>
      <td>${Number(p.dg) > 0 ? '+' : ''}${Number(p.dg)}</td>
      <td><strong style="font-family:var(--head);color:var(--ink)">${Number(p.pts)}</strong></td>
    </tr>`).join('');

  box.innerHTML = `
    <div class="card">
      <div class="card__header"><h3>Tabla de posiciones</h3></div>
      <div style="overflow-x:auto">
        <table class="table-pos">
          <thead>
            <tr><th>#</th><th>Equipo / Participante</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>GF</th><th>GC</th><th>DG</th><th>Pts</th></tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>
      <div class="card__footer" style="font-size:12px;color:var(--muted-2);font-family:var(--mono)">
        PJ: Jugados · G: Ganados · E: Empatados · P: Perdidos · GF/GC: A favor/En contra · DG: Diferencia · Pts: Puntos
      </div>
    </div>`;
}

/* ─── Resultados (agrupados por ronda) ─────────────── */
function renderResultados(rows) {
  const box = document.getElementById('tab-partidos');
  if (!rows.length) {
    box.innerHTML = vacio('Aún no hay resultados cargados.');
    return;
  }
  const grupos = new Map();
  rows.forEach(r => {
    const clave = r.ronda_nombre || ('Ronda ' + r.ronda);
    if (!grupos.has(clave)) grupos.set(clave, []);
    grupos.get(clave).push(r);
  });

  box.innerHTML = [...grupos.entries()].map(([ronda, partidos]) => `
    <h3 style="margin:var(--space-5) 0 var(--space-4);color:var(--ink)">${esc(ronda)}</h3>
    ${partidos.map(r => `
      <div class="partido-row">
        <span style="flex:1;text-align:right;font-weight:600;color:var(--ink)">${esc(r.local_nombre || '—')}</span>
        <div class="partido-score">${Number(r.goles_local)} · ${Number(r.goles_visitante)}</div>
        <span style="flex:1;font-weight:600;color:var(--ink)">${esc(r.visitante_nombre || '—')}</span>
      </div>`).join('')}
  `).join('');
}

/* ─── Equipos ──────────────────────────────────────── */
function renderEquipos(rows) {
  const box = document.getElementById('tab-equipos');
  if (!rows.length) {
    box.innerHTML = vacio('Aún no hay equipos ni participantes inscritos.');
    return;
  }
  box.innerHTML = `<div class="cards-grid">${rows.map(e => `
    <div class="card"><div class="card__body">
      <h4 style="color:var(--ink);margin-bottom:6px">🛡️ ${esc(e.nombre)}</h4>
    </div></div>`).join('')}</div>`;
}

/* ─── Información ───────────────────────────────────── */
function renderInfoTab(t) {
  const org = [t.org_nombre, t.org_apellido].filter(Boolean).join(' ');
  const filas = [
    ['Disciplina', t.disciplina],
    ['Formato', TorneoUI.formato(t.formato)],
    ['Estado', TorneoUI.estado(t.estado).label],
    ['Máximo de participantes', t.max_participantes],
    ['Inicio', TorneoUI.fecha(t.fecha_inicio) || '—'],
    ['Fin', TorneoUI.fecha(t.fecha_fin) || '—'],
    ['Organiza', org || '—']
  ];
  const desc = t.descripcion
    ? `<p style="color:var(--muted);margin-bottom:var(--space-5);line-height:1.7">${esc(t.descripcion)}</p>`
    : '';
  document.getElementById('tab-info').innerHTML = `
    <div class="card"><div class="card__body">
      <h3 style="margin-bottom:var(--space-4);color:var(--ink)">Información del torneo</h3>
      ${desc}
      <ul style="display:flex;flex-direction:column;gap:var(--space-3)">
        ${filas.map(([k, v]) => `
          <li style="display:flex;justify-content:space-between;gap:var(--space-4);color:var(--muted);font-size:var(--font-size-sm);border-bottom:1px solid var(--line-soft);padding-bottom:var(--space-2)">
            <span>${esc(k)}</span>
            <span style="color:var(--ink);font-weight:500">${esc(String(v))}</span>
          </li>`).join('')}
      </ul>
    </div></div>`;
}

/* ─── Init ─────────────────────────────────────────── */
async function initDetalle() {
  const root = document.getElementById('detalleRoot');
  if (!root) return;

  const id = torneoId();
  if (!id) {
    root.innerHTML = `<div class="wrap" style="padding:var(--space-12) 0;text-align:center">
      ${vacio('No se indicó qué torneo ver.')}
      <a class="btn btn-ghost btn-sm" href="/torneos">Ver todos los torneos</a></div>`;
    return;
  }

  try {
    const data = await Api.get('/api/torneo/' + id);
    renderHero(data.torneo);
    renderInfo(data.torneo);
    renderPosiciones(data.posiciones || []);
    renderResultados(data.resultados || []);
    renderEquipos(data.equipos || []);
    renderInfoTab(data.torneo);
  } catch (err) {
    const mensaje = err.status === 404
      ? 'Torneo no encontrado.'
      : (err.message || 'No se pudo cargar el torneo.');
    root.innerHTML = `<div class="wrap" style="padding:var(--space-12) 0;text-align:center">
      ${vacio(mensaje)}
      <a class="btn btn-ghost btn-sm" href="/torneos">Ver todos los torneos</a></div>`;
  }
}

document.addEventListener('DOMContentLoaded', initDetalle);
