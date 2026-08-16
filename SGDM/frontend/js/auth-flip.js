/* =====================================================
   TORNALYX – auth-flip.js
   Control del Panel Deslizante (Sliding Overlay)
   Login ⇆ Registro con animación de transición fluida
   ===================================================== */

'use strict';

(function () {
  const container = document.getElementById('authContainer');
  const signInBtn = document.getElementById('signInBtn');
  const signUpBtn = document.getElementById('signUpBtn');
  const mobileSignInTab = document.getElementById('mobileSignInTab');
  const mobileSignUpTab = document.getElementById('mobileSignUpTab');
  const signInPanel = document.getElementById('authFaceLogin');
  const signUpPanel = document.getElementById('authFaceSignup');

  if (!container) return;

  const ROUTES = { login: '/login', signup: '/registro' };

  function modeFromPath(path) {
    const clean = (path || '').toLowerCase().replace(/\/+$/, '');
    return clean.endsWith('/registro') ? 'signup' : 'login';
  }

  function focusFirstField(panel) {
    if (!panel) return;
    const field = panel.querySelector('input:not([type="hidden"]):not([disabled])');
    if (field) field.focus({ preventScroll: true });
  }

  function setMode(mode, { push = false, focus = false } = {}) {
    const isSignUp = mode === 'signup';

    container.classList.toggle('right-panel-active', isSignUp);

    if (mobileSignInTab && mobileSignUpTab) {
      mobileSignInTab.classList.toggle('active', !isSignUp);
      mobileSignUpTab.classList.toggle('active', isSignUp);
    }

    if (push) {
      const url = ROUTES[mode];
      if (location.pathname.replace(/\/+$/, '') !== url) {
        history.pushState({ authMode: mode }, '', url);
      }
    }

    if (focus) {
      setTimeout(() => {
        focusFirstField(isSignUp ? signUpPanel : signInPanel);
      }, 350);
    }
  }

  // Eventos de botones Desktop Overlay
  signUpBtn?.addEventListener('click', () => setMode('signup', { push: true, focus: true }));
  signInBtn?.addEventListener('click', () => setMode('login', { push: true, focus: true }));

  // Eventos de tabs Mobile
  mobileSignUpTab?.addEventListener('click', () => setMode('signup', { push: true, focus: true }));
  mobileSignInTab?.addEventListener('click', () => setMode('login', { push: true, focus: true }));

  // Disparadores genéricos data-auth-toggle
  document.querySelectorAll('[data-auth-toggle]').forEach(el => {
    el.addEventListener('click', (e) => {
      const targetMode = el.dataset.authToggle;
      if (targetMode) {
        e.preventDefault();
        setMode(targetMode, { push: true, focus: true });
      }
    });
  });

  // Navegación con historial (Atrás / Adelante del navegador)
  window.addEventListener('popstate', () => {
    setMode(modeFromPath(location.pathname), { push: false });
  });

  // Inicializar estado según la ruta actual
  setMode(modeFromPath(location.pathname), { push: false });
})();

