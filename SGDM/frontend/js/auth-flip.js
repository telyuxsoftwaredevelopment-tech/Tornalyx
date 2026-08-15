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

  function applyMode(mode, { animate = true, focus = false, push = false } = {}) {
    const flipped = mode === 'signup';

    if (!animate) flipEl.classList.add('no-anim');

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
      setTimeout(() => focusFirstField(flipped ? backEl : frontEl), animate ? 420 : 0);
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

  applyMode(modeFromPath(location.pathname), { animate: false });
})();
