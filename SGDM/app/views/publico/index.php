<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Tornalyx · La plataforma que organiza todo. Torneos deportivos, mentales y esports." />
  <title><?= e($title ?? 'Tornalyx · La plataforma que organiza todo') ?></title>
  
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
  <a href="#audiencias">Soluciones</a>
  <a href="#empresa">Empresa</a>
  <a href="/login" class="ghost-link" style="font-size:1rem">Entrar</a>
  <a class="btn btn-primary mobile-cta" href="/torneos">Ver torneos</a>
</div>

<!-- ?? NAV ??????????????????????????????????????????????? -->
<header class="nav">
  <div class="wrap nav-in">
    <a class="brand" href="/">
      <span class="badge badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
      Tornalyx
    </a>
    <nav class="nav-links" aria-label="Navegación principal">
      <a href="#audiencias">Soluciones</a>
      <a href="#empresa">Empresa</a>
    </nav>
    <div class="nav-right">
      <a class="ghost-link" href="/login">Entrar</a>
      <a class="btn btn-primary btn-sm" href="/torneos">Ver torneos</a>
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
      <p class="eyebrow eyebrow-center">Torneos · deporte · mente · esports</p>
      <h1>Un sistema.<br><span class="accent">Cualquier competencia.</span></h1>
      <p class="lead">Liga, eliminación directa o sistema suizo. Del club de barrio a la liga online · participantes, calendarios, llaves y resultados en una sola plataforma modular.</p>
      <div class="btn-row">
        <a class="btn btn-primary" href="/torneos">Ver torneos</a>
        <a class="btn btn-ghost" href="/registro">Crear cuenta</a>
      </div>
      <div class="chips">
        <span class="chip chip-solid"><span class="dot"></span>Liga</span>
        <span class="chip">Eliminación directa</span>
        <span class="chip">Sistema suizo</span>
      </div>
      <p class="tagline">La plataforma que organiza todo</p>
    </div>
  </section>

  <!-- ?? AUDIENCIAS ??????????????????????????????????????? -->
  <section class="section section--alt" id="audiencias">
    <div class="wrap text-center">
      <p class="eyebrow eyebrow-center" data-reveal>Para cada organización</p>
      <h2 data-reveal>Pensado para todo tipo de competencia</h2>
      <p class="lead" data-reveal style="max-width:56ch;margin:12px auto 0">Clubes deportivos, instituciones educativas, federaciones, comunidades de esports y de juegos de mesa: todos gestionan sus torneos desde la misma plataforma.</p>
      <div class="chips" data-reveal style="justify-content:center;margin-top:28px">
        <span class="chip chip-solid"><span class="dot"></span>Clubes deportivos</span>
        <span class="chip">Instituciones educativas</span>
        <span class="chip">Federaciones y ligas</span>
        <span class="chip">Esports</span>
        <span class="chip">Juegos de mesa</span>
      </div>
    </div>
  </section>

  <!-- ?? CIERRE ??????????????????????????????????????????? -->
  <section class="section" id="empresa">
    <div class="wrap">
      <div class="cta-band" data-reveal style="text-align:center">
        <p class="eyebrow eyebrow-center">Hecho por Telyux Software Development · UTU Montevideo</p>
        <h2>Llevá tu torneo al siguiente nivel</h2>
        <p class="lead" style="max-width:50ch;margin:0 auto">Creá tu primera competencia o explorá los torneos ya publicados. Sin planillas, sin complicaciones.</p>
        <div class="btn-row btn-row-center" style="margin-top:28px">
          <a class="btn btn-primary" href="/registro">Crear cuenta gratis</a>
          <a class="btn btn-ghost" href="/torneos">Explorar torneos</a>
        </div>
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
        <p>La plataforma que organiza todo. Torneos de deporte, mente y esports.</p>
      </div>
      <div class="foot-col">
        <h5>Producto</h5>
        <a href="#audiencias">Soluciones</a>
      </div>
      <div class="foot-col">
        <h5>Formatos</h5>
        <a href="/torneos">Liga</a>
        <a href="/torneos">Eliminación directa</a>
        <a href="/torneos">Sistema suizo</a>
      </div>
      <div class="foot-col">
        <h5>Empresa</h5>
        <a href="#empresa">Sobre Telyux</a>
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
</body>
</html>


