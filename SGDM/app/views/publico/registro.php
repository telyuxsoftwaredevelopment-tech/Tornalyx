<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($title ?? 'Tornalyx | Crear cuenta') ?></title>
  
  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/main.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <style>
    .auth-page {
      min-height:calc(100vh - var(--navbar-height));
      display:flex; align-items:flex-start; justify-content:center;
      padding:var(--space-8) var(--space-4);
    }
    .auth-card { width:100%; max-width:520px; }
    /* Role cards */
    .role-grid { display:grid; grid-template-columns:1fr 1fr; gap:var(--space-3); }
    .role-card {
      border:1.5px solid var(--line); border-radius:14px;
      padding:var(--space-5); cursor:pointer;
      transition:all var(--transition-fast);
      text-align:center; background:var(--bg-2);
    }
    .role-card:hover  { border-color:var(--red-deep); background:rgba(236,28,36,.06); }
    .role-card.selected { border-color:var(--red); background:rgba(236,28,36,.12); box-shadow:0 0 20px -8px var(--red-glow); }
    .role-card__icon  { font-size:2rem; margin-bottom:var(--space-2); }
    .role-card__title { font-family:var(--head); font-weight:600; font-size:var(--font-size-sm); color:var(--ink); margin-bottom:4px; }
    .role-card__desc  { font-size:12px; color:var(--muted-2); }
    /* Password strength */
    .strength-bar { height:4px; border-radius:2px; background:var(--line); margin-top:8px; overflow:hidden; }
    .strength-fill { height:100%; border-radius:2px; transition:all var(--transition-base); width:0; }
    .strength-label { font-size:12px; margin-top:4px; color:var(--muted-2); }
    /* Password requirements checklist */
    .pass-reqs { list-style:none; margin:10px 0 0; padding:0; display:grid; gap:6px; }
    .pass-reqs li {
      display:flex; align-items:center; gap:8px;
      font-size:12px; color:var(--muted-2); transition:color var(--transition-fast);
    }
    .pass-reqs .req-ico {
      width:18px; height:18px; border-radius:50%; flex:none;
      border:1.5px solid var(--line); color:transparent;
      display:inline-flex; align-items:center; justify-content:center;
      font-size:11px; font-weight:700; line-height:1;
      transition:all var(--transition-fast);
    }
    /* La marca ✓ siempre está presente pero transparente; al cumplirse el
       requisito el círculo se vuelve verde y el tick se hace visible. */
    .pass-reqs .req-ico::after { content:'\2713'; } /* ✓ */
    .pass-reqs li.met { color:#22c55e; }
    .pass-reqs li.met .req-ico { background:#22c55e; border-color:#22c55e; color:#fff; }
    .link-red { color:var(--red-bright); }
    .link-red:hover { color:var(--red); }
    .check-label { display:flex; gap:var(--space-2); align-items:flex-start; cursor:pointer; font-size:var(--font-size-sm); color:var(--muted); }
    input[type="checkbox"] { width:16px; height:16px; accent-color:var(--red); cursor:pointer; flex:none; margin-top:2px; }
  </style>
</head>
<body>

  <?= $partial('partials/nav-auth', ['links' => [
      ['class' => 'ghost-link',          'href' => '/login', 'text' => 'Iniciar sesión'],
      ['class' => 'btn btn-ghost btn-sm', 'href' => '/login', 'text' => 'Entrar'],
  ]]) ?>

  <main class="auth-page">
    <div class="auth-card">
      <div class="text-center" style="margin-bottom:var(--space-6)">
        <h1 style="font-size:var(--font-size-2xl);color:var(--ink)">Crear cuenta</h1>
        <p style="color:var(--muted);font-size:var(--font-size-sm);margin-top:4px">Completá tus datos para unirte a Tornalyx</p>
      </div>

      <div class="card">
        <div class="card__body" style="padding:28px">

          <!-- Datos personales -->
          <h2 style="font-size:var(--font-size-xl);margin-bottom:var(--space-5);color:var(--ink)">Datos personales</h2>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
            <div class="form-group">
              <label class="form-label" for="nombre">Nombre</label>
              <input class="form-control" type="text" id="nombre" name="nombre" placeholder="Carlos" required />
            </div>
            <div class="form-group">
              <label class="form-label" for="apellido">Apellido</label>
              <input class="form-control" type="text" id="apellido" name="apellido" placeholder="García" required />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="emailReg">Correo electrónico</label>
            <input class="form-control" type="email" id="emailReg" name="email" placeholder="tu@correo.com" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="passReg">Contraseña</label>
            <input class="form-control" type="password" id="passReg" name="password" placeholder="Mínimo 8 caracteres" required />
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <span class="strength-label" id="strengthLabel">Ingresa una contraseña</span>
            <ul class="pass-reqs" id="passReqs" aria-label="Requisitos de la contraseña">
              <li data-req="length"><span class="req-ico" aria-hidden="true"></span>Al menos 8 caracteres</li>
              <li data-req="upper"><span class="req-ico" aria-hidden="true"></span>Una letra mayúscula (A-Z)</li>
              <li data-req="lower"><span class="req-ico" aria-hidden="true"></span>Una letra minúscula (a-z)</li>
              <li data-req="number"><span class="req-ico" aria-hidden="true"></span>Un número (0-9)</li>
            </ul>
          </div>
          <div class="form-group">
            <label class="form-label" for="passConfirm">Confirmar contraseña</label>
            <input class="form-control" type="password" id="passConfirm" placeholder="Repite la contraseña" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="fechaNac">Fecha de nacimiento</label>
            <input class="form-control" type="date" id="fechaNac" name="fecha_nacimiento" required />
          </div>

          <hr style="border:none;border-top:1px solid var(--line);margin:var(--space-6) 0" />

          <!-- Rol -->
          <h2 style="font-size:var(--font-size-xl);margin-bottom:6px;color:var(--ink)">¿Cuál es tu rol?</h2>
          <p style="color:var(--muted);font-size:var(--font-size-sm);margin-bottom:var(--space-5)">Podrás cambiarlo más adelante desde tu perfil.</p>

          <div class="role-grid" role="radiogroup" aria-label="Seleccionar rol">
            <div class="role-card" data-role="participante" tabindex="0" role="radio" aria-checked="false">
              <div class="role-card__icon"><img src="../assets/icon-participante.svg" width="28" height="28" alt=""></div>
              <div class="role-card__title">Participante</div>
              <div class="role-card__desc">Inscríbete a torneos y compite</div>
            </div>
            <div class="role-card" data-role="organizador" tabindex="0" role="radio" aria-checked="false">
              <div class="role-card__icon"><img src="../assets/icon-organizador.svg" width="28" height="28" alt=""></div>
              <div class="role-card__title">Organizador</div>
              <div class="role-card__desc">Crea y gestiona torneos</div>
            </div>
          </div>
          <input type="hidden" id="rolSelected" name="rol" value="" />

          <div class="form-group" style="margin-top:var(--space-5)">
            <label class="check-label">
              <input type="checkbox" id="terminos" name="terminos" required />
              <span>Acepto los <a href="/terminos" target="_blank" rel="noopener" class="link-red">Términos de uso</a> y la <a href="/terminos" target="_blank" rel="noopener" class="link-red">Política de privacidad</a> de Tornalyx.</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-full" form="registroForm">Crear cuenta</button>

          <form id="registroForm" action="/registro" method="POST" style="display:none"></form>
        </div>
      </div>

      <p class="text-center" style="font-size:12px;color:var(--muted-2);margin-top:var(--space-4)">
        ¿Ya tienes cuenta? <a href="/login" class="link-red">Inicia sesión</a>
      </p>
    </div>
  </main>

  <script src="../js/main.js"></script>
  <script src="../js/validations.js"></script>
</body>
</html>


