/* =====================================================
   TORNALYX – validations.js
   Validaciones de formularios en el cliente
   Login, Registro, campos en tiempo real
   ===================================================== */

'use strict';

/* ─── Helpers de autenticación (AJAX) ────────────────── */
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/* Redirige al panel según el rol devuelto por el backend. */
function redirectByRole(rol) {
  const map = {
    administrador: '/admin/dashboard',
    organizador:   '/organizador/dashboard',
    participante:  '/perfil',
  };
  window.location.href = map[rol] || '/';
}

/* Muestra un mensaje de error (toast si existe, alert como fallback). */
function authError(msg) {
  if (window.Tornalyx?.Toast) window.Tornalyx.Toast.error(msg);
  else alert(msg);
}

/* Lee el valor de una cookie por nombre (devuelve '' si no existe). */
function getCookie(name) {
  const match = document.cookie.match(
    new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
  );
  return match ? decodeURIComponent(match[1]) : '';
}

/* POST de un FormData con token CSRF y parseo de la respuesta JSON. */
async function postForm(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': getCookie('XSRF-TOKEN'),
    },
    body: data,
  });
  return res.json();
}

/* ─── Toggle visibilidad de contraseña ──────────────── */
/* El ícono es siempre el ojo; el estado "visible" se marca con la clase
   .is-visible (estilo en CSS) y el aria-label, sin cambiar de emoji. */
function togglePassVisibility(btn, input) {
  if (!input) return;
  const mostrar = input.type === 'password';
  input.type = mostrar ? 'text' : 'password';
  btn.classList.toggle('is-visible', mostrar);
  btn.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
}

function initPasswordToggle() {
  document.querySelectorAll('[data-toggle-pass]').forEach(btn => {
    btn.addEventListener('click', function () {
      togglePassVisibility(this, document.getElementById(this.dataset.togglePass || 'password'));
    });
  });

  /* Fallback para el botón con id fijo en login.html */
  const legacyBtn = document.getElementById('togglePassword');
  if (legacyBtn && !legacyBtn.dataset.togglePass) {
    legacyBtn.addEventListener('click', function () {
      togglePassVisibility(this, document.getElementById('password'));
    });
  }
}

/* ─── Validación del formulario de Login ─────────────── */
function initLoginValidation() {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  /* Validación en tiempo real */
  form.querySelectorAll('input').forEach(input => {
    input.addEventListener('blur', () => validateField(input));
    input.addEventListener('input', () => {
      const errEl = document.getElementById(input.id + 'Error');
      if (errEl) errEl.classList.add('hidden');
      input.classList.remove('input-error');
    });
  });

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    let valid = true;

    const email    = document.getElementById('email');
    const password = document.getElementById('password');
    const emailErr = document.getElementById('emailError');
    const passErr  = document.getElementById('passwordError');

    if (emailErr) emailErr.classList.add('hidden');
    if (passErr)  passErr.classList.add('hidden');

    if (!email?.value || !EMAIL_RE.test(email.value)) {
      if (emailErr) emailErr.classList.remove('hidden');
      email?.classList.add('input-error');
      if (valid) email?.focus();
      valid = false;
    }
    if (!password?.value) {
      if (passErr) passErr.classList.remove('hidden');
      password?.classList.add('input-error');
      if (valid) password?.focus();
      valid = false;
    }
    if (!valid) return;

    try {
      const json = await postForm('/login', new FormData(form));
      if (json.success && json.twofa) {
        showOtpStep(json.target_masked);
      } else if (json.success) {
        redirectByRole(json.rol);
      } else {
        let msg = json.error || 'No se pudo iniciar sesión.';
        if (typeof json.intentos_restantes === 'number') {
          msg += ` Intentos restantes: ${json.intentos_restantes}.`;
        }
        authError(msg);
      }
    } catch {
      authError('Error de conexión. Intentá de nuevo.');
    }
  });

  function validateField(input) {
    const errEl = document.getElementById(input.id + 'Error');
    if (!errEl) return;
    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    let invalid = false;
    if (input.type === 'email')    invalid = !EMAIL_RE.test(input.value);
    if (input.required)            invalid = invalid || !input.value.trim();
    errEl.classList.toggle('hidden', !invalid);
    input.classList.toggle('input-error', invalid);
  }
}

/* ─── MFA: verificar código (2FA por email) ─── */

/* Oculta los pasos previos y muestra el paso del código. */
function showOtpStep(targetMasked) {
  const otp = document.getElementById('otpCard');
  if (!otp) return;
  document.getElementById('credCard')?.classList.add('hidden');
  otp.classList.remove('hidden');
  const t = document.getElementById('otpTarget');
  if (t && targetMasked) t.textContent = targetMasked;
  document.querySelector('.otp-box')?.focus();
}

function initOtpFlow() {
  /* Solo en la página de login (donde existe la tarjeta del código). */
  if (!document.getElementById('otpForm')) return;

  const errEl   = document.getElementById('otpError');
  const showErr = (m) => {
    if (errEl) { errEl.textContent = m; errEl.classList.remove('hidden'); }
    else authError(m);
  };
  const hideErr = () => errEl?.classList.add('hidden');

  /* ── Casilleros del código: cada dígito en su propia caja, con
     autoavance/retroceso/pegado. El valor combinado vive en el input
     oculto #codigo, que es lo único que lee el resto del flujo. */
  const boxes  = Array.from(document.querySelectorAll('.otp-box'));
  const hidden = document.getElementById('codigo');
  const track  = Array.from(document.getElementById('otpTrack')?.children || []);

  function syncOtpBoxes() {
    if (!hidden) return;
    const value = boxes.map(b => b.value).join('');
    hidden.value = value;
    boxes.forEach(b => b.classList.toggle('is-filled', !!b.value));
    track.forEach((seg, i) => seg.classList.toggle('is-lit', i < value.length));
  }

  function clearOtpBoxes() {
    boxes.forEach(b => b.value = '');
    syncOtpBoxes();
  }

  boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/[^0-9]/g, '').slice(-1);
      if (box.value && boxes[i + 1]) boxes[i + 1].focus();
      syncOtpBoxes();
    });
    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && boxes[i - 1]) boxes[i - 1].focus();
    });
    box.addEventListener('paste', (e) => {
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
      if (!text) return;
      e.preventDefault();
      text.slice(0, boxes.length).split('').forEach((d, j) => { if (boxes[j]) boxes[j].value = d; });
      syncOtpBoxes();
      (boxes[Math.min(text.length, boxes.length - 1)])?.focus();
    });
  });

  /* Al cargar, consultar si hay un desafío MFA pendiente (p. ej. tras
     registrarse y ser redirigido). Reemplaza al script inline que la CSP
     bloqueaba. */
  (async function checkPending() {
    try {
      const res  = await fetch('/api/2fa/estado', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();
      if (!json || !json.pending) return;
      showOtpStep(json.target_masked);
    } catch { /* sin pendiente o error: queda el login normal */ }
  })();

  /* Verificar el código. */
  document.getElementById('otpForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    hideErr();
    const codigo = (document.getElementById('codigo')?.value || '').trim();
    if (!/^[0-9]{6}$/.test(codigo)) {
      showErr('Ingresá el código de 6 dígitos.');
      return;
    }
    const data = new FormData();
    data.append('codigo', codigo);
    try {
      const json = await postForm('/login/verificar', data);
      if (json.success) {
        redirectByRole(json.rol);
      } else {
        showErr(json.error || 'Código incorrecto.');
        clearOtpBoxes();
        boxes[0]?.focus();
      }
    } catch {
      showErr('Error de conexión. Intentá de nuevo.');
    }
  });

  /* Reenviar el código por email. */
  document.getElementById('resendBtn')?.addEventListener('click', async function (e) {
    e.preventDefault();
    if (this.style.pointerEvents === 'none') return;   // en enfriamiento
    hideErr();
    try {
      const json = await postForm('/login/reenviar', new FormData());
      if (json.success) {
        if (window.Tornalyx?.Toast) window.Tornalyx.Toast.success('Código reenviado.');
        clearOtpBoxes();
        if (json.target_masked) showOtpStep(json.target_masked);
        startResendCooldown(60);
      } else {
        showErr(json.error || 'No se pudo reenviar el código.');
      }
    } catch {
      showErr('Error de conexión. Intentá de nuevo.');
    }
  });

  /* Volver al login desde el paso del código. */
  document.getElementById('backToLogin')?.addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('otpCard')?.classList.add('hidden');
    document.getElementById('credCard')?.classList.remove('hidden');
    clearOtpBoxes();
    hideErr();
  });

  syncOtpBoxes();
}

/* Deshabilita el enlace "Reenviar" durante unos segundos. */
function startResendCooldown(seconds) {
  const link = document.getElementById('resendBtn');
  const span = document.getElementById('resendCooldown');
  if (!link) return;
  let s = seconds;
  link.style.pointerEvents = 'none';
  link.style.opacity = '.5';
  (function tick() {
    if (span) span.textContent = s > 0 ? ` (${s}s)` : '';
    if (s <= 0) {
      link.style.pointerEvents = '';
      link.style.opacity = '';
      return;
    }
    s--;
    setTimeout(tick, 1000);
  })();
}

/* ─── Medidor de fortaleza de contraseña ─────────────── */
function initPasswordStrength() {
  const passInput = document.getElementById('passReg');
  if (!passInput) return;

  const card  = document.getElementById('strengthCard');
  const icon  = document.getElementById('strengthIcon');
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  if (!fill || !label) return;

  const levels = [
    { w: '0%',   tier: 'var(--muted-2)',                icon: '🔓', text: 'Ingresa una contraseña' },
    { w: '25%',  tier: 'var(--red-bright)',              icon: '📎', text: 'Débil — se descifra al toque' },
    { w: '50%',  tier: 'var(--color-warning,#f59e0b)',   icon: '🔑', text: 'Regular' },
    { w: '75%',  tier: '#eab308',                        icon: '🔒', text: 'Buena' },
    { w: '100%', tier: '#22c55e',                        icon: '🛡️', text: 'Fuerte' }
  ];

  passInput.addEventListener('input', function () {
    const val = this.value;
    let score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const lv = levels[score] || levels[0];
    fill.style.width      = lv.w;
    fill.style.background = lv.tier;
    label.textContent     = lv.text;
    if (icon) icon.textContent = lv.icon;
    if (card) {
      card.style.setProperty('--tier', lv.tier);
      card.toggleAttribute('data-tier', !!val);
    }
  });
}

/* ─── Requisitos de contraseña (checklist en vivo) ───── */
/* Refleja exactamente lo que valida el backend (AuthController::passwordEsFuerte):
   8+ caracteres, mayúscula, minúscula y número. Visible desde el inicio. */
function initPasswordRequirements() {
  const input = document.getElementById('passReg');
  const list  = document.getElementById('passReqs');
  if (!input || !list) return;

  const rules = {
    length: v => v.length >= 8,
    upper:  v => /[A-Z]/.test(v),
    lower:  v => /[a-z]/.test(v),
    number: v => /[0-9]/.test(v),
  };

  const evaluate = () => {
    const v = input.value;
    list.querySelectorAll('li[data-req]').forEach(li => {
      const rule = rules[li.dataset.req];
      li.classList.toggle('met', !!(rule && rule(v)));
    });
  };

  input.addEventListener('input', evaluate);
  evaluate(); // estado inicial
}

/* ─── Login social (Google / GitHub) ─────────────────── */
/* Todavía no hay backend de OAuth: los botones avisan que está en camino
   en vez de simular un inicio de sesión que no existe. */
function initSocialButtons() {
  document.querySelectorAll('[data-social]').forEach(btn => {
    btn.addEventListener('click', function () {
      const nombre = this.dataset.social === 'google' ? 'Google' : 'GitHub';
      const msg = `Inicio de sesión con ${nombre}: próximamente.`;
      if (window.Tornalyx?.Toast) window.Tornalyx.Toast.info(msg);
      else alert(msg);
    });
  });
}

/* ─── Envío del formulario de Registro (AJAX) ────────── */
function initRegistroSubmit() {
  const form = document.getElementById('registroForm');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const nombre      = document.getElementById('nombre');
    const email       = document.getElementById('emailReg');
    const fecha       = document.getElementById('fechaNac');
    const pass        = document.getElementById('passReg');
    const passConfirm = document.getElementById('passConfirm');
    const terminos    = document.getElementById('terminos');

    /* Como ahora todo está en una sola pantalla (sin el paso "Continuar" que
       antes validaba estos campos), los chequeamos acá antes de enviar. */
    if (!nombre?.value.trim()) {
      authError('Ingresá tu nombre.');
      nombre?.focus();
      return;
    }
    if (!email?.value || !EMAIL_RE.test(email.value)) {
      authError('Ingresá un correo electrónico válido.');
      email?.focus();
      return;
    }
    const pv = pass?.value || '';
    if (pv.length < 8 || !/[A-Z]/.test(pv) || !/[a-z]/.test(pv) || !/[0-9]/.test(pv)) {
      authError('La contraseña no cumple los requisitos: 8+ caracteres, mayúscula, minúscula y número.');
      pass?.focus();
      return;
    }
    if (!fecha?.value) {
      authError('Ingresá tu fecha de nacimiento.');
      fecha?.focus();
      return;
    }
    /* Validaciones de cliente que el backend no puede inferir */
    if ((pass?.value || '') !== (passConfirm?.value || '')) {
      authError('Las contraseñas no coinciden.');
      return;
    }
    if (terminos && !terminos.checked) {
      authError('Debés aceptar los términos de uso.');
      return;
    }

    /* Los campos viven fuera del <form>; se recogen por name. */
    const data = new FormData();
    ['nombre', 'apellido', 'email', 'password', 'fecha_nacimiento', 'rol']
      .forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el) data.append(name, el.value);
      });

    try {
      const json = await postForm('/registro', data);
      if (json.success && json.twofa) {
        // 2FA obligatorio: la cuenta se creó pero falta verificar el código.
        // Vamos al login, que mostrará el paso del código (desafío en sesión).
        if (window.Tornalyx?.Toast) window.Tornalyx.Toast.success('¡Cuenta creada! Te enviamos un código.');
        window.location.href = '/login';
      } else if (json.success) {
        if (window.Tornalyx?.Toast) window.Tornalyx.Toast.success('¡Cuenta creada!');
        redirectByRole(json.rol);
      } else {
        authError(json.error || 'No se pudo crear la cuenta.');
      }
    } catch {
      authError('Error de conexión. Intentá de nuevo.');
    }
  });
}

/* ─── Validación en tiempo real genérica ─────────────── */
function initRealtimeValidation() {
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  document.querySelectorAll('input[type="email"]').forEach(input => {
    input.addEventListener('blur', function () {
      const ok = EMAIL_RE.test(this.value);
      this.classList.toggle('input-error', this.value && !ok);
    });
  });
  document.querySelectorAll('input[required]').forEach(input => {
    input.addEventListener('blur', function () {
      this.classList.toggle('input-error', !this.value.trim());
    });
    input.addEventListener('input', function () {
      if (this.value.trim()) this.classList.remove('input-error');
    });
  });
}

/* ─── Init ───────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initPasswordToggle();
  initLoginValidation();
  initOtpFlow();
  initPasswordStrength();
  initPasswordRequirements();
  initSocialButtons();
  initRegistroSubmit();
  initRealtimeValidation();
});
