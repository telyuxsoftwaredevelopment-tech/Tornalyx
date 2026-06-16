/* =====================================================
   TORNALYX – validations.js
   Validaciones de formularios en el cliente
   Login, Registro, campos en tiempo real
   ===================================================== */

'use strict';

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

  form.addEventListener('submit', function (e) {
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
    if (!valid) e.preventDefault();
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
  initPasswordStrength();
  initRegistroStepper();
  initRoleSelection();
  initRealtimeValidation();
});
