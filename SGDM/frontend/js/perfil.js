/* =====================================================
   TORNALYX – perfil.js
   Página /perfil: carga los datos reales del usuario
   (GET /api/perfil) y maneja la edición de datos, bio,
   contraseña y foto de perfil. Sin datos de ejemplo.
   ===================================================== */

'use strict';

(function () {

  const { Api, Toast, Utils } = window.Tornalyx || {};
  if (!Api) return;

  const BIO_MAX_PALABRAS = 500;

  /* Estado del usuario cargado, para "Restablecer" y para re-render. */
  let usuarioActual = null;

  const $ = (id) => document.getElementById(id);

  const ESTADO_TORNEO = {
    borrador:    ['Borrador',      'badge-gray'],
    inscripcion: ['Inscripciones', 'badge-green'],
    en_curso:    ['En curso',      'badge-green'],
    finalizado:  ['Finalizado',    'badge-gray'],
    cancelado:   ['Cancelado',     'badge-gray'],
  };
  const ESTADO_INSCRIPCION = {
    pendiente: ['Pendiente', 'badge-gray'],
    aprobada:  ['Aprobada',  'badge-green'],
    rechazada: ['Rechazada', 'badge-gray'],
  };

  /* ─── Render del hero y avatar ─────────────────────── */

  function iniciales(u) {
    return (((u.nombre || '')[0] || '') + ((u.apellido || '')[0] || '')).toUpperCase() || '?';
  }

  function pintarAvatar(el, u) {
    if (!el) return;
    if (u.avatar_url) {
      el.textContent = '';
      el.style.backgroundImage = `url(${encodeURI(u.avatar_url)})`;
    } else {
      el.style.backgroundImage = '';
      el.textContent = iniciales(u);
    }
  }

  /* ─── Redes sociales ───────────────────────────────── */
  const REDES_ICONS = {
    twitter_url: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4l16 16M20 4L4 20"/></svg>',
    facebook_url: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 21v-7h2.5l.5-3H14V9a1.5 1.5 0 0 1 1.5-1.5H17V4.6C16.5 4.5 15.6 4.4 14.7 4.4c-2.5 0-4.2 1.6-4.2 4.4V11H8v3h2.5v7"/></svg>',
    instagram_url: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1"/></svg>',
  };
  const REDES_LABELS = { twitter_url: 'X / Twitter', facebook_url: 'Facebook', instagram_url: 'Instagram' };

  function redesHtml(u) {
    return Object.keys(REDES_ICONS)
      .filter(k => u[k])
      .map(k => `<a href="${Utils.escapeHtml(u[k])}" target="_blank" rel="noopener noreferrer" aria-label="${REDES_LABELS[k]}" title="${REDES_LABELS[k]}">${REDES_ICONS[k]}</a>`)
      .join('');
  }

  function pintarHero(u) {
    if ($('heroNombre')) $('heroNombre').textContent = `${u.nombre} ${u.apellido}`.trim();

    const meta = [];
    if (u.rol) {
      const esAdmin = u.rol === 'administrador';
      meta.push(`<span class="${esAdmin ? 'badge-role-admin' : 'badge-role-user'}">${esAdmin ? 'ADMINISTRADOR' : 'PARTICIPANTE'}</span>`);
    }
    if (u.created_at) {
      const alta = new Date(String(u.created_at).replace(' ', 'T'));
      if (!isNaN(alta)) {
        meta.push('<span>📅 Miembro desde ' + alta.toLocaleDateString('es-UY', { month: 'short', year: 'numeric' }) + '</span>');
      }
    }
    if (u.email) {
      meta.push('<span>✉️ ' + Utils.escapeHtml(u.email) + '</span>');
    }
    if (u.ubicacion) {
      meta.push('<span>📍 ' + Utils.escapeHtml(u.ubicacion) + '</span>');
    }
    if ($('heroMeta')) $('heroMeta').innerHTML = meta.join('');
    if ($('heroRedes')) $('heroRedes').innerHTML = redesHtml(u);

    pintarAvatar($('heroAvatar'), u);
    if ($('editAvatar')) pintarAvatar($('editAvatar'), u);
  }

  /* ─── Render de stats, torneos y equipos ───────────── */

  function pintarStats(s) {
    $('statTorneos').textContent     = s.torneos;
    $('statEquipos').textContent     = s.equipos;
    $('statActivos').textContent     = s.activos;
    $('statFinalizados').textContent = s.finalizados;
  }

  function badge(mapa, clave) {
    const [texto, clase] = mapa[clave] || [clave, 'badge-gray'];
    return `<span class="badge ${clase}">${Utils.escapeHtml(texto)}</span>`;
  }

  function pintarTorneos(torneos) {
    const body  = $('torneosBody');
    const empty = $('torneosEmpty');
    if (!torneos.length) {
      body.innerHTML = '';
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');
    body.innerHTML = torneos.map(t => `
      <tr>
        <td>${Utils.escapeHtml(t.nombre)}</td>
        <td>${Utils.escapeHtml(t.disciplina)}</td>
        <td>${Utils.escapeHtml(t.equipo || 'Individual')}</td>
        <td>${badge(ESTADO_INSCRIPCION, t.inscripcion_estado)}</td>
        <td>${badge(ESTADO_TORNEO, t.estado)}</td>
      </tr>`).join('');
  }

  function pintarEquipos(equipos) {
    const grid  = $('equiposGrid');
    const empty = $('equiposEmpty');
    if (!equipos.length) {
      grid.innerHTML = '';
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');
    grid.innerHTML = equipos.map(e => `
      <div class="team-card">
        <div class="team-card__name">${Utils.escapeHtml(e.nombre)}</div>
        <div class="team-card__meta">
          ${Utils.escapeHtml(e.disciplina)} · ${Utils.escapeHtml(e.torneo)}
          ${Number(e.es_capitan) ? ' · Capitán' : ''}
        </div>
        <div style="margin-top:var(--space-3)">
          ${badge(ESTADO_TORNEO, e.torneo_estado)}
        </div>
      </div>`).join('');
  }

  /* ─── Formulario de datos ──────────────────────────── */

  function llenarFormulario(u) {
    $('pNombre').value    = u.nombre    || '';
    $('pApellido').value  = u.apellido  || '';
    $('pEmail').value     = u.email     || '';
    $('pFecha').value     = u.fecha_nac || '';
    $('pUbicacion').value = u.ubicacion || '';
    $('pBio').value       = u.bio       || '';
    $('pTwitter').value   = u.twitter_url   || '';
    $('pFacebook').value  = u.facebook_url  || '';
    $('pInstagram').value = u.instagram_url || '';
    actualizarContadorBio();
  }

  function contarPalabras(texto) {
    const limpio = texto.trim();
    return limpio === '' ? 0 : limpio.split(/\s+/).length;
  }

  function actualizarContadorBio() {
    const contador = $('bioCounter');
    const palabras = contarPalabras($('pBio').value);
    contador.textContent = `${palabras} / ${BIO_MAX_PALABRAS} palabras`;
    contador.classList.toggle('limit', palabras > BIO_MAX_PALABRAS);
  }

  function initFormularios() {
    $('pBio').addEventListener('input', actualizarContadorBio);

    /* "Restablecer" vuelve a los valores guardados, no a campos vacíos. */
    $('perfilForm').addEventListener('reset', (e) => {
      e.preventDefault();
      if (usuarioActual) llenarFormulario(usuarioActual);
    });

    $('perfilForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const palabras = contarPalabras($('pBio').value);
      if (palabras > BIO_MAX_PALABRAS) {
        Toast.error(`La descripción supera las ${BIO_MAX_PALABRAS} palabras (tiene ${palabras}).`);
        return;
      }
      try {
        const res = await Api.post('/api/perfil/actualizar', {
          nombre:         $('pNombre').value.trim(),
          apellido:       $('pApellido').value.trim(),
          email:          $('pEmail').value.trim(),
          fecha_nac:      $('pFecha').value,
          ubicacion:      $('pUbicacion').value.trim(),
          bio:            $('pBio').value.trim(),
          twitter_url:    $('pTwitter').value.trim(),
          facebook_url:   $('pFacebook').value.trim(),
          instagram_url:  $('pInstagram').value.trim(),
        });
        usuarioActual = res.usuario;
        pintarHero(usuarioActual);
        llenarFormulario(usuarioActual);
        Toast.success('Perfil actualizado.');
      } catch (err) {
        Toast.error(err.message);
      }
    });

    $('passForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const nueva = $('passNueva').value;
      if (nueva !== $('passConfirma').value) {
        Toast.error('Las contraseñas no coinciden.');
        return;
      }
      try {
        await Api.post('/api/perfil/password', {
          actual: $('passActual').value,
          nueva,
        });
        e.target.reset();
        Toast.success('Contraseña actualizada.');
      } catch (err) {
        Toast.error(err.message);
      }
    });
  }

  /* ─── Foto de perfil ───────────────────────────────── */

  function initAvatar() {
    const input = $('avatarInput');
    if (!input) return;
    const triggers = [$('avatarBtn'), $('heroAvatar')].filter(Boolean);
    triggers.forEach(el => el.addEventListener('click', () => input.click()));

    input.addEventListener('change', async () => {
      const file = input.files && input.files[0];
      input.value = '';
      if (!file) return;

      if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        Toast.error('Formato no admitido. Usá JPG, PNG o WebP.');
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        Toast.error('La imagen no puede superar los 2 MB.');
        return;
      }

      const data = new FormData();
      data.append('avatar', file);
      try {
        const res = await fetch('/api/perfil/avatar', {
          method: 'POST',
          body: data,
          credentials: 'same-origin',
          headers: Utils.csrfHeaders(),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'No se pudo subir la imagen.');
        usuarioActual.avatar_url = json.avatar_url;
        pintarAvatar($('heroAvatar'), usuarioActual);
        if ($('editAvatar')) pintarAvatar($('editAvatar'), usuarioActual);
        Toast.success('Foto de perfil actualizada.');
      } catch (err) {
        Toast.error(err.message);
      }
    });
  }

  /* ─── Carga inicial ────────────────────────────────── */

  function verificarRolAdmin(u) {
    const adminLink = $('sidebarAdminLink');
    const adminSec  = $('sidebarAdminSection');
    const esAdmin   = (u && u.rol === 'administrador');
    if (adminLink) adminLink.style.display = esAdmin ? 'flex' : 'none';
    if (adminSec)  adminSec.style.display  = esAdmin ? 'block' : 'none';
  }

  async function cargar() {
    try {
      const res = await Api.get('/api/perfil');
      usuarioActual = res.usuario;
      verificarRolAdmin(res.usuario);
      pintarHero(res.usuario);
      pintarStats(res.stats);
      pintarTorneos(res.torneos);
      pintarEquipos(res.equipos);
      llenarFormulario(res.usuario);
    } catch (err) {
      /* Api.get ya redirige al login si la sesión expiró (401). */
      Toast.error(err.message);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initFormularios();
    initAvatar();
    cargar();
  });

})();
