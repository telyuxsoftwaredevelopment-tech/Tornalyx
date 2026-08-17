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
  <link rel="stylesheet" href="../css/variables.css?v=11" />
  <link rel="stylesheet" href="../css/main.css?v=11" />
  <link rel="stylesheet" href="../css/components.css?v=11" />
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

  <footer class="footer-wrap">
    <div class="wrap">
      <div class="foot-bottom">
        <span>© 2026 Tornalyx · por Telyux Software Development</span>
        <span>soporte@telyuxsoftwaredevelopment.com</span>
      </div>
    </div>
  </footer>

  <script src="../js/main.js?v=11"></script>
</body>
</html>
