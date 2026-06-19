/* =====================================================
   TORNALYX – SGDM | Script Principal
   Vanilla JS – Sin dependencias externas
   ===================================================== */

'use strict';

/* ─── Mobile Nav (burger) ───────────────────────────── */
function initMobileNav() {
  const burger    = document.getElementById('burgerBtn');
  const mobileNav = document.getElementById('mobileNav');
  const closeBtn  = document.getElementById('mobileClose');
  if (!burger || !mobileNav) return;

  function openMenu() {
    burger.classList.add('open');
    burger.setAttribute('aria-expanded', 'true');
    mobileNav.style.display = 'flex';
    requestAnimationFrame(() => mobileNav.classList.add('open'));
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    burger.classList.remove('open');
    burger.setAttribute('aria-expanded', 'false');
    mobileNav.classList.remove('open');
    document.body.style.overflow = '';
    setTimeout(() => { mobileNav.style.display = 'none'; }, 260);
  }

  burger.addEventListener('click', () =>
    mobileNav.classList.contains('open') ? closeMenu() : openMenu()
  );
  if (closeBtn) closeBtn.addEventListener('click', closeMenu);
  mobileNav.querySelectorAll('a').forEach(l => l.addEventListener('click', closeMenu));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
}

/* ─── Scroll Reveal ─────────────────────────────────── */
function initScrollReveal() {
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el));
  } else {
    document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('visible'));
  }
}

/* ─── Tabs genéricos [role="tab"] ───────────────────── */
function initTabs() {
  document.querySelectorAll('[role="tab"]').forEach(tab => {
    tab.addEventListener('click', function () {
      const parent = this.closest('[role="tablist"]') || document;
      parent.querySelectorAll('[role="tab"]').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      const panel = document.getElementById('tab-' + this.dataset.tab);
      if (panel) panel.classList.add('active');
    });
  });
}

/* ─── Tabs de audiencia (index.html) ────────────────── */
function initAudTabs() {
  const tabBtns = document.querySelectorAll('#audTabs .aud-tab');
  const panels  = document.querySelectorAll('#audPanels .aud-panel');
  if (!tabBtns.length) return;
  tabBtns.forEach(btn => btn.addEventListener('click', () => {
    tabBtns.forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    const i = +btn.dataset.aud;
    panels.forEach((p, j) => p.classList.toggle('on', i === j));
  }));
}

/* ─── Toast Notifications ────────────────────────────── */
const Toast = {
  container: null,

  init() {
    this.container = document.createElement('div');
    this.container.id = 'toast-container';
    Object.assign(this.container.style, {
      position: 'fixed', bottom: '1.5rem', right: '1.5rem',
      display: 'flex', flexDirection: 'column', gap: '0.5rem',
      zIndex: '9999', maxWidth: '360px'
    });
    document.body.appendChild(this.container);
  },

  show(message, type = 'info', duration = 4000) {
    if (!this.container) this.init();
    const colors = {
      success: { bg: 'rgba(5,150,105,.15)', border: '#059669', text: '#6ee7b7' },
      error:   { bg: 'rgba(220,38,38,.15)', border: 'var(--red)', text: '#fca5a5' },
      warning: { bg: 'rgba(217,119,6,.15)', border: '#d97706', text: '#fcd34d' },
      info:    { bg: 'rgba(37,99,235,.15)', border: '#2563eb', text: '#93c5fd' }
    };
    const c = colors[type] || colors.info;
    const toast = document.createElement('div');
    toast.setAttribute('role', 'alert');
    Object.assign(toast.style, {
      padding: '0.75rem 1rem', borderRadius: '10px',
      border: `1px solid ${c.border}`, background: c.bg, color: c.text,
      fontSize: '0.875rem', fontWeight: '500',
      boxShadow: '0 4px 24px rgba(0,0,0,.4)',
      opacity: '0', transform: 'translateX(1rem)',
      transition: 'all 250ms ease', cursor: 'pointer'
    });
    toast.textContent = message;
    this.container.appendChild(toast);
    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateX(0)';
    });
    const remove = () => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(1rem)';
      setTimeout(() => toast.remove(), 300);
    };
    toast.addEventListener('click', remove);
    setTimeout(remove, duration);
  },

  success: (msg) => Toast.show(msg, 'success'),
  error:   (msg) => Toast.show(msg, 'error'),
  warning: (msg) => Toast.show(msg, 'warning'),
  info:    (msg) => Toast.show(msg, 'info')
};

/* ─── Utilidades ─────────────────────────────────────── */
const Utils = {
  /**
   * Formatea fecha ISO a español.
   * @param {string} isoDate
   * @returns {string}
   */
  formatDate(isoDate) {
    return new Date(isoDate).toLocaleDateString('es-UY', {
      day: '2-digit', month: 'long', year: 'numeric'
    });
  },

  /**
   * Trunca texto a N caracteres.
   * @param {string} text
   * @param {number} max
   * @returns {string}
   */
  truncate(text, max = 100) {
    return text.length > max ? text.slice(0, max) + '…' : text;
  },

  /**
   * Escapa HTML para prevenir XSS.
   * @param {string} str
   * @returns {string}
   */
  escapeHtml(str) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(str).replace(/[&<>"']/g, m => map[m]);
  },

  /**
   * Lee una cookie por nombre.
   * @param {string} name
   * @returns {string}
   */
  getCookie(name) {
    const match = document.cookie.match(
      new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
    );
    return match ? decodeURIComponent(match[1]) : '';
  },

  /**
   * Headers con el token CSRF para peticiones que cambian estado.
   * @returns {Object}
   */
  csrfHeaders() {
    return {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': this.getCookie('XSRF-TOKEN'),
    };
  },

  /**
   * Debounce: retrasa ejecución hasta que pase wait ms.
   * @param {Function} fn
   * @param {number} wait
   * @returns {Function}
   */
  debounce(fn, wait = 300) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), wait);
    };
  }
};

/* ─── Modal ──────────────────────────────────────────── */
const Modal = {
  open(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  },
  close(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  },
  init() {
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.modal');
        if (modal) Modal.close(modal.id);
      });
    });
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', e => {
        if (e.target === modal) Modal.close(modal.id);
      });
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal[style*="flex"]').forEach(m => Modal.close(m.id));
      }
    });
  }
};

/* ─── Fallback de imágenes (reemplaza onerror inline) ──── */
/* Se hace por JS externo para cumplir la CSP (script-src 'self'). */
function initImageFallbacks() {
  document.querySelectorAll('img[data-fallback]').forEach(img => {
    const applyFallback = () => {
      switch (img.dataset.fallback) {
        case 'hide':
          img.style.display = 'none';
          break;
        case 'mark':
          img.outerHTML = '<div class="mark-fallback">TX</div>';
          break;
        case 'logo':
          img.outerHTML = '<div class="logo-mark-fallback">TX</div>';
          break;
      }
    };
    img.addEventListener('error', applyFallback);
    /* La imagen pudo fallar antes de registrar el listener. */
    if (img.complete && img.naturalWidth === 0) applyFallback();
  });
}

/* ─── Botones que activan un tab (reemplaza onclick inline) ─ */
function initTabShortcuts() {
  document.querySelectorAll('[data-goto-tab]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.querySelector(`[data-tab="${btn.dataset.gotoTab}"]`);
      if (target) target.click();
    });
  });
}

/* ─── Init global ────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initMobileNav();
  initScrollReveal();
  initTabs();
  initAudTabs();
  initImageFallbacks();
  initTabShortcuts();
  Toast.init();
  Modal.init();
});

window.Tornalyx = { Toast, Utils, Modal };
