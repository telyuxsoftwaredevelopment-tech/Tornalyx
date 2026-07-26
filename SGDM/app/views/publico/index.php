<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Tornalyx · Organizá torneos de deporte, mente y esports: inscripciones, fixture, resultados y posiciones en un solo lugar." />
  <script>(function(){try{var t=localStorage.getItem('tornalyx-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <title><?= e($title ?? 'Tornalyx · Torneos de deporte, mente y esports') ?></title>
  
  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/main.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <link rel="stylesheet" href="../css/home.css" />
</head>
<body>

<!-- ?? MOBILE NAV ???????????????????????????????????????? -->
<div class="mobile-nav" id="mobileNav" role="dialog" aria-modal="true" aria-label="Menú de navegación">
  <button class="mobile-close" id="mobileClose" aria-label="Cerrar menú">✕</button>
  <a href="/torneos">Torneos</a>
  <a href="/documentacion">Documentación</a>
  <a href="/login" class="ghost-link" style="font-size:1rem">Entrar</a>
  <a class="btn btn-primary mobile-cta" href="/torneos">Ver torneos</a>
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

<!-- ?? NAV ??????????????????????????????????????????????? -->
<header class="nav">
  <div class="wrap nav-in">
    <a class="brand" href="/">
      <span class="badge badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
      Tornalyx
    </a>
    <nav class="nav-links" aria-label="Navegación principal">
      <a href="/torneos">Torneos</a>
      <a href="/documentacion">Documentación</a>
    </nav>
    <div class="nav-right">
      <a class="ghost-link" href="/login">Entrar</a>
      <a class="btn btn-primary btn-sm" href="/torneos">Ver torneos</a>
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

<main id="top">

  <!-- ?? HERO ??????????????????????????????????????????? -->
  <section class="hero">
    <div class="grid-lines"></div>
    <div class="wrap hero-in">
      <div class="hero-top">
        <div class="hero-text">
          <h1>Un sistema.<br><span class="accent">Cualquier competencia.</span></h1>
          <p class="lead">Liga, eliminación directa o sistema suizo. Del club de barrio a la liga online · participantes, calendarios, llaves y resultados en una sola plataforma modular.</p>
        </div>
        <div class="hero-cta">
          <a class="btn btn-primary btn-lg" href="/registro">Crear cuenta</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ?? AUDIENCIAS ??????????????????????????????????????? -->
  <section class="section section--alt" id="audiencias">
    <div class="wrap audiencias-grid">
      <!-- Galería: las fotos rotan solas (js/galeria.js) y muestran los
           distintos tipos de competencia que cubre la plataforma. -->
      <div class="galeria" data-reveal role="group" aria-roledescription="carrusel" aria-label="Tipos de competencia">
        <div class="galeria__slides">
          <figure class="galeria__slide is-active">
            <!-- La primera se carga de inmediato para que no quede un hueco
                 al llegar a la sección; el resto van en diferido. -->
            <img src="../assets/galeria/futbol.jpg" alt="Jugador de fútbol rematando al arco" />
            <figcaption>Deportes de equipo</figcaption>
          </figure>
          <figure class="galeria__slide">
            <img src="../assets/galeria/futbol-copa.jpg" alt="Futbolista celebrando con el trofeo del campeonato" loading="lazy" />
            <figcaption>Campeonatos y copas</figcaption>
          </figure>
          <figure class="galeria__slide">
            <img src="../assets/galeria/ajedrez.jpg" alt="Dos ajedrecistas saludándose antes de una partida" loading="lazy" />
            <figcaption>Deportes de la mente</figcaption>
          </figure>
          <figure class="galeria__slide">
            <img src="../assets/galeria/juegos-de-mesa.jpg" alt="Mesa de juego de cartas vista desde arriba" loading="lazy" />
            <figcaption>Juegos de mesa y cartas</figcaption>
          </figure>
          <figure class="galeria__slide">
            <img src="../assets/galeria/esports.jpg" alt="Equipo de esports entrando al escenario" loading="lazy" />
            <figcaption>Equipos de esports</figcaption>
          </figure>
          <figure class="galeria__slide">
            <img src="../assets/galeria/esports-arena.jpg" alt="Estadio lleno durante una final de esports" loading="lazy" />
            <figcaption>Grandes finales</figcaption>
          </figure>
        </div>
        <div class="galeria__puntos" role="tablist" aria-label="Elegir foto"></div>
      </div>
      <div class="audiencias-text">
        <p class="eyebrow" data-reveal>Para cada organización</p>
        <h2 data-reveal>Pensado para todo tipo de competencia</h2>
        <p class="lead" data-reveal style="max-width:56ch;margin-top:12px">Clubes deportivos, instituciones educativas, federaciones, comunidades de esports y de juegos de mesa: todos gestionan sus torneos desde la misma plataforma.</p>
      </div>
    </div>
  </section>

</main>

<!-- ?? FOOTER ???????????????????????????????????????????? -->
<footer class="footer-wrap">
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <a class="brand" href="/">
          <span class="badge badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
          Tornalyx
        </a>
        <p>Torneos de deporte, mente y esports en una sola plataforma.</p>
      </div>
      <div class="foot-col">
        <h5>Producto</h5>
        <a href="/torneos">Torneos</a>
        <a href="/jugadores">Jugadores</a>
        <a href="/documentacion">Documentación</a>
      </div>
      <div class="foot-col">
        <h5>Formatos</h5>
        <a href="/torneos">Liga</a>
        <a href="/torneos">Eliminación directa</a>
        <a href="/torneos">Sistema suizo</a>
      </div>
      <div class="foot-col">
        <h5>Cuenta</h5>
        <a href="/login">Entrar</a>
        <a href="/registro">Registrarse</a>
        <a href="/terminos">Términos y condiciones</a>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© 2026 Tornalyx · por Telyux Software Development</span>
      <span>Bachillerato Tecnológico en TI · UTU Montevideo</span>
    </div>
  </div>
</footer>

<script src="../js/main.js"></script>
<script src="../js/galeria.js"></script>
</body>
</html>


