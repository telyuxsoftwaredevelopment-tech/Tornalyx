/* =====================================================
   TORNALYX – dashboard.js
   Navegación SPA-lite para paneles privados
   (Admin y Organizador)
   ===================================================== */

'use strict';

/**
 * Lee el título de una sección desde el atributo data-title
 * del ítem del sidebar, o hace fallback al texto del ítem.
 * @param {Element} item - Elemento .sidebar__item
 * @returns {string}
 */
function getSectionTitle(item) {
  if (!item) return '';
  return item.dataset.title || item.textContent.trim();
}

/**
 * Activa una sección del dashboard y actualiza el sidebar.
 * @param {string} key - Valor de data-section
 */
function setSection(key) {
  document.querySelectorAll('.sidebar__item').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.section-panel').forEach(el => el.classList.remove('active'));

  const item  = document.querySelector(`.sidebar__item[data-section="${key}"]`);
  const panel = document.getElementById('panel-' + key);

  if (item)  item.classList.add('active');
  if (panel) panel.classList.add('active');

  const title = getSectionTitle(item);
  const titleEl = document.getElementById('topbarTitle');
  if (titleEl) titleEl.textContent = title;

  /* Actualizar URL sin recargar (SPA-lite) */
  if (history.pushState) {
    history.pushState({ section: key }, '', '#' + key);
  }
}

/**
 * Inicializa la navegación sidebar y el overlay mobile.
 */
function initDashboard() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggle  = document.getElementById('menuToggle');
  if (!sidebar) return;

  /* Navegación por secciones */
  document.querySelectorAll('.sidebar__item[data-section]').forEach(item => {
    item.addEventListener('click', function () {
      setSection(this.dataset.section);
      closeSidebar();
    });
  });

  /* Botones con data-goto activan una sección directamente */
  document.querySelectorAll('[data-goto]').forEach(btn => {
    btn.addEventListener('click', function () {
      setSection(this.dataset.goto);
    });
  });

  /* Overlay mobile */
  function openSidebar() {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
  }

  if (toggle)  toggle.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });

  /* Restaurar sección desde hash de URL */
  const hash = location.hash.replace('#', '');
  if (hash && document.getElementById('panel-' + hash)) {
    setSection(hash);
  }

  /* Scroll reveal dentro del dashboard */
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

document.addEventListener('DOMContentLoaded', initDashboard);

/* Exponer setSection globalmente por si se necesita desde HTML */
window.setSection = setSection;
