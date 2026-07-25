<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($title ?? 'Tornalyx | Panel de Administración') ?></title>
  
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
      padding:var(--space-5) var(--space-4);
      border-bottom:1px solid var(--line);
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
      background:var(--red);
      display:flex; align-items:center; justify-content:center;
      font-weight:700; font-size:12px; color:#fff; flex:none; font-family:var(--head);
    }
    .user-name  { font-size:var(--font-size-sm); color:var(--ink); font-weight:600; }
    .user-role  { font-size:11px; color:var(--muted-2); font-family:var(--mono); }

    .sidebar-overlay {
      position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:150;
      opacity:0; pointer-events:none; transition:opacity .25s;
    }
    .sidebar-overlay.show { opacity:1; pointer-events:all; }

    .app-main { margin-left:var(--sidebar-width); flex:1; display:flex; flex-direction:column; min-width:0; }

    .topbar {
      position:sticky; top:0; z-index:100;
      background:rgba(11,7,8,.9); backdrop-filter:blur(12px);
      border-bottom:1px solid var(--line-soft);
      padding:0 var(--space-6); height:64px;
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
      display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
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

    .dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; vertical-align:middle; }
    .dot-green  { background:#22c55e; box-shadow:0 0 6px rgba(34,197,94,.6); }
    .dot-yellow { background:#eab308; }
    .dot-muted  { background:var(--muted-2); }

    /* Tarjeta de torneo del listado (misma presentación que el panel del organizador) */
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

    .progress-bar  { height:6px; background:var(--line); border-radius:3px; overflow:hidden; margin-top:6px; }
    .progress-fill { height:100%; border-radius:3px; background:var(--red); transition:width .5s ease; }

    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4); }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:var(--space-4); }
    @media(max-width:640px) {
      .form-grid-2 { grid-template-columns:1fr; }
      .form-grid-3 { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="/">
      <span class="mark badge-crop" style="background-image:url(../../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
      Tornalyx
    </a>
    <nav class="sidebar__nav">
      <div class="sidebar__section">Principal</div>
      <a class="sidebar__item active" data-section="inicio" data-title="Dashboard">
        <span class="icon">📊</span> Dashboard
      </a>
      <div class="sidebar__section">Gestión</div>
      <a class="sidebar__item" data-section="usuarios" data-title="Usuarios">
        <span class="icon">👤</span> Usuarios
      </a>
      <a class="sidebar__item" data-section="torneos" data-title="Torneos">
        <span class="icon">🏆</span> Torneos
      </a>
      <a class="sidebar__item" data-section="equipos" data-title="Equipos">
        <span class="icon">👥</span> Equipos
      </a>
      <a class="sidebar__item" data-section="resultados" data-title="Resultados">
        <span class="icon">📋</span> Resultados
      </a>
      <div class="sidebar__section">Documentación</div>
      <a class="sidebar__item" data-section="solicitudes" data-title="Solicitudes de acceso">
        <span class="icon">🔒</span> Solicitudes de acceso
      </a>
      <div class="sidebar__section">Sistema</div>
      <a class="sidebar__item" data-section="reportes" data-title="Reportes">
        <span class="icon">📈</span> Reportes
      </a>
      <a class="sidebar__item" data-section="config" data-title="Configuración">
        <span class="icon">⚙️</span> Configuración
      </a>
    </nav>
    <div class="sidebar__footer">
      <div class="user-row">
        <div class="user-avatar" data-user-avatar>A</div>
        <div>
          <div class="user-name" data-user-name>Administrador</div>
          <div class="user-role" data-user-role>admin</div>
        </div>
        <a href="/logout" style="margin-left:auto;color:var(--muted-2)" title="Cerrar sesión">🚪</a>
      </div>
    </div>
  </aside>

  <div class="app-main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle" aria-label="Menú">☰</button>
        <span id="topbarTitle" style="font-family:var(--head);font-weight:700;color:var(--ink)">Dashboard</span>
      </div>
      <div style="display:flex;align-items:center;gap:var(--space-4)">
        <span style="font-size:12px;font-family:var(--mono);color:var(--muted-2)">
          <span class="dot dot-green"></span>Sistema activo
        </span>
        <a href="/torneos" class="btn btn-ghost btn-sm">Ver sitio</a>
      </div>
    </header>

    <main class="page-content">

      <!-- INICIO -->
      <div class="section-panel active" id="panel-inicio">
        <div class="page-header">
          <div>
            <h1 data-user-welcome>Bienvenido, Administrador</h1>
            <p>Resumen general del sistema Tornalyx · Actualizado ahora</p>
          </div>
        </div>
        <!-- KPIs y actividad los completa admin.js con GET /api/admin/stats -->
        <div class="kpi-grid" data-reveal>
          <div class="kpi-card">
            <div class="kpi-card__label">Usuarios registrados</div>
            <div class="kpi-card__value" id="kpiUsuarios">—</div>
            <div class="kpi-card__delta" id="kpiUsuariosDelta"></div>
          </div>
          <div class="kpi-card">
            <div class="kpi-card__label">Torneos activos</div>
            <div class="kpi-card__value" id="kpiTorneos">—</div>
            <div class="kpi-card__delta" id="kpiTorneosDelta"></div>
          </div>
          <div class="kpi-card">
            <div class="kpi-card__label">Equipos inscritos</div>
            <div class="kpi-card__value" id="kpiEquipos">—</div>
            <div class="kpi-card__delta" id="kpiEquiposDelta"></div>
          </div>
          <div class="kpi-card">
            <div class="kpi-card__label">Partidos registrados</div>
            <div class="kpi-card__value" id="kpiPartidos">—</div>
            <div class="kpi-card__delta" id="kpiPartidosDelta"></div>
          </div>
        </div>
        <div class="card">
          <div class="card__header"><h3>Actividad reciente</h3></div>
          <div id="actividadReciente" style="display:flex;flex-direction:column;gap:var(--space-3);padding:var(--space-4)">
            <div style="color:var(--muted);font-size:var(--font-size-sm)">Cargando actividad…</div>
          </div>
        </div>
      </div>

      <!-- USUARIOS -->
      <div class="section-panel" id="panel-usuarios">
        <div class="page-header">
          <div><h1>Usuarios</h1><p>Gestión de usuarios del sistema</p></div>
          <button class="btn btn-primary btn-sm" id="nuevoUsuarioBtn">+ Nuevo usuario</button>
        </div>
        <div class="card">
          <div class="card__header" style="display:flex;justify-content:space-between;align-items:center;gap:var(--space-3);flex-wrap:wrap">
            <h3>Lista de usuarios</h3>
            <div style="display:flex;gap:var(--space-2)">
              <select class="form-control" id="usuariosRol" style="max-width:180px;padding:8px 14px">
                <option value="">Todos los roles</option>
                <option value="participante">Participantes</option>
                <option value="organizador">Organizadores</option>
                <option value="administrador">Administradores</option>
              </select>
              <input class="form-control" type="search" id="usuariosBuscar" placeholder="Buscar..." style="max-width:240px;padding:8px 14px" />
            </div>
          </div>
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Registrado</th><th></th></tr>
              </thead>
              <!-- Las filas las completa admin.js con GET /api/admin/usuarios -->
              <tbody id="usuariosBody"></tbody>
            </table>
          </div>
          <div class="card__footer" id="usuariosFooter" style="font-size:12px;color:var(--muted-2);font-family:var(--mono)">
            Cargando usuarios…
          </div>
        </div>
      </div>

      <!-- TORNEOS -->
      <div class="section-panel" id="panel-torneos">
        <div class="page-header">
          <div><h1>Torneos</h1><p>Todos los torneos del sistema</p></div>
          <button class="btn btn-primary btn-sm" data-toggle-create>+ Nuevo torneo</button>
        </div>

        <!-- Formulario de creación: oculto hasta pulsar "+ Nuevo torneo" -->
        <div class="card hidden" id="createCard" style="max-width:720px;margin-bottom:var(--space-6)">
          <div class="card__header"><h3>Nuevo torneo</h3></div>
          <div class="card__body">
            <form id="createForm" novalidate>
              <div class="form-group">
                <label class="form-label" for="torneoNombre">Nombre del torneo</label>
                <input class="form-control" type="text" id="torneoNombre" name="nombre"
                       placeholder="ej. Copa Verano 2026" maxlength="120" required />
              </div>
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label" for="deporte">Deporte</label>
                  <select class="form-control" id="deporte" name="disciplina" required>
                    <option value="">Seleccionar...</option>
                    <option value="Fútbol">Fútbol</option>
                    <option value="Básquetbol">Básquetbol</option>
                    <option value="Tenis">Tenis</option>
                    <option value="Vóley">Vóley</option>
                    <option value="Ajedrez">Ajedrez</option>
                    <option value="eSports">eSports</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label" for="sistema">Sistema de torneo</label>
                  <!-- Los value coinciden con el ENUM `formato` de la tabla torneos -->
                  <select class="form-control" id="sistema" name="formato" required>
                    <option value="">Seleccionar...</option>
                    <option value="liga">Liga (todos contra todos)</option>
                    <option value="eliminacion_directa">Eliminación directa</option>
                    <option value="suizo">Sistema suizo</option>
                    <option value="grupos_playoff">Grupos + playoffs</option>
                  </select>
                </div>
              </div>
              <div class="form-grid-3">
                <div class="form-group">
                  <label class="form-label" for="maxEquipos">Máx. participantes</label>
                  <input class="form-control" type="number" id="maxEquipos" name="max_participantes"
                         placeholder="16" min="2" max="512" />
                </div>
                <div class="form-group">
                  <label class="form-label" for="fechaInicio">Fecha inicio</label>
                  <input class="form-control" type="date" id="fechaInicio" name="fecha_inicio" />
                </div>
                <div class="form-group">
                  <label class="form-label" for="fechaFin">Fecha estimada fin</label>
                  <input class="form-control" type="date" id="fechaFin" name="fecha_fin" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="descripcion">Descripción del torneo</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="2000"
                  placeholder="Describe el torneo, reglas especiales, premios, etc." style="resize:vertical"></textarea>
              </div>
              <div class="form-group">
                <label class="form-label" for="publicar">Al crearlo</label>
                <select class="form-control" id="publicar" name="publicar">
                  <option value="1">Publicar y abrir inscripciones</option>
                  <option value="0">Guardar como borrador (no visible al público)</option>
                </select>
              </div>
              <div style="display:flex;gap:var(--space-3)">
                <button type="submit" class="btn btn-primary">Crear torneo</button>
                <button type="reset" class="btn btn-ghost" data-toggle-create>Cancelar</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Lista de todos los torneos: la completa organizador.js -->
        <div id="misTorneos"></div>
      </div>

      <!-- EQUIPOS -->
      <div class="section-panel" id="panel-equipos">
        <div class="page-header">
          <div><h1>Equipos</h1><p>Todos los equipos registrados</p></div>
        </div>
        <div class="card">
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead>
                <tr><th>Nombre</th><th>Deporte</th><th>Capitán</th><th>Jugadores</th><th>Torneos</th></tr>
              </thead>
              <tbody>
                <tr><td>Atlético Norte</td><td>Fútbol</td><td>Carlos García</td><td>15</td><td>2</td></tr>
                <tr><td>Deportivo Sur</td><td>Fútbol</td><td>Juan Méndez</td><td>14</td><td>1</td></tr>
                <tr><td>Club Basket CE</td><td>Básquetbol</td><td>Laura Sosa</td><td>10</td><td>1</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- RESULTADOS -->
      <div class="section-panel" id="panel-resultados">
        <div class="page-header">
          <div><h1>Resultados</h1><p>Gestión de resultados de partidos</p></div>
          <button class="btn btn-primary btn-sm">+ Cargar resultado</button>
        </div>
        <div class="card">
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead>
                <tr><th>Torneo</th><th>Local</th><th>Marcador</th><th>Visitante</th><th>Fecha</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>Copa Regional</td><td>Atlético Norte</td>
                  <td style="text-align:center;font-family:var(--head);font-weight:700;color:var(--ink)">3 · 1</td>
                  <td>Los Guerreros</td>
                  <td style="font-family:var(--mono);font-size:12px">14/06/2026</td>
                  <td><span class="badge badge-green">Confirmado</span></td>
                </tr>
                <tr>
                  <td>Copa Regional</td><td>Deportivo Sur</td>
                  <td style="text-align:center;font-family:var(--head);font-weight:700;color:var(--ink)">2 · 2</td>
                  <td>Club Rivera FC</td>
                  <td style="font-family:var(--mono);font-size:12px">14/06/2026</td>
                  <td><span class="badge badge-green">Confirmado</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SOLICITUDES DE ACCESO A DOCUMENTACIÓN -->
      <div class="section-panel" id="panel-solicitudes">
        <div class="page-header">
          <div><h1>Solicitudes de acceso</h1><p>Autorizaciones para la documentación restringida</p></div>
          <button class="btn btn-ghost btn-sm" id="solicitudesRefrescar">Actualizar</button>
        </div>
        <div class="card">
          <div style="overflow-x:auto">
            <table class="data-table">
              <thead>
                <tr><th>Usuario</th><th>Correo</th><th>Materia</th><th>Estado</th><th>Solicitado</th><th>Acciones</th></tr>
              </thead>
              <!-- Las filas las completa admin.js con GET /api/admin/doc-solicitudes -->
              <tbody id="solicitudesBody"></tbody>
            </table>
          </div>
          <div class="card__footer" id="solicitudesFooter" style="font-size:12px;color:var(--muted-2);font-family:var(--mono)">
            Cargando…
          </div>
        </div>
      </div>

      <!-- REPORTES -->
      <div class="section-panel" id="panel-reportes">
        <div class="page-header">
          <div><h1>Reportes</h1><p>Métricas y estadísticas del sistema</p></div>
          <button class="btn btn-ghost btn-sm">Exportar CSV</button>
        </div>
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-card__label">Torneos este mes</div>
            <div class="kpi-card__value">8</div>
            <div class="kpi-card__delta">▲ +2 vs mes anterior</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-card__label">Partidos este mes</div>
            <div class="kpi-card__value">96</div>
            <div class="kpi-card__delta">▲ +14 vs mes anterior</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-card__label">Nuevos usuarios</div>
            <div class="kpi-card__value">42</div>
            <div class="kpi-card__delta">▼ -3 vs mes anterior</div>
          </div>
        </div>
      </div>

      <!-- CONFIG -->
      <div class="section-panel" id="panel-config">
        <div class="page-header">
          <div><h1>Configuración</h1><p>Ajustes generales del sistema</p></div>
        </div>
        <div class="card" style="max-width:560px">
          <div class="card__body">
            <div class="form-group">
              <label class="form-label">Nombre de la plataforma</label>
              <input class="form-control" type="text" value="Tornalyx" />
            </div>
            <div class="form-group">
              <label class="form-label">Correo de contacto</label>
              <input class="form-control" type="email" value="admin@tornalyx.uy" />
            </div>
            <div class="form-group">
              <label class="form-label">Zona horaria</label>
              <select class="form-control">
                <option selected>America/Montevideo (UTC-3)</option>
                <option>America/Buenos_Aires (UTC-3)</option>
              </select>
            </div>
            <button class="btn btn-primary">Guardar cambios</button>
          </div>
        </div>
      </div>

    </main>
  </div>

  <!-- Modal de alta/edición de usuario (lo maneja admin.js) -->
  <div class="modal" id="usuarioModal" aria-hidden="true">
    <div class="modal__box" style="max-width:560px">
      <button class="modal__close" data-modal-close aria-label="Cerrar">✕</button>
      <h3 class="modal__title" id="usuarioModalTitle">Nuevo usuario</h3>
      <form id="usuarioForm" novalidate>
        <input type="hidden" name="id" id="usuarioId" />
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="usuarioNombre">Nombre</label>
            <input class="form-control" type="text" id="usuarioNombre" name="nombre" maxlength="60" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="usuarioApellido">Apellido</label>
            <input class="form-control" type="text" id="usuarioApellido" name="apellido" maxlength="60" required />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="usuarioEmail">Correo electrónico</label>
          <input class="form-control" type="email" id="usuarioEmail" name="email" maxlength="120" required />
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="usuarioRolCampo">Rol</label>
            <select class="form-control" id="usuarioRolCampo" name="rol" required>
              <option value="participante">Participante</option>
              <option value="organizador">Organizador</option>
              <option value="administrador">Administrador</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="usuarioEstado">Estado</label>
            <select class="form-control" id="usuarioEstado" name="estado" required>
              <option value="activo">Activo</option>
              <option value="suspendido">Suspendido</option>
              <option value="pendiente">Pendiente</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="usuarioFechaNac">Fecha de nacimiento</label>
          <input class="form-control" type="date" id="usuarioFechaNac" name="fecha_nac" required />
        </div>
        <div class="form-group">
          <label class="form-label" for="usuarioPassword">Contraseña</label>
          <input class="form-control" type="password" id="usuarioPassword" name="password"
                 autocomplete="new-password" />
          <!-- El texto de ayuda cambia entre alta y edición desde admin.js -->
          <small id="usuarioPasswordHint" style="color:var(--muted-2);font-size:12px"></small>
        </div>
        <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
          <button type="submit" class="btn btn-primary" id="usuarioSubmit">Crear usuario</button>
          <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../../js/main.js"></script>
  <script src="../../js/dashboard.js"></script>
  <script src="../../js/torneo-ui.js"></script>
  <script src="../../js/organizador.js"></script>
  <script src="../../js/admin.js"></script>
</body>
</html>


