/* =====================================================
   TORNALYX – torneos.js
   Lógica de filtrado y búsqueda en /torneos
   ===================================================== */

'use strict';

/**
 * Inicializa los filtros de la página de torneos.
 * Lee los campos de búsqueda, estado y sistema de juego
 * y muestra/oculta las cards según los criterios.
 */
function initFiltros() {
  const btnFiltrar = document.getElementById('btnFiltrar');
  const btnLimpiar = document.getElementById('btnLimpiar');
  if (!btnFiltrar) return;

  function aplicarFiltros() {
    const q       = (document.getElementById('searchQ')?.value || '').toLowerCase().trim();
    const estado  = document.getElementById('filterEstado')?.value  || '';
    const sistema = document.getElementById('filterSistema')?.value || '';
    const disciplina = document.getElementById('filterDisciplina')?.value || '';
    const cards   = document.querySelectorAll('#torneosGrid .torneo-card');
    let visible   = 0;

    cards.forEach(card => {
      const title     = card.querySelector('.torneo-card__title')?.textContent.toLowerCase() || '';
      const matchQ    = !q          || title.includes(q);
      const matchE    = !estado     || card.dataset.estado     === estado;
      const matchS    = !sistema    || card.dataset.sistema    === sistema;
      const matchD    = !disciplina || card.dataset.disciplina === disciplina;
      const show      = matchQ && matchE && matchS && matchD;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    const countEl = document.querySelector('#resultsCount strong');
    if (countEl) countEl.textContent = visible;

    const emptyEl = document.getElementById('emptyState');
    if (emptyEl) emptyEl.classList.toggle('hidden', visible > 0);
  }

  btnFiltrar.addEventListener('click', aplicarFiltros);

  /* Búsqueda en tiempo real al escribir */
  const searchInput = document.getElementById('searchQ');
  if (searchInput) {
    const debouncedFilter = window.Tornalyx?.Utils?.debounce(aplicarFiltros, 300) || aplicarFiltros;
    searchInput.addEventListener('input', debouncedFilter);
    searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') aplicarFiltros(); });
  }

  /* Limpiar filtros */
  if (btnLimpiar) {
    btnLimpiar.addEventListener('click', () => {
      ['searchQ', 'filterEstado', 'filterSistema', 'filterDisciplina'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
      aplicarFiltros();
    });
  }

  /* Ordenar resultados */
  const sortSelect = document.getElementById('sortOrder');
  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      const grid  = document.getElementById('torneosGrid');
      const cards = [...grid.querySelectorAll('.torneo-card')];
      const val   = sortSelect.value;
      cards.sort((a, b) => {
        const ta = a.querySelector('.torneo-card__title')?.textContent || '';
        const tb = b.querySelector('.torneo-card__title')?.textContent || '';
        if (val === 'az')  return ta.localeCompare(tb);
        if (val === 'za')  return tb.localeCompare(ta);
        return 0;
      });
      cards.forEach(c => grid.appendChild(c));
    });
  }
}

document.addEventListener('DOMContentLoaded', initFiltros);
