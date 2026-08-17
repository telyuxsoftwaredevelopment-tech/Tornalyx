<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Documentación del proyecto Tornalyx · SGDM, organizada por materia." />
  <script>(function(){try{var t=localStorage.getItem('tornalyx-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
  <title><?= e($title ?? 'Tornalyx | Documentación') ?></title>

  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/main.css?v=5" />
  <link rel="stylesheet" href="../css/components.css" />
  <style>
    .doc-page { padding:var(--space-10) var(--space-4) var(--space-16); }
    .doc-head { text-align:center; margin-bottom:var(--space-10); }
    .doc-head h1 { font-family:var(--head); font-size:var(--font-size-3xl); color:var(--ink); margin-top:6px; }
    .doc-head .doc-sub { color:var(--muted); font-size:var(--font-size-sm); margin-top:8px; }

    /* ── Grilla de materias ─────────────────────────────── */
    .doc-materias { display:grid; grid-template-columns:1fr; gap:var(--space-4); max-width:960px; margin:0 auto; }
    .doc-materia {
      display:flex; flex-direction:column; gap:6px;
      justify-content:space-between;
      padding:var(--space-6); background:var(--bg-card);
      border:1px solid var(--line-soft); border-radius:16px;
      text-decoration:none; transition:border-color var(--transition-fast), transform var(--transition-fast);
    }
    .doc-materia:hover { border-color:var(--red); transform:translateY(-2px); }
    .doc-materia__ic { width:40px; height:40px; color:var(--red-bright); margin-bottom:var(--space-2); }
    .doc-materia__ic svg { width:100%; height:100%; fill:none; stroke:currentColor; stroke-width:1.6; stroke-linecap:round; stroke-linejoin:round; }
    .doc-materia__nombre { font-family:var(--head); font-size:var(--font-size-lg); font-weight:600; color:var(--ink); }
    .doc-materia__desc { color:var(--muted); font-size:var(--font-size-sm); line-height:1.6; }
    .doc-materia__go { color:var(--red-bright); font-size:13px; font-weight:600; margin-top:var(--space-2); }

    /* ── Cabecera de materia ────────────────────────────── */
    .doc-back { display:inline-flex; align-items:center; gap:8px; margin-bottom:var(--space-6); color:var(--red-bright); font-size:var(--font-size-sm); text-decoration:none; }
    .doc-back:hover { color:var(--red); }
    .doc-materia-head { display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:var(--space-4); margin-bottom:var(--space-6); padding-bottom:var(--space-5); border-bottom:1px solid var(--line); }
    .doc-materia-head h1 { font-family:var(--head); font-size:var(--font-size-2xl); color:var(--ink); margin-top:4px; }
    .doc-meta { display:flex; flex-direction:column; align-items:flex-end; gap:4px; font-size:12px; color:var(--muted-2); }
    .doc-meta a { color:var(--red-bright); text-decoration:none; }
    .doc-meta a:hover { color:var(--red); }
    .doc-live { display:inline-flex; align-items:center; gap:6px; }
    .doc-live .status-dot { width:7px; height:7px; }

    /* ── Contenido renderizado desde Google Docs ────────── */
    .doc-content { max-width:900px; margin:0 auto; }
    .doc-error { max-width:900px; margin:0 auto; }
    .markdown { color:var(--muted); font-size:var(--font-size-base); line-height:1.75; overflow-wrap:break-word; }
    .markdown > :first-child { margin-top:0; }
    .markdown h1, .markdown h2, .markdown h3, .markdown h4, .markdown h5, .markdown h6 { font-family:var(--head); color:var(--ink); line-height:1.3; }
    .markdown h1 { font-size:var(--font-size-2xl); margin:var(--space-8) 0 var(--space-4); }
    .markdown h2 { font-size:var(--font-size-xl); margin:var(--space-8) 0 var(--space-3); padding-top:var(--space-5); border-top:1px solid var(--line); }
    .markdown h3 { font-size:var(--font-size-lg); color:var(--red-bright); margin:var(--space-5) 0 var(--space-2); }
    .markdown h4, .markdown h5, .markdown h6 { font-size:var(--font-size-base); margin:var(--space-4) 0 var(--space-2); }
    .markdown p { margin:0 0 var(--space-4); }
    .markdown a { color:var(--red-bright); text-decoration:underline; text-underline-offset:2px; }
    .markdown a:hover { color:var(--red); }
    .markdown ul, .markdown ol { margin:0 0 var(--space-4) var(--space-6); display:flex; flex-direction:column; gap:6px; }
    .markdown li { color:var(--muted); }
    .markdown ul li { list-style:disc; }
    .markdown ol li { list-style:decimal; }
    .markdown strong, .markdown b { color:var(--ink); font-weight:600; }
    .markdown blockquote { margin:0 0 var(--space-4); padding:var(--space-3) var(--space-4); border-left:3px solid var(--red); background:rgba(236,28,36,.06); border-radius:0 8px 8px 0; color:var(--muted); }
    .markdown hr { border:none; border-top:1px solid var(--line); margin:var(--space-6) 0; }
    .markdown code { font-family:var(--mono); font-size:.9em; background:var(--bg-2); border:1px solid var(--line-soft); border-radius:6px; padding:2px 6px; color:var(--ink); }
    .markdown pre { margin:0 0 var(--space-4); padding:var(--space-4); background:var(--bg); border:1px solid var(--line); border-radius:10px; overflow-x:auto; }
    .markdown pre code { background:none; border:none; padding:0; font-size:13px; line-height:1.6; color:var(--muted); white-space:pre; }
    .markdown img { max-width:100%; height:auto; border-radius:8px; margin:var(--space-2) 0; }
    /* Tablas: contenedor con scroll horizontal en pantallas chicas. */
    .markdown table { width:100%; border-collapse:collapse; font-size:var(--font-size-sm); margin:0 0 var(--space-4); display:block; overflow-x:auto; }
    .markdown th, .markdown td { padding:10px 14px; border:1px solid var(--line); text-align:left; vertical-align:top; min-width:80px; }
    .markdown th { font-family:var(--head); color:var(--ink); background:rgba(255,255,255,.03); }
    .markdown td { color:var(--muted); }

    /* ── Pantalla de acceso restringido (gate) ──────────── */
    .doc-gate { max-width:520px; margin:var(--space-8) auto 0; }
    .doc-gate__card { background:var(--bg-card); border:1px solid var(--line-soft); border-radius:16px; padding:var(--space-8); text-align:center; }
    .doc-gate__ic { width:52px; height:52px; margin:0 auto var(--space-4); color:var(--red-bright); }
    .doc-gate__ic svg { width:100%; height:100%; fill:none; stroke:currentColor; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; }
    .doc-gate__card h2 { font-family:var(--head); font-size:var(--font-size-xl); color:var(--ink); margin-bottom:var(--space-3); }
    .doc-gate__card p { color:var(--muted); font-size:var(--font-size-sm); line-height:1.7; margin-bottom:var(--space-5); }
    .doc-gate__card p strong { color:var(--ink); }
    .doc-gate form { display:flex; flex-direction:column; align-items:center; gap:var(--space-3); }
    .doc-code-input { width:200px; text-align:center; font-family:var(--mono); font-size:1.4rem; letter-spacing:8px; padding:12px; }
    .doc-resend { margin-top:var(--space-3); }
    .doc-flash { max-width:520px; margin:0 auto var(--space-4); }

    @media (min-width:768px) {
      .doc-materias { grid-template-columns:repeat(2, 1fr); }
    }
  </style>
</head>
<body>

  <?= $partial('nav-publico', ['activo' => 'documentacion']) ?>

  <main class="doc-page">
    <div class="wrap">

<?php if (($materia ?? null) === null): ?>
      <!-- ── Estado 1 · Grilla de materias ───────────────── -->
      <?php
        // Icono por materia (SVG inline: la CSP permite estilos, no scripts).
        $iconos = [
          'ciberseguridad'      => '<svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15" r="1.3"/></svg>',
          'ingenieria-software' => '<svg viewBox="0 0 24 24"><path d="M12 3l7 4v5c0 4.4-3 7.6-7 9-4-1.4-7-4.6-7-9V7l7-4Z"/><path d="M9.5 12l1.8 1.8L15 10"/></svg>',
          'sistemas-operativos' => '<svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
          'tutoria'             => '<svg viewBox="0 0 24 24"><path d="M3 7l9-4 9 4-9 4-9-4Z"/><path d="M7 9v5c0 1.5 2.2 3 5 3s5-1.5 5-3V9"/><path d="M21 7v6"/></svg>',
        ];
      ?>
      <div class="doc-head" data-reveal>
        <p class="eyebrow eyebrow-center">Documentación del proyecto</p>
        <h1>Materias del proyecto</h1>
        <p class="doc-sub">Elegí una materia para ver su documentación dentro de la plataforma.</p>
      </div>
      <div class="doc-materias">
        <?php foreach ($materias as $slug => $m): ?>
          <a class="doc-materia" href="/documentacion?materia=<?= e($slug) ?>" data-reveal>
            <span class="doc-materia__ic"><?= $iconos[$slug] ?? '' ?></span>
            <span class="doc-materia__nombre"><?= e($m['nombre']) ?></span>
            <span class="doc-materia__desc"><?= e($m['desc']) ?></span>
            <span class="doc-materia__go">Ver documento →</span>
          </a>
        <?php endforeach; ?>
        <?php foreach (($enlaces ?? []) as $slug => $en): ?>
          <a class="doc-materia" href="/documentacion?enlace=<?= e($slug) ?>" target="_blank" rel="noopener" data-reveal>
            <span class="doc-materia__ic"><?= $iconos[$slug] ?? '<svg viewBox="0 0 24 24"><path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/></svg>' ?></span>
            <span class="doc-materia__nombre"><?= e($en['nombre']) ?></span>
            <span class="doc-materia__desc"><?= e($en['desc']) ?></span>
            <span class="doc-materia__go">Ver presentación ↗</span>
          </a>
        <?php endforeach; ?>
      </div>

<?php else: ?>
      <?php $gate = $gate ?? null; ?>
      <!-- ── Estado 2 · Documento de la materia (en vivo) ── -->
      <a class="doc-back" href="/documentacion">
        <img src="../assets/icon-volver.svg" width="17" height="17" alt="">Todas las materias
      </a>
      <div class="doc-materia-head">
        <div>
          <p class="eyebrow">Documentación</p>
          <h1><?= e($materia['nombre']) ?></h1>
        </div>
        <?php if ($gate === null): ?>
        <div class="doc-meta">
          <span class="doc-live"><span class="status-dot status-dot--green"></span>Se actualiza automáticamente</span>
          <a href="<?= e($materia['url']) ?>" target="_blank" rel="noopener">Abrir original en Google Docs ↗</a>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($gate !== null): ?>
        <!-- Acceso restringido: registrado + aprobado + código por email -->
        <?php if (!empty($flash)): ?>
          <div class="doc-flash"><div class="alert alert-info"><?= e($flash) ?></div></div>
        <?php endif; ?>
        <div class="doc-gate">
          <div class="doc-gate__card">
            <div class="doc-gate__ic">
              <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/><circle cx="12" cy="15.5" r="1.2"/></svg>
            </div>

            <?php if ($gate['estado'] === 'login'): ?>
              <h2>Documentación restringida</h2>
              <p>Este documento solo está disponible para usuarios registrados y autorizados. Iniciá sesión para poder solicitar acceso.</p>
              <a class="btn btn-primary" href="/login">Iniciar sesión</a>

            <?php elseif ($gate['estado'] === 'solicitar'): ?>
              <h2>Acceso restringido</h2>
              <p>Para ver la documentación de <strong><?= e($materia['nombre']) ?></strong> necesitás autorización. Solicitá acceso y un administrador lo revisará.</p>
              <form method="post" action="/documentacion/solicitar">
                <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
                <input type="hidden" name="materia" value="<?= e($materiaSlug) ?>">
                <button class="btn btn-primary" type="submit">Solicitar acceso</button>
              </form>

            <?php elseif ($gate['estado'] === 'pendiente'): ?>
              <h2>Solicitud pendiente</h2>
              <p>Tu solicitud de acceso está <strong>pendiente de aprobación</strong>. Te avisaremos por correo cuando un administrador la resuelva.</p>

            <?php elseif ($gate['estado'] === 'rechazado'): ?>
              <h2>Acceso rechazado</h2>
              <p>Tu solicitud de acceso fue rechazada. Si creés que es un error, podés volver a solicitarlo.</p>
              <form method="post" action="/documentacion/solicitar">
                <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
                <input type="hidden" name="materia" value="<?= e($materiaSlug) ?>">
                <button class="btn btn-primary" type="submit">Solicitar de nuevo</button>
              </form>

            <?php else: /* codigo */ ?>
              <h2>Verificación por código</h2>
              <p>Tu acceso está aprobado. Para ver el documento, verificá tu identidad con un código enviado a <strong><?= e($gate['emailMasked']) ?></strong>.</p>
              <?php if (empty($gate['codigoActivo'])): ?>
                <form method="post" action="/documentacion/codigo">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
                  <input type="hidden" name="materia" value="<?= e($materiaSlug) ?>">
                  <button class="btn btn-primary" type="submit">Enviarme el código</button>
                </form>
              <?php else: ?>
                <form method="post" action="/documentacion/verificar">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
                  <input type="hidden" name="materia" value="<?= e($materiaSlug) ?>">
                  <input class="form-control doc-code-input" type="text" name="codigo"
                         inputmode="numeric" maxlength="6" pattern="\d{6}" placeholder="000000"
                         autocomplete="one-time-code" required aria-label="Código de 6 dígitos">
                  <button class="btn btn-primary" type="submit">Verificar y ver documento</button>
                </form>
                <form class="doc-resend" method="post" action="/documentacion/codigo">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
                  <input type="hidden" name="materia" value="<?= e($materiaSlug) ?>">
                  <button class="btn btn-ghost btn-sm" type="submit" <?= ((int) ($gate['cooldown'] ?? 0)) > 0 ? 'disabled' : '' ?>>
                    <?= ((int) ($gate['cooldown'] ?? 0)) > 0 ? 'Reenviar en ' . (int) $gate['cooldown'] . ' s' : 'Reenviar código' ?>
                  </button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

      <?php elseif (($error ?? null) !== null): ?>
        <div class="doc-error">
          <div class="alert alert-error"><?= e($error) ?></div>
        </div>
      <?php else: ?>
        <article class="doc-content markdown">
          <?= $contenido /* HTML ya saneado en el servidor (allowlist de tags/atributos) */ ?>
        </article>
      <?php endif; ?>

<?php endif; ?>

    </div>
  </main>

  <footer class="footer-wrap">
    <div class="wrap">
      <div class="foot-bottom">
        <span>© 2026 Tornalyx · por Telyux Software Development</span>
        <span>soporte@telyuxsoftwaredevelopment.com</span>
      </div>
    </div>
  </footer>

  <script src="../js/main.js?v=5"></script>
</body>
</html>
