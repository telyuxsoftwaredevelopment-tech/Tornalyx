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
      if (json.success && json.twofa) {
        showOtpStep(json.email_masked);
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

/* ─── Verificación en dos pasos (2FA por email) ──────── */

/* Muestra el paso del código y oculta el de credenciales. */
function showOtpStep(emailMasked) {
  const otp = document.getElementById('otpCard');
  if (!otp) return;
  document.getElementById('credCard')?.classList.add('hidden');
  otp.classList.remove('hidden');
  const emailEl = document.getElementById('otpEmail');
  if (emailEl && emailMasked) emailEl.textContent = emailMasked;
  document.getElementById('codigo')?.focus();
}

function initOtpFlow() {
  const form = document.getElementById('otpForm');
  if (!form) return;

  const errEl   = document.getElementById('otpError');
  const showErr = (m) => {
    if (errEl) { errEl.textContent = m; errEl.classList.remove('hidden'); }
    else authError(m);
  };
  const hideErr = () => errEl?.classList.add('hidden');

  /* Si el servidor dejó un desafío pendiente (p. ej. tras registrarse o
     recargar), mostramos directamente el paso del código. */
  if (window.__2FA_PENDING__) {
    showOtpStep(window.__2FA_EMAIL__ || '');
  }

  form.addEventListener('submit', async function (e) {
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

  document.getElementById('resendBtn')?.addEventListener('click', async function (e) {
    e.preventDefault();
    if (this.style.pointerEvents === 'none') return;   // en enfriamiento
    hideErr();
    try {
      const json = await postForm('/login/reenviar', new FormData());
      if (json.success) {
        if (window.Tornalyx?.Toast) window.Tornalyx.Toast.success('Código reenviado.');
        startResendCooldown(60);
      } else {
        showErr(json.error || 'No se pudo reenviar el código.');
      }
    } catch {
      showErr('Error de conexión. Intentá de nuevo.');
    }
  });

  document.getElementById('backToLogin')?.addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('otpCard')?.classList.add('hidden');
    document.getElementById('credCard')?.classList.remove('hidden');
    const codigo = document.getElementById('codigo');
    if (codigo) codigo.value = '';
    hideErr();
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

/* ─── Stepper de registro (2 pasos) ─────────────────── */
function initRegistroStepper() {
  const btnNext = document.getElementById('btnNext');
  const btnBack = document.getElementById('btnBack');
  if (!btnNext) return;

  btnNext.addEventListener('click', function () {
    /* Validar paso 1 antes de avanzar */
    const step1Fields = document.querySelectorAll('#formStep1 [required]');
    let valid = true;
    step1Fields.forEach(field => {
      if (!field.value.trim()) {
        field.classList.add('input-error');
        valid = false;
      } else {
        field.classList.remove('input-error');
      }
    });
    if (!valid) return;

    document.getElementById('formStep1')?.classList.add('hidden');
    document.getElementById('formStep2')?.classList.remove('hidden');
    document.getElementById('step1Ind')?.classList.remove('active');
    document.getElementById('step1Ind')?.classList.add('done');
    const s1Circle = document.getElementById('step1Circle');
    if (s1Circle) s1Circle.textContent = '✓';
    document.getElementById('step2Ind')?.classList.add('active');
    document.getElementById('line1')?.classList.add('done');
  });

  if (btnBack) {
    btnBack.addEventListener('click', function () {
      document.getElementById('formStep2')?.classList.add('hidden');
      document.getElementById('formStep1')?.classList.remove('hidden');
      document.getElementById('step2Ind')?.classList.remove('active');
      document.getElementById('step1Ind')?.classList.add('active');
      document.getElementById('step1Ind')?.classList.remove('done');
      const s1Circle = document.getElementById('step1Circle');
      if (s1Circle) s1Circle.textContent = '1';
      document.getElementById('line1')?.classList.remove('done');
    });
  }
}

/* ─── Envío del formulario de Registro (AJAX) ────────── */
function initRegistroSubmit() {
  const form = document.getElementById('registroForm');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const pass        = document.getElementById('passReg');
    const passConfirm = document.getElementById('passConfirm');
    const rol         = document.getElementById('rolSelected');
    const terminos    = document.getElementById('terminos');

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
  initRegistroStepper();
  initRegistroSubmit();
  initRoleSelection();
  initRealtimeValidation();
});
