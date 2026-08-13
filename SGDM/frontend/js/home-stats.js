/* =====================================================
   TORNALYX – home-stats.js
   Contadores animados del hero: torneos y disciplinas
   reales, derivados de /api/torneos (el mismo endpoint
   público que usa /torneos). Si el API falla, la sección
   queda oculta en vez de mostrar un número roto.
   ===================================================== */

'use strict';

(function () {

function prefiereMenosMovimiento() {
  return document.documentElement.classList.contains('a11y-motion')
    || (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
}

function animarContador(el, valor) {
  if (prefiereMenosMovimiento()) { el.textContent = valor; return; }
  const duracion = 900;
  const inicio = performance.now();
  function paso(ahora) {
    const p = Math.min(1, (ahora - inicio) / duracion);
    const suavizado = 1 - Math.pow(1 - p, 3); // ease-out cúbico
    el.textContent = Math.round(valor * suavizado);
    if (p < 1) requestAnimationFrame(paso);
    else el.textContent = valor;
  }
  requestAnimationFrame(paso);
}

async function initHeroStats() {
  const section = document.getElementById('heroStats');
  const { Api } = window.Tornalyx || {};
  if (!section || !Api) return;

  const valores = section.querySelectorAll('.hero-stat__value');
  if (!valores.length) return;

  let torneos = 0;
  let disciplinas = 0;
  try {
    const data = await Api.get('/api/torneos');
    const lista = data.torneos || [];
    torneos = lista.length;
    disciplinas = new Set(lista.map(t => t.disciplina).filter(Boolean)).size;
  } catch {
    return; // sin datos confiables, no se muestra la sección
  }
  if (!torneos) return; // "0 torneos" no aporta nada como dato destacado

  section.classList.remove('hidden');
  const objetivos = [torneos, disciplinas];
  let animado = false;

  function disparar() {
    if (animado) return;
    animado = true;
    valores.forEach((el, i) => animarContador(el, objetivos[i]));
  }

  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { disparar(); io.disconnect(); } });
    }, { threshold: 0.3 });
    io.observe(section);
  } else {
    disparar();
  }
}

document.addEventListener('DOMContentLoaded', initHeroStats);

})();
