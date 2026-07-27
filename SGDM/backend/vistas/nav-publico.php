<?php
/**
 * Parcial: navbar pública reutilizable (home, torneos, jugadores, docs).
 * Recibe $activo con la sección en la que está parado el usuario
 * ('inicio', 'torneos', 'jugadores' o 'documentacion') para resaltar
 * ese enlace; si no se pasa, ninguno queda marcado.
 */
$activo = $activo ?? '';

/** Resalta el enlace de la sección actual y lo anuncia a los lectores de pantalla. */
$marca = static fn(string $seccion): string =>
    $seccion === $activo ? ' style="color:var(--ink)" aria-current="page"' : '';
?>
<!-- Menú móvil -->
<div class="mobile-nav" id="mobileNav" role="dialog" aria-modal="true" aria-label="Menú">
  <button class="mobile-close" id="mobileClose" aria-label="Cerrar menú">✕</button>
  <a href="/"<?= $marca('inicio') ?>>Inicio</a>
  <a href="/torneos"<?= $marca('torneos') ?>>Torneos</a>
  <a href="/jugadores"<?= $marca('jugadores') ?>>Jugadores</a>
  <a href="/documentacion"<?= $marca('documentacion') ?>>Documentación</a>
  <label class="theme-toggle" aria-label="Cambiar tema claro/oscuro" style="margin-top:8px">
    <input type="checkbox" class="theme-toggle__input" />
    <span class="theme-toggle__track">
      <span class="theme-toggle__thumb">
        <span class="theme-toggle__icon theme-toggle__icon--sun">☀</span>
        <span class="theme-toggle__icon theme-toggle__icon--moon">☾</span>
      </span>
    </span>
  </label>
</div>

<header class="nav">
  <div class="wrap nav-in">
    <a class="brand" href="/">
      <span class="badge badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
      Tornalyx
    </a>
    <nav class="nav-links" aria-label="Navegación principal">
      <a href="/"<?= $marca('inicio') ?>>Inicio</a>
      <a href="/torneos"<?= $marca('torneos') ?>>Torneos</a>
      <a href="/jugadores"<?= $marca('jugadores') ?>>Jugadores</a>
      <a href="/documentacion"<?= $marca('documentacion') ?>>Documentación</a>
    </nav>
    <div class="nav-right">
      <label class="theme-toggle" aria-label="Cambiar tema claro/oscuro">
        <input type="checkbox" class="theme-toggle__input" />
        <span class="theme-toggle__track">
          <span class="theme-toggle__thumb">
            <span class="theme-toggle__icon theme-toggle__icon--sun">☀</span>
            <span class="theme-toggle__icon theme-toggle__icon--moon">☾</span>
          </span>
        </span>
      </label>
    </div>
    <button class="burger" id="burgerBtn" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobileNav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
