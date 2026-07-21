<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($title ?? 'Tornalyx | Buscar Torneos') ?></title>
  
  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/main.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <link rel="stylesheet" href="../css/torneos.css" />
  <style>
    /* Page header */
    .page-hero {
      position:relative; overflow:hidden;
      padding:clamp(44px,7vw,80px) 0;
      border-bottom:1px solid var(--line-soft);
    }
    .page-hero .glow-sm {
      position:absolute; inset:0; pointer-events:none;
      background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(236,28,36,.18),transparent 65%);
    }
    .page-hero .grid-lines-sm {
      position:absolute; inset:0; z-index:0; opacity:.35;
      background-image:linear-gradient(var(--line-soft) 1px,transparent 1px),
                       linear-gradient(90deg,var(--line-soft) 1px,transparent 1px);
      background-size:64px 64px;
      mask-image:radial-gradient(ellipse 80% 100% at 50% 0%,#000,transparent 80%);
      -webkit-mask-image:radial-gradient(ellipse 80% 100% at 50% 0%,#000,transparent 80%);
    }
    .page-hero-in { position:relative; z-index:2; }
    /* Filters */
    .filters {
      background:var(--bg-card);
      border:1px solid var(--line);
      border-radius:16px;
      padding:var(--space-5);
      margin-bottom:var(--space-6);
    }
    .filters__grid { display:grid; grid-template-columns:1fr; gap:var(--space-3); }
    @media(min-width:640px)  { .filters__grid { grid-template-columns:1fr 1fr; } }
    @media(min-width:1024px) { .filters__grid { grid-template-columns:2fr 1fr 1fr 1fr auto; align-items:end; } }
    /* Torneo card */
    .torneo-card {
      background:var(--bg-card);
      border:1px solid var(--line);
      border-radius:16px;
      overflow:hidden;
      transition:border-color .2s,transform .2s,box-shadow .2s;
      display:flex; flex-direction:column;
    }
    .torneo-card:hover { border-color:var(--red-deep); transform:translateY(-4px); box-shadow:0 24px 50px -30px rgba(0,0,0,.9); }
    .torneo-card__sport {
      height:80px;
      display:flex; align-items:center; justify-content:center;
      font-size:2.2rem;
      position:relative; overflow:hidden;
    }
    .torneo-card__sport::after {
      content:"";
      position:absolute; inset:0;
      background:rgba(0,0,0,.3);
    }
    .torneo-card__body { padding:var(--space-4); flex:1; }
    .torneo-card__meta { display:flex; gap:var(--space-2); flex-wrap:wrap; margin-bottom:var(--space-3); }
    .torneo-card__title { font-family:var(--head); font-size:var(--font-size-lg); font-weight:700; margin-bottom:var(--space-2); color:var(--ink); }
    .torneo-card__info {
      font-size:var(--font-size-sm); color:var(--muted);
      display:flex; flex-direction:column; gap:4px;
      margin-bottom:var(--space-4);
    }
    .torneo-card__footer {
      padding:var(--space-3) var(--space-4);
      border-top:1px solid var(--line-soft);
      display:flex; justify-content:space-between; align-items:center;
      background:rgba(0,0,0,.2);
    }
    .status-live { font-size:12px; color:var(--red-bright); font-family:var(--mono); font-weight:600; }
    .status-next { font-size:12px; color:var(--color-warning); font-family:var(--mono); font-weight:600; }
    .status-done { font-size:12px; color:var(--muted-2); font-family:var(--mono); font-weight:600; }
    /* Empty state */
    .empty-state { text-align:center; padding:var(--space-16); color:var(--muted); }
    .empty-state .icon { font-size:3.5rem; margin-bottom:var(--space-4); }
    /* Results bar */
    .results-bar {
      display:flex; justify-content:space-between; align-items:center;
      margin-bottom:var(--space-4);
      font-size:var(--font-size-sm); color:var(--muted);
    }
    select.form-control { cursor:pointer; }
  </style>
</head>
<body>

  <?= $partial('partials/nav-publico') ?>

  <!-- Page hero -->
  <div class="page-hero">
    <div class="glow-sm"></div>
    <div class="grid-lines-sm"></div>
    <div class="wrap page-hero-in">
      <p class="eyebrow">Torneos · deporte · mente · esports</p>
      <h1 style="font-size:clamp(28px,4vw,48px);margin-bottom:10px">Buscar torneos</h1>
      <p class="lead" style="max-width:52ch">Encontrá el torneo ideal entre todas las disciplinas disponibles.</p>
    </div>
  </div>

  <main class="section" style="padding-top:clamp(32px,5vw,56px)">
    <div class="wrap">

      <!-- Filtros -->
      <div class="filters" aria-label="Filtros de búsqueda">
        <div class="filters__grid">
          <div class="form-group" style="margin:0">
            <label class="form-label" for="searchQ">Buscar por nombre</label>
            <input class="form-control" type="search" id="searchQ" placeholder="ej: Copa Fútbol..." />
          </div>
          <div class="form-group" style="margin:0">
            <!-- Las opciones las carga torneos.js con las disciplinas que existen -->
            <label class="form-label" for="filterDeporte">Disciplina</label>
            <select class="form-control" id="filterDeporte">
              <option value="">Todas</option>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label" for="filterEstado">Estado</label>
            <select class="form-control" id="filterEstado">
              <option value="">Todos</option>
              <option value="inscripcion">Inscripciones abiertas</option>
              <option value="en_curso">En curso</option>
              <option value="finalizado">Finalizado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label" for="filterSistema">Sistema</label>
            <select class="form-control" id="filterSistema">
              <option value="">Todos</option>
              <option value="liga">Liga</option>
              <option value="eliminacion_directa">Eliminación directa</option>
              <option value="suizo">Sistema suizo</option>
              <option value="grupos_playoff">Grupos + playoffs</option>
            </select>
          </div>
          <button class="btn btn-primary" id="btnFiltrar" style="align-self:flex-end;white-space:nowrap">Filtrar</button>
        </div>
      </div>

      <!-- Resultados -->
      <div class="results-bar">
        <span id="resultsCount">Mostrando <strong style="color:var(--ink)">0</strong> torneos</span>
        <select class="form-control" style="width:auto;font-size:var(--font-size-sm)" id="sortBy" aria-label="Ordenar por">
          <option value="recientes">Más recientes</option>
          <option value="nombre">Nombre A-Z</option>
          <option value="fecha">Fecha de inicio</option>
        </select>
      </div>

      <!-- Grid de torneos -->
      <div class="cards-grid" id="torneosGrid">

        <!-- Las cards las inserta torneos.js con GET /api/torneos -->

      </div><!-- /torneosGrid -->

      <div class="empty-state hidden" id="emptyState">
        <div class="icon">??</div>
        <h3 style="color:var(--ink);margin-bottom:8px">No se encontraron torneos</h3>
        <p>Probá con otros filtros o buscá otro nombre.</p>
      </div>

    </div>
  </main>

  <?= $partial('partials/footer') ?>

  <script src="../js/main.js"></script>
  <script src="../js/torneo-ui.js"></script>
  <script src="../js/torneos.js"></script>
</body>
</html>


