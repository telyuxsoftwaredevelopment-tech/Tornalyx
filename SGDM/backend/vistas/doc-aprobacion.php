<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Aprobación de solicitud de acceso a la documentación de Tornalyx." />
  <script>document.documentElement.setAttribute('data-theme','dark');</script>
  <title><?= e($title ?? 'Tornalyx | Solicitud de acceso') ?></title>

  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../css/variables.css?v=15" />
  <link rel="stylesheet" href="../css/main.css?v=15" />
  <link rel="stylesheet" href="../css/components.css?v=15" />
  <style>
    .aprob-page { padding:var(--space-12) var(--space-4) var(--space-16); }
    .aprob-card {
      max-width:520px; margin:0 auto; text-align:center;
      background:var(--bg-card); border:1px solid var(--line-soft);
      border-radius:16px; padding:var(--space-8);
    }
    .aprob-ic { width:56px; height:56px; margin:0 auto var(--space-4); color:var(--red-bright); }
    .aprob-ic svg { width:100%; height:100%; fill:none; stroke:currentColor; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; }
    .aprob-card h1 { font-family:var(--head); font-size:var(--font-size-xl); color:var(--ink); margin-bottom:var(--space-3); }
    .aprob-card p { color:var(--muted); font-size:var(--font-size-sm); line-height:1.7; margin-bottom:var(--space-5); }
    .aprob-card p strong { color:var(--ink); }
    .aprob-who { background:var(--bg-2); border:1px solid var(--line-soft); border-radius:12px; padding:var(--space-4); margin-bottom:var(--space-6); }
    .aprob-who .n { font-family:var(--head); font-weight:600; color:var(--ink); }
    .aprob-who .e { color:var(--muted-2); font-size:13px; }
    .aprob-actions { display:flex; gap:var(--space-3); justify-content:center; flex-wrap:wrap; }
    .aprob-actions form { margin:0; }
  </style>
</head>
<body>

  <?= $partial('nav-publico', ['activo' => 'documentacion']) ?>

  <main class="aprob-page">
    <div class="wrap">
      <div class="aprob-card">
        <div class="aprob-ic">
          <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/><circle cx="12" cy="15.5" r="1.2"/></svg>
        </div>

<?php if (($estado ?? '') === 'confirmar'): ?>
        <h1>Solicitud de acceso</h1>
        <p>La siguiente persona pide acceso a la documentación de <strong><?= e($materiaNombre ?? '') ?></strong>:</p>
        <div class="aprob-who">
          <div class="n"><?= e($solicitante ?: 'Usuario') ?></div>
          <div class="e"><?= e($email ?? '') ?></div>
        </div>
        <div class="aprob-actions">
          <form method="post" action="/documentacion/resolver">
            <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <input type="hidden" name="accion" value="aprobar">
            <button class="btn btn-primary" type="submit">Aprobar acceso</button>
          </form>
          <form method="post" action="/documentacion/resolver">
            <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>">
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <input type="hidden" name="accion" value="rechazar">
            <button class="btn btn-ghost" type="submit">Rechazar</button>
          </form>
        </div>

<?php elseif (($estado ?? '') === 'resuelto'): ?>
        <?php $aprobado = (($resultado ?? '') === 'aprobado'); ?>
        <h1><?= $aprobado ? 'Acceso aprobado' : 'Solicitud rechazada' ?></h1>
        <?php if ($aprobado): ?>
          <p><strong><?= e($solicitante ?: 'El usuario') ?></strong> ya puede ver la documentación de <strong><?= e($materiaNombre ?? '') ?></strong> (tras verificar su identidad con un código enviado a su correo).</p>
        <?php else: ?>
          <p>Rechazaste la solicitud de acceso de <strong><?= e($solicitante ?: 'el usuario') ?></strong> a <strong><?= e($materiaNombre ?? '') ?></strong>.</p>
        <?php endif; ?>
        <a class="btn btn-ghost btn-sm" href="/">Volver al inicio</a>

<?php else: /* invalido */ ?>
        <h1>Enlace no válido</h1>
        <p>Este enlace de aprobación no es válido, ya fue usado o venció. Si la persona todavía necesita acceso, pedile que vuelva a solicitarlo para generar un enlace nuevo.</p>
        <a class="btn btn-ghost btn-sm" href="/">Volver al inicio</a>
<?php endif; ?>

      </div>
    </div>
  </main>

  <!-- ── FOOTER ─────────────────────────────────────────── -->
  <footer class="footer-wrap">
    <div class="wrap">
      <div class="foot-grid">
        <div class="foot-brand">
          <a class="brand" href="/">
            <span class="badge badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
            Tornalyx
          </a>
          <p>Llaves, calendarios y resultados en vivo — todo en un solo lugar.</p>
        </div>
        <div class="foot-col">
          <h5>Producto</h5>
          <a href="/torneos">Crear un torneo</a>
          <a href="/torneos">Buscar torneos</a>
          <a href="/nosotros">Precios</a>
          <a href="/nosotros">Programa de afiliados</a>
        </div>
        <div class="foot-col">
          <h5>Recursos</h5>
          <a href="/documentacion">Blog</a>
          <a href="/documentacion">Base de conocimiento</a>
          <a href="mailto:contacto@telyuxsoftwaredevelopment.com">Escríbenos</a>
        </div>
        <div class="foot-col">
          <h5>Conéctate</h5>
          <div class="foot-socials">
            <a href="https://x.com" target="_blank" rel="noopener noreferrer" class="foot-social-link" aria-label="X (Twitter)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
            </a>
            <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="foot-social-link" aria-label="YouTube">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
            </a>
            <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="foot-social-link" aria-label="LinkedIn">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 10.9v8.37H9.2V10.9H6.46M7.83 6.45a1.62 1.62 0 0 0-1.62 1.62c0 .9.72 1.63 1.62 1.63.9 0 1.63-.73 1.63-1.63 0-.9-.73-1.62-1.63-1.62z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>
      <div class="foot-bottom">
        <div class="foot-legal-links">
          <span>© 2026 Tornalyx</span>
          <span class="foot-sep" aria-hidden="true">·</span>
          <a href="/privacidad">Política de Privacidad</a>
          <span class="foot-sep" aria-hidden="true">·</span>
          <a href="/terminos">Términos de Servicio</a>
          <span class="foot-sep" aria-hidden="true">·</span>
          <a href="/terminos">Política de Cancelación y Reembolso</a>
          <span class="foot-sep" aria-hidden="true">·</span>
          <span>Hecho con ♡</span>
        </div>
      </div>
    </div>
  </footer>

  <script src="../js/main.js?v=15"></script>
</body>
</html>
