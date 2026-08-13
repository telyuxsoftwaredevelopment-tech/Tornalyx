/* =====================================================
   TORNALYX – auth-flip.js
   Flip 3D entre las tarjetas de Login y Registro
   ===================================================== */

'use strict';

(function () {
  const flipEl  = document.getElementById('authFlip');
  const frontEl = document.getElementById('authFaceLogin');
  const backEl  = document.getElementById('authFaceSignup');
  if (!flipEl || !frontEl || !backEl) return;

  const ROUTES = { login: '/login', signup: '/registro' };

  function modeFromPath(path) {
    return path.replace(/\/+$/, '') === '/registro' ? 'signup' : 'login';
  }

  function setInert(el, hidden) {
    el.toggleAttribute('inert', hidden);
    el.setAttribute('aria-hidden', String(hidden));
  }

  function focusFirstField(el) {
    const field = el.querySelector('input:not([type="hidden"])');
    if (field) field.focus({ preventScroll: true });
  }

  function activeFace(flipped) {
    return flipped ? backEl : frontEl;
  }

  function syncHeight(flipped) {
    flipEl.style.height = activeFace(flipped).scrollHeight + 'px';
  }

  function applyMode(mode, { animate = true, focus = false, push = false } = {}) {
    const flipped = mode === 'signup';

    if (!animate) flipEl.classList.add('no-anim');

    syncHeight(flipped);
    flipEl.classList.toggle('is-flipped', flipped);
    frontEl.classList.toggle('is-active', !flipped);
    backEl.classList.toggle('is-active', flipped);
    setInert(frontEl, flipped);
    setInert(backEl, !flipped);

    if (!animate) {
      void flipEl.offsetHeight; // fuerza reflow antes de reactivar la transición
      flipEl.classList.remove('no-anim');
    }

    if (push) {
      const url = ROUTES[mode];
      if (location.pathname.replace(/\/+$/, '') !== url) {
        history.pushState({ authMode: mode }, '', url);
      }
    }

    if (focus) {
      setTimeout(() => focusFirstField(activeFace(flipped)), animate ? 420 : 0);
    }
  }

  document.querySelectorAll('[data-auth-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      applyMode(btn.dataset.authToggle, { animate: true, focus: true, push: true });
    });
  });

  window.addEventListener('popstate', () => {
    applyMode(modeFromPath(location.pathname), { animate: true, focus: true, push: false });
  });

  window.addEventListener('resize', () => {
    syncHeight(flipEl.classList.contains('is-flipped'));
  });

  /* Recalcula el alto si cambia el contenido del lado activo (p. ej. aparece
     un error o el paso de verificación 2FA reemplaza al formulario). */
  new MutationObserver(() => {
    syncHeight(flipEl.classList.contains('is-flipped'));
  }).observe(flipEl, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'hidden'] });

  applyMode(modeFromPath(location.pathname), { animate: false });
})();
