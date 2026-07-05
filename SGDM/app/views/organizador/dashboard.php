<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($title ?? 'Tornalyx | Panel Organizador') ?></title>
  
  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../../css/main.css" />
  <link rel="stylesheet" href="../../css/components.css" />
  <link rel="stylesheet" href="../../css/dashboard.css" />
  <style>
    body { display:flex; min-height:100vh; overflow-x:hidden; }
    .sidebar {
      position:fixed; top:0; left:0; bottom:0; width:var(--sidebar-width);
      background:var(--bg-2); border-right:1px solid var(--line);
      display:flex; flex-direction:column; z-index:200;
      transition:transform .25s cubic-bezier(.4,0,.2,1);
    }
    .sidebar-brand {
      display:flex; align-items:center; gap:var(--space-3);
      padding:var(--space-5) var(--space-4); border-bottom:1px solid var(--line);
      font-family:var(--head); font-weight:800; font-size:var(--font-size-lg);
      color:var(--ink); text-decoration:none;
    }
    .sidebar-brand .mark {
      width:36px; height:36px; border-radius:8px;
      object-fit:contain; flex:none; display:block;
    }
    .sidebar__nav { flex:1; padding:var(--space-4) 0; overflow-y:auto; }
    .sidebar__section {
      padding:0 var(--space-3) var(--space-2);
      font-family:var(--mono); font-size:10px; letter-spacing:1.5px; text-transform:uppercase;
      color:var(--muted-2); margin-top:var(--space-4);
    }
    .sidebar__item {
      display:flex; align-items:center; gap:var(--space-3);
      padding:10px var(--space-4); margin:2px var(--space-2); border-radius:10px;
      font-size:var(--font-size-sm); color:var(--muted);
      cursor:pointer; transition:all var(--transition-fast);
      border:1px solid transparent; font-weight:500; text-decoration:none;
    }
    .sidebar__item:hover { background:rgba(255,255,255,.04); color:var(--ink); }
    .sidebar__item.active {
      background:rgba(236,28,36,.08); color:var(--red-bright);
      border-color:rgba(236,28,36,.18); box-shadow:inset 3px 0 0 var(--red);
    }
    .sidebar__item .icon { font-size:1.1rem; width:22px; text-align:center; flex:none; }
    .sidebar__footer { padding:var(--space-4); border-top:1px solid var(--line); }
    .user-row {
      display:flex; align-items:center; gap:var(--space-3);
      padding:var(--space-2) var(--space-3); border-radius:10px;
      background:rgba(255,255,255,.03); border:1px solid var(--line);
    }
    .user-avatar {
      width:34px; height:34px; border-radius:50%;
      background:linear-gradient(135deg,var(--red),var(--red-deep));
      display:flex; align-items:center; justify-content:center;
      font-weight:700; font-size:12px; color:#fff; flex:none; font-family:var(--head);
    }
    .user-name { font-size:var(--font-size-sm); color:var(--ink); font-weight:600; }
    .user-role { font-size:11px; color:var(--muted-2); font-family:var(--mono); }

    .sidebar-overlay {
      position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:150;
      opacity:0; pointer-events:none; transition:opacity .25s;
    }
    .sidebar-overlay.show { opacity:1; pointer-events:all; }

    .app-main { margin-left:var(--sidebar-width); flex:1; display:flex; flex-direction:column; min-width:0; }

    .topbar {
      position:sticky; top:0; z-index:100;
      background:rgba(11,7,8,.9); backdrop-filter:blur(12px);
      border-bottom:1px solid var(--line-soft); padding:0 var(--space-6); height:64px;
      display:flex; align-items:center; justify-content:space-between; gap:var(--space-4);
    }
    .topbar-left { display:flex; align-items:center; gap:var(--space-3); }
    .menu-toggle {
      display:none; background:none; border:none; cursor:pointer;
      color:var(--muted); font-size:1.2rem; padding:6px; border-radius:8px;
    }
    @media(max-width:768px) {
      .sidebar { transform:translateX(-100%); }
      .sidebar.open { transform:translateX(0); }
      .app-main { margin-left:0; }
      .menu-toggle { display:flex; }
    }

    .page-content { padding:var(--space-6); flex:1; }
    .section-panel { display:none; }
    .section-panel.active { display:block; }

    .kpi-grid {
      display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
      gap:var(--space-4); margin-bottom:var(--space-6);
    }
    .kpi-card {
      background:var(--bg-card); border:1px solid var(--line);
      border-radius:16px; padding:var(--space-5); transition:border-color .2s;
    }
    .kpi-card:hover { border-color:var(--red-deep); }
    .kpi-card__label { font-size:var(--font-size-sm); color:var(--muted); margin-bottom:var(--space-2); }
    .kpi-card__value {
      font-family:var(--head); font-size:2.25rem; font-weight:800; line-height:1;
      color:var(--red-bright); margin-bottom:6px;
    }
    .kpi-card__delta { font-size:12px; color:var(--muted-2); font-family:var(--mono); }

    /* Progress bars */
    .progress-bar { height:6px; background:var(--line); border-radius:3px; overflow:hidden; margin-top:6px; }
    .progress-fill { height:100%; border-radius:3px; background:var(--red); transition:width .5s ease; }

    .data-table { width:100%; border-collapse:collapse; }
    .data-table th {
      font-family:var(--mono); font-size:10px; letter-spacing:1px; text-transform:uppercase;
      color:var(--muted-2); text-align:left; padding:var(--space-2) var(--space-4);
      background:rgba(255,255,255,.02); border-bottom:1px solid var(--line);
    }
    .data-table td {
      padding:var(--space-3) var(--space-4); border-bottom:1px solid var(--line-soft);
      font-size:var(--font-size-sm); color:var(--muted);
    }
    .data-table tr:last-child td { border-bottom:none; }
    .data-table tr:hover td { background:rgba(255,255,255,.02); }
    .data-table td:first-child { color:var(--ink); font-weight:500; }

    .page-header {
      margin-bottom:var(--space-6);
      display:flex; align-items:flex-start; justify-content:space-between;
      gap:var(--space-4); flex-wrap:wrap;
    }
    .page-header h1 { font-size:var(--font-size-2xl); color:var(--ink); }
    .page-header p  { color:var(--muted); font-size:var(--font-size-sm); margin-top:4px; }

    /* Torneo card compact */
    .torneo-item {
      padding:var(--space-4);
      border:1px solid var(--line); border-radius:14px;
      background:var(--bg-card); margin-bottom:var(--space-3);
      transition:border-color .2s;
    }
    .torneo-item:hover { border-color:var(--red-deep); }
    .torneo-item__head { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:var(--space-3); }
    .torneo-item__title { font-family:var(--head); font-weight:700; color:var(--ink); font-size:var(--font-size-base); }
    .torneo-item__meta  { font-size:12px; color:var(--muted-2); margin-top:2px; }

    /* Create form */
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4); }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:var(--space-4); }
    @media(max-width:640px) {
      .form-grid-2 { grid-template-columns:1fr; }
      .form-grid-3 { grid-template-columns:1fr; }
    }

    /* Standings */
    .pos-num {
      width:28px; height:28px; border-radius:8px;
      display:inline-flex; align-items:center; justify-content:center;
      font-weight:700; font-size:12px; font-family:var(--head);
      background:rgba(255,255,255,.06); color:var(--muted);
    }
    .pos-num.gold   { background:rgba(251,191,36,.15); color:#fbbf24; }
    .pos-num.silver { background:rgba(148,163,184,.1);  color:#94a3b8; }
    .pos-num.bronze { background:rgba(217,119,6,.12);   color:#fbbf24; }
  </style>
</head>
<body>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="/">
      <img class="mark" src="../../assets/ICONO.png" alt="Tornalyx" data-fallback="mark">
      Tornalyx
    </a>
    <nav class="sidebar__nav">
      <div class="sidebar__section">Panel</div>
      <a class="sidebar__item active" data-section="inicio" data-title="Mis torneos">
        <span class="icon"><img src="../../assets/icon-inicio.svg" width="22" height="22" alt=""></span> Inicio
      </a>
      <a class="sidebar__item" data-section="crear" data-title="Crear torneo">
        <span class="icon"><img src="../../assets/icon-crear-torneo.svg" width="22" height="22" alt=""></span> Crear torneo
      </a>
      <div class="sidebar__section">Gestión</div>
      <a class="sidebar__item" data-section="participantes" data-title="Participantes">
        <span class="icon"><img src="../../assets/icon-participantes.svg" width="22" height="22" alt=""></span> Participantes
      </a>
      <a class="sidebar__item" data-section="resultados" data-title="Resultados">
        <span class="icon"><img src="../../assets/icon-resultados.svg" width="22" height="22" alt=""></span> Resultados
      </a>
      <a class="sidebar__item" data-section="posiciones" data-title="Posiciones">
        <span class="icon"><img src="../../assets/icon-posiciones.svg" width="22" height="22" alt=""></span> Posiciones
      </a>
    </nav>
    <div class="sidebar__footer">
      <div class="user-row">
        <div class="user-avatar" data-user-avatar>ML</div>
        <div>
          <div class="user-name" data-user-name>María López</div>
          <div class="user-role" data-user-role>organizador</div>
        </div>
        <a href="/logout" style="margin-left:auto;color:var(--muted-2)" title="Cerrar sesión">?</a>
      </div>
    </div>
  </aside>

  <div class="app-main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle" aria-label="Menú">?</button>
        <span id="topbarTitle" style="font-family:var(--head);font-weight:700;color:var(--ink)">Mis torneos</span>
      </div>
      <div style="display:flex;align-items:center;gap:var(--space-3)">
        <a href="/torneos" class="btn btn-ghost btn-sm">Ver sitio</a>
        <a class="btn btn-primary btn-sm" data-goto="crear" id="createBtn">+ Crear torneo</a>
      </div>
    </header>

    <main class="page-content">

      <!-- INICIO -->
      <div class="section-panel active" id="panel-inicio">
        <div class="page-header">
          <div>
            <h1 data-user-welcome>Bienvenida, María</h1>
            <p>Gestiona tus torneos activos y crea nuevos eventos</p>
          </div>
        </div>

        <div class="kpi-grid" data-reveal>
          <div class="kpi-card">
            <div class="kpi-card__label">Torneos activos</div>
            <div class="kpi-card__value">3</div>
            <div class="kpi-card__delta">1 finalizado este mes</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-card__label">Equipos totales</div>
            <div class="kpi-card__value">52</div>
            <div class="kpi-card__delta">? +4 esta semana</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-card__label">Partidos jugados</div>
            <div class="kpi-card__value">24</div>
            <div class="kpi-card__delta">de 66 programados</div>
          </div>
        </div>

        <h2 style="font-size:var(--font-size-lg);color:var(--ink);margin-bottom:var(--space-4)">Mis torneos</h2>

        <div class="torneo-item">
          <div class="torneo-item__head">
            <div>
              <div class="torneo-item__title">? Copa Regional Fútbol 2026</div>
              <div class="torneo-item__meta">Liga · 12 equipos · Jornada 8 de 22</div>
            </div>
            <span class="badge badge-green">Activo</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:12px;color:var(--muted)">Progreso</span>
            <span style="font-size:12px;color:var(--muted-2);font-family:var(--mono)">36%</span>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width:36%"></div></div>
        </div>

        <div class="torneo-item">
          <div class="torneo-item__head">
            <div>
              <div class="torneo-item__title">??? Torneo Basket Invierno 2026</div>
              <div class="torneo-item__meta">Eliminatoria · 8 equipos · Inicio 01/07/2026</div>
            </div>
            <span class="badge badge-yellow">Próximo</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:12px;color:var(--muted)">Inscripciones</span>
            <span style="font-size:12px;color:var(--muted-2);font-family:var(--mono)">6/8 equipos</span>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width:75%;background:var(--red-bright)"></div></div>
        </div>

        <div class="torneo-item">
          <div class="torneo-item__head">
            <div>
              <div class="torneo-item__title">🎾 Copa Tenis Rápido</div>
              <div class="torneo-item__meta">Swiss · 16 jugadores · Ronda 4 de 5</div>
            </div>
            <span class="badge badge-green">Activo</span>
          </div>
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:12px;color:var(--muted)">Progreso</span>
            <span style="font-size:12px;color:var(--muted-2);font-family:var(--mono)">80%</span>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width:80%"></div></div>
        </div>
      </div>

      <!-- CREAR -->
      <div class="section-panel" id="panel-crear">
        <div class="page-header">
          <div>
            <h1>Crear torneo</h1>
            <p>Configura todos los parámetros de tu nuevo torneo</p>
          </div>
        </div>
        <div class="card" style="max-width:720px">
          <div class="card__body">
            <form id="createForm" novalidate>
              <h3 style="color:var(--ink);margin-bottom:var(--space-4)">Información básica</h3>
              <div class="form-group">
                <label class="form-label" for="torneoNombre">Nombre del torneo</label>
                <input class="form-control" type="text" id="torneoNombre" placeholder="ej. Copa Verano 2026" required />
              </div>
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label" for="deporte">Deporte</label>
                  <select class="form-control" id="deporte">
                    <option value="">Seleccionar...</option>
                    <option>Fútbol</option>
                    <option>Básquetbol</option>
                    <option>Tenis</option>
                    <option>Vóley</option>
                    <option>Ajedrez</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="sistema">Sistema de torneo</label>
                  <select class="form-control" id="sistema">
                    <option value="">Seleccionar...</option>
                    <option>Liga (todos contra todos)</option>
                    <option>Eliminación directa</option>
                    <option>Sistema suizo</option>
                    <option>Grupos + playoffs</option>
                  </select>
                </div>
              </div>
              <div class="form-grid-3">
                <div class="form-group">
                  <label class="form-label" for="maxEquipos">Máx. equipos</label>
                  <input class="form-control" type="number" id="maxEquipos" placeholder="16" min="2" />
                </div>
                <div class="form-group">
                  <label class="form-label" for="fechaInicio">Fecha inicio</label>
                  <input class="form-control" type="date" id="fechaInicio" />
                </div>
                <div class="form-group">
                  <label class="form-label" for="fechaFin">Fecha estimada fin</label>
                  <input class="form-control" type="date" id="fechaFin" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="descripcion">Descripción del torneo</label>
                <textarea class="form-control" id="descripcion" rows="3"
                  placeholder="Describe el torneo, reglas especiales, premios, etc." style="resize:vertical"></textarea>
              </div>
              <div style="display:flex;gap:var(--space-3)">
                <button type="submit" class="btn btn-primary">Crear torneo</button>
                <button type="reset" class="btn btn-ghost">Limpiar</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- PARTICIPANTES -->
      <div class="section-panel" id="panel-participantes">
        <div class="page-header">
          <div>
            <h1>Participantes</h1>
            <p>Equipos y jugadores inscritos en tus torneos</p>
          </div>
          <button class="btn btn-primary btn-sm">+ Inscribir equipo</button>
        </div>
        <div class="card">
          <div class="card__header" style="display:flex;justify-content:space-between;align-items:center">
            <h3>Copa Regional Fútbol 2026</h3>
            <select class="form-control" style="max-width:220px;padding:7px 12px">
              <option>Copa Regional Fútbol 2026</option>
              <option>Copa Tenis Rápido</option>
            </select>
          </div>
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead>
                <tr><th>#</th><th>Equipo</th><th>Capitán</th><th>Jugadores</th><th>Inscrito el</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <tr><td>1</td><td>Atlético Norte</td><td>Carlos García</td><td>15</td><td style="font-family:var(--mono);font-size:12px">10/03/2026</td><td><span class="badge badge-green">Confirmado</span></td></tr>
                <tr><td>2</td><td>Deportivo Sur</td><td>Juan Méndez</td><td>14</td><td style="font-family:var(--mono);font-size:12px">11/03/2026</td><td><span class="badge badge-green">Confirmado</span></td></tr>
                <tr><td>3</td><td>Club Rivera FC</td><td>Ana Suárez</td><td>16</td><td style="font-family:var(--mono);font-size:12px">12/03/2026</td><td><span class="badge badge-green">Confirmado</span></td></tr>
                <tr><td>4</td><td>FC Este</td><td>Pablo Rodríguez</td><td>13</td><td style="font-family:var(--mono);font-size:12px">14/03/2026</td><td><span class="badge badge-yellow">Pendiente</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RESULTADOS -->
      <div class="section-panel" id="panel-resultados">
        <div class="page-header">
          <div>
            <h1>Cargar resultados</h1>
            <p>Registra los marcadores de los partidos jugados</p>
          </div>
        </div>
        <div class="card" style="max-width:640px">
          <div class="card__header"><h3>Nuevo resultado</h3></div>
          <div class="card__body">
            <form id="resultadoForm" novalidate>
              <div class="form-group">
                <label class="form-label">Torneo</label>
                <select class="form-control">
                  <option>Copa Regional Fútbol 2026</option>
                  <option>Copa Tenis Rápido</option>
                </select>
              </div>
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label">Equipo local</label>
                  <select class="form-control">
                    <option>Atlético Norte</option>
                    <option>Deportivo Sur</option>
                    <option>Club Rivera FC</option>
                    <option>FC Este</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Equipo visitante</label>
                  <select class="form-control">
                    <option>FC Este</option>
                    <option>Atlético Norte</option>
                    <option>Deportivo Sur</option>
                    <option>Club Rivera FC</option>
                  </select>
                </div>
              </div>
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label">Goles local</label>
                  <input class="form-control" type="number" min="0" placeholder="0" />
                </div>
                <div class="form-group">
                  <label class="form-label">Goles visitante</label>
                  <input class="form-control" type="number" min="0" placeholder="0" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Fecha del partido</label>
                <input class="form-control" type="date" />
              </div>
              <button type="submit" class="btn btn-primary">Guardar resultado</button>
            </form>
          </div>
        </div>
      </div>

      <!-- POSICIONES -->
      <div class="section-panel" id="panel-posiciones">
        <div class="page-header">
          <div>
            <h1>Tabla de posiciones</h1>
            <p>Clasificación actualizada por torneo</p>
          </div>
          <select class="form-control" style="max-width:240px;padding:8px 14px">
            <option>Copa Regional Fútbol 2026</option>
            <option>Copa Tenis Rápido</option>
          </select>
        </div>
        <div class="card">
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead>
                <tr><th>#</th><th>Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>GF</th><th>GC</th><th>DG</th><th>Pts</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="pos-num gold">1</span></td>
                  <td><strong style="color:var(--ink)">Atlético Norte</strong></td>
                  <td>8</td><td>6</td><td>1</td><td>1</td><td>18</td><td>7</td><td>+11</td>
                  <td><strong style="font-family:var(--head);color:var(--ink)">19</strong></td>
                </tr>
                <tr>
                  <td><span class="pos-num silver">2</span></td>
                  <td><strong style="color:var(--ink)">Deportivo Sur</strong></td>
                  <td>8</td><td>5</td><td>2</td><td>1</td><td>14</td><td>8</td><td>+6</td>
                  <td><strong style="font-family:var(--head);color:var(--ink)">17</strong></td>
                </tr>
                <tr>
                  <td><span class="pos-num bronze">3</span></td>
                  <td><strong style="color:var(--ink)">Club Rivera FC</strong></td>
                  <td>8</td><td>5</td><td>1</td><td>2</td><td>12</td><td>9</td><td>+3</td>
                  <td><strong style="font-family:var(--head);color:var(--ink)">16</strong></td>
                </tr>
                <tr><td><span class="pos-num">4</span></td><td>FC Este</td><td>8</td><td>4</td><td>1</td><td>3</td><td>11</td><td>10</td><td>+1</td><td>13</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>
  </div>

  <script src="../../js/main.js"></script>
  <script src="../../js/dashboard.js"></script>
</body>
</html>


