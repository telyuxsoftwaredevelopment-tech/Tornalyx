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
function initPasswordToggle() {
  document.querySelectorAll('[data-toggle-pass]').forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.dataset.togglePass || 'password';
      const input = document.getElementById(targetId);
      if (!input) return;
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      this.textContent = isText ? '👁' : '🙈';
    });
  });

  /* Fallback para el botón con id fijo en login.html */
  const legacyBtn = document.getElementById('togglePassword');
  if (legacyBtn && !legacyBtn.dataset.togglePass) {
    legacyBtn.addEventListener('click', function () {
      const input = document.getElementById('password');
      if (!input) return;
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      this.textContent = isText ? '👁' : '🙈';
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
      if (json.success && json.mfa_choose) {
        showChannelChooser(json.channels);
      } else if (json.success && json.twofa) {
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

/* ─── MFA: elegir canal (email/WhatsApp) + verificar código ─── */

/* Oculta credenciales y muestra el paso de elegir canal. */
function showChannelChooser(channels) {
  const card = document.getElementById('mfaChooseCard');
  if (!card) return;
  document.getElementById('credCard')?.classList.add('hidden');
  document.getElementById('otpCard')?.classList.add('hidden');
  card.classList.remove('hidden');

  channels = channels || {};
  const emailSpan = document.getElementById('chEmail');
  if (emailSpan) emailSpan.textContent = channels.email ? `(${channels.email})` : '';

  const waBtn  = document.getElementById('chWhatsappBtn');
  const waSpan = document.getElementById('chWhatsapp');
  if (waBtn) {
    if (channels.whatsapp) {
      waBtn.classList.remove('hidden');
      if (waSpan) waSpan.textContent = `(${channels.whatsapp})`;
    } else {
      waBtn.classList.add('hidden');
    }
  }
}

/* Oculta los pasos previos y muestra el paso del código. */
function showOtpStep(targetMasked) {
  const otp = document.getElementById('otpCard');
  if (!otp) return;
  document.getElementById('credCard')?.classList.add('hidden');
  document.getElementById('mfaChooseCard')?.classList.add('hidden');
  otp.classList.remove('hidden');
  const t = document.getElementById('otpTarget');
  if (t && targetMasked) t.textContent = targetMasked;
  document.getElementById('codigo')?.focus();
}

function initOtpFlow() {
  /* Solo en la página de login (donde existen estas tarjetas). */
  if (!document.getElementById('otpForm') && !document.getElementById('mfaChooseCard')) return;

  const errEl   = document.getElementById('otpError');
  const showErr = (m) => {
    if (errEl) { errEl.textContent = m; errEl.classList.remove('hidden'); }
    else authError(m);
  };
  const hideErr = () => errEl?.classList.add('hidden');

  const chooseErrEl  = document.getElementById('mfaChooseError');
  const showChooseErr = (m) => {
    if (chooseErrEl) { chooseErrEl.textContent = m; chooseErrEl.classList.remove('hidden'); }
    else authError(m);
  };

  /* Pide el código por el canal elegido y pasa al paso del código. */
  async function pedirCodigo(canal) {
    chooseErrEl?.classList.add('hidden');
    const data = new FormData();
    data.append('canal', canal);
    try {
      const json = await postForm('/login/codigo', data);
      if (json.success && json.twofa) {
        showOtpStep(json.target_masked);
      } else {
        showChooseErr(json.error || 'No se pudo enviar el código.');
      }
    } catch {
      showChooseErr('Error de conexión. Intentá de nuevo.');
    }
  }

  document.querySelectorAll('#mfaChooseCard [data-canal]').forEach(btn => {
    btn.addEventListener('click', () => pedirCodigo(btn.dataset.canal));
  });

  /* Al cargar, consultar si hay un desafío MFA pendiente (p. ej. tras
     registrarse y ser redirigido). Reemplaza al script inline que la CSP
     bloqueaba. */
  (async function checkPending() {
    try {
      const res  = await fetch('/api/2fa/estado', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();
      if (!json || !json.pending) return;
      if (json.step === 'sent') showOtpStep(json.target_masked);
      else showChannelChooser(json.channels);
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
      if (json.success) redirectByRole(json.rol);
      else showErr(json.error || 'Código incorrecto.');
    } catch {
      showErr('Error de conexión. Intentá de nuevo.');
    }
  });

  /* Reenviar por el mismo canal. */
  document.getElementById('resendBtn')?.addEventListener('click', async function (e) {
    e.preventDefault();
    if (this.style.pointerEvents === 'none') return;   // en enfriamiento
    hideErr();
    try {
      const json = await postForm('/login/reenviar', new FormData());
      if (json.success) {
        if (window.Tornalyx?.Toast) window.Tornalyx.Toast.success('Código reenviado.');
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
    document.getElementById('mfaChooseCard')?.classList.add('hidden');
    document.getElementById('credCard')?.classList.remove('hidden');
    const codigo = document.getElementById('codigo');
    if (codigo) codigo.value = '';
    hideErr();
  });

  /* Volver al login desde el paso de elegir canal. */
  document.getElementById('backToLoginFromChoose')?.addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('mfaChooseCard')?.classList.add('hidden');
    document.getElementById('credCard')?.classList.remove('hidden');
  });
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

  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  if (!fill || !label) return;

  const levels = [
    { w: '0%',    color: 'transparent',              text: 'Ingresa una contraseña' },
    { w: '25%',   color: 'var(--red-bright)',         text: 'Débil' },
    { w: '50%',   color: 'var(--color-warning,#f59e0b)', text: 'Regular' },
    { w: '75%',   color: '#eab308',                  text: 'Buena' },
    { w: '100%',  color: '#22c55e',                  text: 'Fuerte' }
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
    fill.style.background = lv.color;
    label.textContent     = lv.text;
    label.style.color     = lv.color;
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
    const rol         = document.getElementById('rolSelected');
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
    const telefono = document.getElementById('telefono');
    const telRaw   = (telefono?.value || '').trim();
    const telNorm  = (telRaw.startsWith('+') ? '+' : '') + telRaw.replace(/\D/g, '');
    if (!/^\+[1-9]\d{7,14}$/.test(telNorm)) {
      authError('Ingresá un teléfono válido en formato internacional, p. ej. +59899123456.');
      telefono?.focus();
      return;
    }

    /* Validaciones de cliente que el backend no puede inferir */
    if ((pass?.value || '') !== (passConfirm?.value || '')) {
      authError('Las contraseñas no coinciden.');
      return;
    }
    if (!rol?.value) {
      authError('Seleccioná un rol para continuar.');
      return;
    }
    if (terminos && !terminos.checked) {
      authError('Debés aceptar los términos de uso.');
      return;
    }

    /* Los campos viven fuera del <form>; se recogen por name. */
    const data = new FormData();
    ['nombre', 'apellido', 'email', 'telefono', 'password', 'fecha_nacimiento', 'rol']
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

/* ─── Selección de rol en registro ──────────────────── */
function initRoleSelection() {
  const roleCards = document.querySelectorAll('.role-card');
  if (!roleCards.length) return;

  roleCards.forEach(card => {
    card.addEventListener('click', function () {
      roleCards.forEach(c => {
        c.classList.remove('selected');
        c.setAttribute('aria-checked', 'false');
      });
      this.classList.add('selected');
      this.setAttribute('aria-checked', 'true');
      const hidden = document.getElementById('rolSelected');
      if (hidden) hidden.value = this.dataset.role;
    });
    card.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
    });
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
  initRegistroSubmit();
  initRoleSelection();
  initRealtimeValidation();
});
