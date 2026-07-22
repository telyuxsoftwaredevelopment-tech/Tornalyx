<?php
/**
 * Parcial: navbar pública reutilizable (home de torneos, detalle de torneo).
 * El enlace "Torneos" queda marcado como activo porque las páginas que la usan
 * pertenecen a esa sección.
 */
?>
<!-- Menú móvil -->
<div class="mobile-nav" id="mobileNav" role="dialog" aria-modal="true" aria-label="Menú">
  <button class="mobile-close" id="mobileClose" aria-label="Cerrar menú">✕</button>
  <a href="/">Inicio</a>
  <a href="/torneos">Torneos</a>
  <a href="/login">Iniciar sesión</a>
  <a class="btn btn-primary mobile-cta" href="/registro">Crear cuenta</a>
</div>

<header class="nav">
  <div class="wrap nav-in">
    <a class="brand" href="/">
      <span class="badge badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
      Tornalyx
    </a>
    <nav class="nav-links" aria-label="Navegación principal">
      <a href="/">Inicio</a>
      <a href="/torneos" style="color:var(--ink)">Torneos</a>
    </nav>
    <div class="nav-right">
      <a class="ghost-link" href="/login">Entrar</a>
      <a class="btn btn-primary btn-sm" href="/registro">Crear cuenta</a>
    </div>
    <button class="burger" id="burgerBtn" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobileNav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
