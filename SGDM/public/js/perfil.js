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

  function pintarHero(u) {
    $('heroNombre').textContent = `${u.nombre} ${u.apellido}`.trim();
    $('heroRol').textContent = { participante: 'Participante', organizador: 'Organizador', administrador: 'Administrador' }[u.rol] || u.rol;

    const bio = $('heroBio');
    if (u.bio) {
      bio.textContent = u.bio;
      bio.classList.remove('hidden');
    } else {
      bio.classList.add('hidden');
    }

    const meta = [];
    if (u.created_at) {
      const alta = new Date(String(u.created_at).replace(' ', 'T'));
      if (!isNaN(alta)) {
        meta.push('📅 Miembro desde ' + alta.toLocaleDateString('es-UY', { month: 'short', year: 'numeric' }));
      }
    }
    if (u.ubicacion) meta.push('📍 ' + u.ubicacion);
    meta.push('✉️ ' + u.email);
    $('heroMeta').innerHTML = meta.map(m => `<span>${Utils.escapeHtml(m)}</span>`).join('');

    pintarAvatar($('heroAvatar'), u);
    pintarAvatar($('editAvatar'), u);
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
          nombre:    $('pNombre').value.trim(),
          apellido:  $('pApellido').value.trim(),
          email:     $('pEmail').value.trim(),
          fecha_nac: $('pFecha').value,
          ubicacion: $('pUbicacion').value.trim(),
          bio:       $('pBio').value.trim(),
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
    $('avatarBtn').addEventListener('click', () => input.click());

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
        pintarAvatar($('editAvatar'), usuarioActual);
        Toast.success('Foto de perfil actualizada.');
      } catch (err) {
        Toast.error(err.message);
      }
    });
  }

  /* ─── Carga inicial ────────────────────────────────── */

  async function cargar() {
    try {
      const res = await Api.get('/api/perfil');
      usuarioActual = res.usuario;
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
