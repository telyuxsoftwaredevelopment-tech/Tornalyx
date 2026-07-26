<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script>(function(){try{var t=localStorage.getItem('tornalyx-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <title><?= e($title ?? 'Tornalyx | Torneo') ?></title>

  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/main.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <style>
    /* Hero del torneo */
    .torneo-hero {
      position:relative; overflow:hidden;
      padding:clamp(44px,7vw,80px) 0;
      border-bottom:1px solid var(--line-soft);
    }
    .torneo-hero-in { position:relative; z-index:2; }
    .torneo-hero__grid { display:grid; grid-template-columns:1fr; gap:var(--space-6); }
    @media(min-width:768px) { .torneo-hero__grid { grid-template-columns:1fr auto; align-items:center; } }

    /* Breadcrumb */
    .breadcrumb {
      border-bottom:1px solid var(--line-soft);
      padding:var(--space-3) 0;
      font-size:var(--font-size-sm);
      color:var(--muted);
    }
    .breadcrumb a { color:var(--muted); transition:color .15s; }
    .breadcrumb a:hover { color:var(--red-bright); }
    .breadcrumb span { color:var(--muted-2); margin:0 6px; }

    /* Info boxes */
    .info-grid { display:grid; grid-template-columns:1fr; gap:var(--space-4); margin-bottom:var(--space-6); }
    @media(min-width:640px) { .info-grid { grid-template-columns:repeat(3,1fr); } }
    .info-box {
      border:1px solid var(--line);
      border-radius:16px;
      padding:var(--space-5);
      text-align:center;
      background:var(--bg-card);
      transition:border-color .2s;
    }
    .info-box:hover { border-color:var(--red-deep); }
    .info-box__value {
      font-family:var(--head); font-size:var(--font-size-2xl); font-weight:800;
      color:var(--red-bright);
    }
    .info-box__label { font-size:var(--font-size-sm); color:var(--muted); margin-top:6px; }

    /* Tabla de posiciones */
    .table-pos { width:100%; border-collapse:collapse; font-size:var(--font-size-sm); }
    .table-pos th {
      font-family:var(--mono); font-size:10px; letter-spacing:1px;
      text-transform:uppercase; color:var(--muted-2); text-align:left;
      padding:var(--space-2) var(--space-3); background:rgba(255,255,255,.02);
      border-bottom:1px solid var(--line); font-weight:500;
    }
    .table-pos td { padding:var(--space-3); border-bottom:1px solid var(--line-soft); color:var(--muted); }
    .table-pos tr:last-child td { border-bottom:none; }
    .table-pos tr:hover td { background:rgba(255,255,255,.025); }
    .pos-badge {
      width:28px; height:28px; border-radius:8px;
      display:inline-flex; align-items:center; justify-content:center;
      font-weight:700; font-size:12px; font-family:var(--head);
    }
    .pos-1 { background:rgba(251,191,36,.15); color:#fbbf24; border:1px solid rgba(251,191,36,.3); }
    .pos-2 { background:rgba(148,163,184,.1); color:#94a3b8; border:1px solid rgba(148,163,184,.2); }
    .pos-3 { background:rgba(217,119,6,.12); color:#fbbf24; border:1px solid rgba(217,119,6,.25); }
    .equipo-tag { display:inline-flex; align-items:center; gap:var(--space-2); color:var(--ink); font-weight:500; }

    /* Resultado de partido */
    .partido-row {
      display:flex; align-items:center; justify-content:space-between;
      padding:var(--space-3) var(--space-4);
      border-radius:12px; border:1px solid var(--line);
      margin-bottom:var(--space-2); background:var(--bg-card);
      transition:border-color .2s;
    }
    .partido-row:hover { border-color:var(--red-deep); }
    .partido-score {
      font-family:var(--head); font-size:var(--font-size-xl); font-weight:800;
      text-align:center; min-width:64px; color:var(--ink);
    }
    .doc-empty { color:var(--muted); padding:var(--space-8) 0; text-align:center; }

    /* Fila del fixture: rival, marcador, y debajo horario/lugar/estado */
    .partido-row { flex-wrap:wrap; }
    .partido-row__side { flex:1; min-width:110px; font-weight:600; color:var(--ink); }
    .partido-row__meta {
      flex-basis:100%; display:flex; flex-wrap:wrap; gap:var(--space-3);
      margin-top:var(--space-2); padding-top:var(--space-2);
      border-top:1px solid var(--line-soft);
      font-size:12px; color:var(--muted-2);
    }
    .partido-row__meta .btn { margin-left:auto; }

    /* Bloque de inscripción del hero */
    .inscribir-box {
      border:1px solid var(--line); border-radius:16px;
      background:var(--bg-card); padding:var(--space-5);
      min-width:260px;
    }
    .inscribir-box h4 { color:var(--ink); font-family:var(--head); margin-bottom:var(--space-3); }

    /* Reglamento y premios */
    .prosa { color:var(--muted); line-height:1.75; white-space:pre-line; }
  </style>
</head>
<body>

  <?= $partial('partials/nav-publico', ['activo' => 'torneos']) ?>

  <!-- Todo el contenido se rellena desde /api/torneo/{id} con torneo-detalle.js -->
  <div id="detalleRoot">

    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Ubicación">
      <div class="wrap">
        <a href="/">Inicio</a><span>›</span>
        <a href="/torneos">Torneos</a><span>›</span>
        <span id="bcNombre" style="color:var(--muted)">Torneo</span>
      </div>
    </nav>

    <!-- Hero del torneo -->
    <div class="torneo-hero">
      <div class="wrap torneo-hero-in">
        <div class="torneo-hero__grid">
          <div>
            <div id="heroBadges" style="display:flex;flex-wrap:wrap;gap:var(--space-2);margin-bottom:var(--space-3)"></div>
            <h1 id="heroNombre" style="margin-bottom:var(--space-3)">Cargando…</h1>
            <p class="lead" id="heroDesc" style="max-width:580px;margin-bottom:var(--space-5);display:none"></p>
            <div id="heroMeta" style="display:flex;flex-wrap:wrap;gap:var(--space-4);font-size:var(--font-size-sm);color:var(--muted)"></div>
          </div>
          <div id="heroAction"></div>
        </div>
      </div>
    </div>

    <main class="section" style="padding-top:clamp(28px,4vw,48px)">
      <div class="wrap">

        <!-- Info boxes (las llena el JS) -->
        <div class="info-grid" id="infoGrid"></div>

        <!-- Tabs -->
        <div class="tab-list" role="tablist">
          <button class="tab active" role="tab" data-tab="fixture">Fixture</button>
          <button class="tab" role="tab" data-tab="posiciones">Posiciones</button>
          <button class="tab" role="tab" data-tab="equipos">Participantes</button>
          <button class="tab" role="tab" data-tab="avisos">Novedades</button>
          <button class="tab" role="tab" data-tab="info">Información</button>
        </div>

        <div class="tab-content active" id="tab-fixture" role="tabpanel"></div>
        <div class="tab-content" id="tab-posiciones" role="tabpanel"></div>
        <div class="tab-content" id="tab-equipos" role="tabpanel"></div>
        <div class="tab-content" id="tab-avisos" role="tabpanel"></div>
        <div class="tab-content" id="tab-info" role="tabpanel"></div>

      </div>
    </main>

  </div>

  <?= $partial('partials/footer') ?>

  <script src="../js/main.js"></script>
  <script src="../js/torneo-ui.js"></script>
  <script src="../js/torneo-detalle.js"></script>
</body>
</html>
