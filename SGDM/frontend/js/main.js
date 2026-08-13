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

    /* Los estilos inline no pueden declarar @keyframes; se inyecta una vez
       para la barra de cuenta regresiva de cada toast. */
    if (!document.getElementById('toast-keyframes')) {
      const style = document.createElement('style');
      style.id = 'toast-keyframes';
      style.textContent = '@keyframes toast-countdown { from { transform: scaleX(1); } to { transform: scaleX(0); } }';
      document.head.appendChild(style);
    }
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
      position: 'relative', overflow: 'hidden',
      padding: '0.75rem 1rem', borderRadius: '10px',
      border: `1px solid ${c.border}`, background: c.bg, color: c.text,
      fontSize: '0.875rem', fontWeight: '500',
      boxShadow: '0 4px 24px rgba(0,0,0,.4)',
      opacity: '0', transform: 'translateX(1rem)',
      transition: 'all 250ms ease', cursor: 'pointer'
    });
    toast.textContent = message;

    /* Barra de vigencia: se pausa (visual y realmente) si el mouse está
       encima, para no perderse un mensaje largo a mitad de lectura. */
    const bar = document.createElement('div');
    Object.assign(bar.style, {
      position: 'absolute', left: '0', bottom: '0', width: '100%', height: '2px',
      background: c.border, transformOrigin: 'left',
    });
    toast.appendChild(bar);

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

    let restante = duration;
    let inicio = Date.now();
    let timer = null;
    const programar = (ms) => {
      inicio = Date.now();
      restante = ms;
      timer = setTimeout(remove, ms);
    };
    const pausar = () => {
      clearTimeout(timer);
      restante -= Date.now() - inicio;
      bar.style.animationPlayState = 'paused';
    };
    const reanudar = () => {
      bar.style.animationPlayState = 'running';
      programar(restante);
    };

    bar.style.animation = `toast-countdown ${duration}ms linear forwards`;
    toast.addEventListener('mouseenter', pausar);
    toast.addEventListener('mouseleave', reanudar);
    toast.addEventListener('click', remove);
    programar(duration);
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
   * Tiempo relativo en español a partir de una fecha del servidor
   * ("hace 2h", "hace 3d", "recién"). Interpreta la fecha SQL como hora
   * local del servidor, que es como la genera MySQL con NOW().
   * @param {string} fechaSql  'YYYY-MM-DD HH:MM:SS'
   * @returns {string}
   */
  timeAgo(fechaSql) {
    if (!fechaSql) return '';
    const t = new Date(String(fechaSql).replace(' ', 'T'));
    if (isNaN(t)) return '';
    const seg = Math.max(0, Math.floor((Date.now() - t.getTime()) / 1000));
    if (seg < 60)     return 'recién';
    const min = Math.floor(seg / 60);
    if (min < 60)     return `hace ${min}m`;
    const hs = Math.floor(min / 60);
    if (hs < 24)      return `hace ${hs}h`;
    const dias = Math.floor(hs / 24);
    if (dias < 30)    return `hace ${dias}d`;
    const meses = Math.floor(dias / 30);
    if (meses < 12)   return `hace ${meses} mes${meses > 1 ? 'es' : ''}`;
    return `hace ${Math.floor(meses / 12)} año(s)`;
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

/* ─── Cliente del API JSON ───────────────────────────── */
/* Centraliza fetch + CSRF + manejo de errores para no repetirlo en cada
   pantalla. Todos los endpoints responden {success:true|false, ...}. */
const Api = {
  /**
   * Petición genérica al API.
   * @param {string} url
   * @param {RequestInit} options
   * @returns {Promise<Object>} Cuerpo JSON de una respuesta exitosa.
   * @throws {Error} Con .status y .data cuando el servidor rechaza la petición.
   */
  async request(url, options = {}) {
    let res;
    try {
      res = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: { 'X-Requested-With': 'XMLHttpRequest', ...(options.headers || {}) }
      });
    } catch {
      throw new Error('No se pudo conectar con el servidor. Revisá tu conexión.');
    }

    let data = null;
    try { data = await res.json(); } catch { /* respuesta vacía o no-JSON */ }

    if (!data) {
      throw new Error('El servidor respondió de forma inesperada.');
    }
    if (!res.ok || data.success === false) {
      /* Sesión vencida: avisamos y mandamos al login (el API no redirige
         para no romper el parseo JSON del cliente). */
      if (res.status === 401 || data.login) {
        Toast.error(data.error || 'Tu sesión expiró.');
        setTimeout(() => { window.location.href = '/login'; }, 1500);
      }
      const err = new Error(data.error || 'No se pudo completar la operación.');
      err.status = res.status;
      err.data = data;
      throw err;
    }
    return data;
  },

  /** GET de un endpoint JSON. */
  get(url) {
    return this.request(url);
  },

  /**
   * POST de un formulario (urlencoded) con token CSRF.
   * @param {string} url
   * @param {Object} fields Pares campo => valor.
   */
  post(url, fields = {}) {
    const body = new URLSearchParams();
    Object.entries(fields).forEach(([k, v]) => body.append(k, v ?? ''));
    return this.request(url, {
      method: 'POST',
      body,
      headers: {
        ...Utils.csrfHeaders(),
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      }
    });
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

/* ─── Nav según sesión ────────────────────────────────── */
/* En las páginas públicas, si hay una sesión activa se reemplazan los
   accesos "Entrar / Crear cuenta" por "Mi perfil (o panel) / Cerrar
   sesión", y en los navs que no tienen accesos de cuenta (p. ej.
   /torneos) se insertan. Así siempre se puede volver al perfil. */
async function initAuthNav() {
  // Las páginas privadas ya traen su propio nav con "Cerrar sesión".
  if (document.querySelector('.nav a[href="/logout"]')) return;

  let me;
  try {
    const res = await fetch('/api/me', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) return;                 // 401: no hay sesión, nav queda igual
    me = await res.json();
  } catch { return; }
  if (!me || !me.success) return;

  const panelUrl = {
    administrador: '/admin/dashboard',
    organizador:   '/organizador/dashboard',
  }[me.rol] || '/perfil';
  const panelTxt = me.rol === 'participante' ? 'Mi perfil' : 'Mi panel';

  /* Avatar del usuario: su foto de perfil o, si no subió ninguna, sus
     iniciales sobre el rojo de marca. Reemplaza al botón "Entrar". */
  const avatar = () => {
    const a = document.createElement('a');
    a.href = panelUrl;
    a.className = 'nav-avatar';
    a.title = panelTxt;
    a.setAttribute('aria-label', panelTxt);
    if (me.avatar_url) {
      a.style.backgroundImage = `url(${encodeURI(me.avatar_url)})`;
    } else {
      a.textContent = ((me.nombre || '')[0] || '') + ((me.apellido || '')[0] || '');
    }
    return a;
  };

  /* En desktop el acceso a la cuenta pasa a ser la foto; en el menú móvil
     conviene el texto, que es más claro con el menú desplegado. */
  document.querySelectorAll('.nav-right a[href="/login"]').forEach(a => a.replaceWith(avatar()));
  document.querySelectorAll('.mobile-nav a[href="/login"]').forEach(a => {
    a.href = panelUrl;
    a.textContent = panelTxt;
  });
  document.querySelectorAll('a[href="/registro"]').forEach(a => {
    a.href = '/logout';
    a.textContent = 'Cerrar sesión';
    a.classList.remove('btn-primary');
    if (a.classList.contains('btn')) a.classList.add('btn-ghost');
  });

  // Navs sin accesos de cuenta (p. ej. /torneos): insertarlos.
  document.querySelectorAll('.nav .nav-right').forEach(cont => {
    if (cont.querySelector('.nav-avatar, a[href="/logout"]')) return;
    const salir = document.createElement('a');
    salir.href = '/logout';
    salir.className = 'ghost-link';
    salir.textContent = 'Cerrar sesión';
    cont.insertBefore(salir, cont.querySelector('.theme-toggle'));
    cont.insertBefore(avatar(), cont.querySelector('.theme-toggle'));
  });
  document.querySelectorAll('.mobile-nav').forEach(cont => {
    if (cont.querySelector(`a[href="${panelUrl}"], a[href="/logout"]`)) return;
    const toggle = cont.querySelector('.theme-toggle');
    const mk = (href, text) => {
      const a = document.createElement('a');
      a.href = href; a.textContent = text;
      return a;
    };
    cont.insertBefore(mk(panelUrl, panelTxt), toggle);
    cont.insertBefore(mk('/logout', 'Cerrar sesión'), toggle);
  });
}

/* ─── Panel de accesibilidad ──────────────────────────── */
/* Botón flotante presente en todas las páginas que cargan main.js.
   Preferencias: tamaño de texto, alto contraste, reducir animaciones y
   subrayar enlaces. Persisten en localStorage y se aplican como clases
   sobre <html> (los estilos viven en main.css). */
const A11Y_KEY = 'tornalyx-a11y';

function initAccessibility() {
  const root = document.documentElement;
  let prefs = {};
  try { prefs = JSON.parse(localStorage.getItem(A11Y_KEY) || '{}') || {}; } catch { /* corrupto */ }

  /* Skip-link para navegación por teclado. */
  const main = document.querySelector('main');
  if (main) {
    if (!main.id) main.id = 'contenido';
    if (!main.hasAttribute('tabindex')) main.setAttribute('tabindex', '-1');
    const skip = document.createElement('a');
    skip.className = 'skip-link';
    skip.href = '#' + main.id;
    skip.textContent = 'Saltar al contenido principal';
    document.body.prepend(skip);
  }

  /* El disparador es un enlace más del menú ("Accesibilidad", junto a
     Documentación). El panel vive en <body> con posición fija para que no
     lo recorte el navbar y sea el mismo en escritorio y en móvil. */
  const navLinks = document.querySelector('.nav .nav-links');
  const widget   = document.createElement('div');
  widget.className = navLinks ? 'a11y-widget a11y-widget--nav' : 'a11y-widget';
  widget.innerHTML = `
    <button type="button" class="a11y-btn" id="a11yBtn" aria-expanded="false"
            aria-controls="a11yPanel" title="Opciones de accesibilidad">
      Accesibilidad
    </button>`;

  const panel = document.createElement('div');
  panel.className = 'a11y-panel hidden';
  panel.id = 'a11yPanel';
  panel.setAttribute('role', 'dialog');
  panel.setAttribute('aria-label', 'Opciones de accesibilidad');
  panel.innerHTML = `
      <h4>Accesibilidad</h4>
      <div class="a11y-row">
        <span id="a11yFontLabel">Tamaño del texto</span>
        <div class="a11y-fonts" role="group" aria-labelledby="a11yFontLabel">
          <button type="button" data-font=""   aria-label="Tamaño normal">A</button>
          <button type="button" data-font="lg" aria-label="Tamaño grande">A+</button>
          <button type="button" data-font="xl" aria-label="Tamaño muy grande">A++</button>
        </div>
      </div>
      <label class="a11y-row">
        <span>Alto contraste</span>
        <input type="checkbox" data-pref="contrast" />
      </label>
      <label class="a11y-row">
        <span>Reducir animaciones</span>
        <input type="checkbox" data-pref="motion" />
      </label>
      <label class="a11y-row">
        <span>Subrayar enlaces</span>
        <input type="checkbox" data-pref="links" />
      </label>`;

  document.body.appendChild(panel);
  if (navLinks) {
    navLinks.appendChild(widget);          // queda a continuación de Documentación
  } else {
    document.body.appendChild(widget);     // páginas sin menú: botón flotante
  }

  const btn = widget.querySelector('#a11yBtn');

  /* Mismo panel desde el menú móvil, donde .nav-links está oculto. */
  const abrirDesdeMobile = document.createElement('a');
  abrirDesdeMobile.href = '#';
  abrirDesdeMobile.textContent = 'Accesibilidad';
  document.querySelectorAll('.mobile-nav').forEach(nav => {
    const copia = abrirDesdeMobile.cloneNode(true);
    copia.addEventListener('click', e => {
      e.preventDefault();
      document.getElementById('mobileClose')?.click();
      togglePanel(true);
    });
    nav.insertBefore(copia, nav.querySelector('.theme-toggle'));
  });

  const aplicar = () => {
    root.classList.toggle('a11y-font-lg',  prefs.font === 'lg');
    root.classList.toggle('a11y-font-xl',  prefs.font === 'xl');
    root.classList.toggle('a11y-contrast', !!prefs.contrast);
    root.classList.toggle('a11y-motion',   !!prefs.motion);
    root.classList.toggle('a11y-links',    !!prefs.links);
    panel.querySelectorAll('[data-font]').forEach(b => {
      b.classList.toggle('active', (prefs.font || '') === b.dataset.font);
    });
    panel.querySelectorAll('[data-pref]').forEach(chk => {
      chk.checked = !!prefs[chk.dataset.pref];
    });
  };

  function togglePanel(abrir) {
    panel.classList.toggle('hidden', !abrir);
    btn.setAttribute('aria-expanded', String(abrir));
  }
  const guardar = () => {
    try { localStorage.setItem(A11Y_KEY, JSON.stringify(prefs)); } catch { /* modo privado */ }
  };

  btn.addEventListener('click', () => togglePanel(panel.classList.contains('hidden')));
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
      togglePanel(false);
      btn.focus();
    }
  });
  document.addEventListener('click', e => {
    const dentro = widget.contains(e.target) || panel.contains(e.target);
    if (!dentro && !panel.classList.contains('hidden')) {
      togglePanel(false);
    }
  });

  panel.querySelectorAll('[data-font]').forEach(b => {
    b.addEventListener('click', () => {
      prefs.font = b.dataset.font || undefined;
      guardar(); aplicar();
    });
  });
  panel.querySelectorAll('[data-pref]').forEach(chk => {
    chk.addEventListener('change', () => {
      prefs[chk.dataset.pref] = chk.checked || undefined;
      guardar(); aplicar();
    });
  });

  /* Todas las opciones arrancan desactivadas: el sitio se ve como está
     diseñado hasta que el usuario elija lo contrario, y su elección queda
     guardada para las próximas visitas. */
  aplicar();
}

/* ─── Toggle claro/oscuro ─────────────────────────────── */
/* El tema ya se aplica antes del primer paint (script inline en el
   <head> de cada página); acá solo sincronizamos los switches visibles
   y persistimos el cambio cuando el usuario lo toca. */
const THEME_KEY = 'tornalyx-theme';

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  document.querySelectorAll('.theme-toggle__input').forEach(input => {
    input.checked = theme === 'light';
  });
}

function initThemeToggle() {
  const inputs = document.querySelectorAll('.theme-toggle__input');
  if (!inputs.length) return;

  const current = document.documentElement.getAttribute('data-theme') || 'dark';
  applyTheme(current);

  inputs.forEach(input => {
    input.addEventListener('change', () => {
      const theme = input.checked ? 'light' : 'dark';
      try { localStorage.setItem(THEME_KEY, theme); } catch { /* privado / bloqueado */ }
      applyTheme(theme);
    });
  });
}

/* ─── Usuario actual en paneles privados ─────────────── */
/* Rellena los datos del usuario logueado en los elementos marcados con
   data-user-*. Solo hace la petición si la página tiene esos hooks (así las
   páginas públicas no llaman a /api/me). */
async function initCurrentUser() {
  const hooks = '[data-user-name],[data-user-role],[data-user-avatar],' +
                '[data-user-welcome],[data-user-email],[data-user-nombre],[data-user-apellido]';
  if (!document.querySelector(hooks)) return;

  let me;
  try {
    const res = await fetch('/api/me', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    me = await res.json();
  } catch {
    return;
  }
  if (!me || !me.success) return;

  const nombre   = me.nombre   || '';
  const apellido = me.apellido || '';
  const full     = (nombre + ' ' + apellido).trim();
  const initials = ((nombre[0] || '') + (apellido[0] || '')).toUpperCase();

  /* textContent / value según el tipo de elemento (evita XSS). */
  const fill = (sel, val) => document.querySelectorAll(sel).forEach(el => {
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.value = val;
    else el.textContent = val;
  });

  fill('[data-user-name]',     full || nombre);
  fill('[data-user-nombre]',   nombre);
  fill('[data-user-apellido]', apellido);
  fill('[data-user-role]',     me.rol || '');
  fill('[data-user-avatar]',   initials || (nombre[0] || '').toUpperCase());
  fill('[data-user-email]',    me.email || '');
  document.querySelectorAll('[data-user-welcome]').forEach(el => {
    el.textContent = 'Bienvenido/a, ' + (nombre || full);
  });
}

/* ─── Init global ────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initMobileNav();
  initScrollReveal();
  initTabs();
  initImageFallbacks();
  initTabShortcuts();
  initThemeToggle();
  initAccessibility();
  initAuthNav();
  Toast.init();
  Modal.init();
  initCurrentUser();
});

window.Tornalyx = { Toast, Utils, Modal, Api };
