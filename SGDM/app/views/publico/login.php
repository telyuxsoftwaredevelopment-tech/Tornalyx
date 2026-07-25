<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($title ?? 'Tornalyx | Iniciar sesión') ?></title>
  
  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="../assets/favicon.ico" />
  <link rel="stylesheet" href="../css/variables.css" />
  <link rel="stylesheet" href="../css/main.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <style>
    body { display:flex; flex-direction:column; min-height:100vh; }
    .auth-page {
      flex:1; display:flex; align-items:center; justify-content:center;
      padding:var(--space-8) var(--space-4);
    }
    .auth-card { width:100%; max-width:440px; }
    .auth-logo { text-align:center; margin-bottom:var(--space-6); }
    .auth-logo .logo-mark {
      width:80px; height:80px; border-radius:50%;
      box-shadow:0 0 0 2px var(--red-deep);
      margin:0 auto var(--space-4);
    }
    .auth-logo .logo-mark-fallback {
      width:80px; height:80px; border-radius:50%;
      background:var(--red);
      box-shadow:0 0 0 2px var(--red-deep);
      display:flex; align-items:center; justify-content:center;
      font-family:var(--head); font-weight:700; font-size:1.5rem; color:#fff;
      margin:0 auto var(--space-4);
    }
    .auth-logo h1 { font-family:var(--head); font-size:var(--font-size-2xl); color:var(--ink); }
    .auth-logo p  { font-size:var(--font-size-sm); color:var(--muted); margin-top:4px; }
    .divider {
      display:flex; align-items:center; gap:var(--space-3);
      margin:var(--space-5) 0;
      color:var(--muted-2); font-size:var(--font-size-sm);
    }
    .divider::before,.divider::after { content:''; flex:1; height:1px; background:var(--line); }
    .password-wrapper { position:relative; }
    .password-toggle {
      position:absolute; right:14px; top:50%; transform:translateY(-50%);
      background:none; border:none; cursor:pointer;
      color:var(--muted-2); font-size:1rem; padding:0;
      transition:color .15s;
    }
    .password-toggle:hover { color:var(--ink); }
    .alert {
      padding:var(--space-3) var(--space-4);
      border-radius:10px;
      font-size:var(--font-size-sm);
      margin-bottom:var(--space-4);
    }
    .alert-error {
      background:rgba(236,28,36,.1);
      color:var(--red-bright);
      border:1px solid var(--red-deep);
    }
    .link-red { color:var(--red-bright); }
    .link-red:hover { color:var(--red); }
    .check-label {
      display:flex; align-items:center; gap:var(--space-2);
      cursor:pointer; font-size:var(--font-size-sm); color:var(--muted);
    }
    input[type="checkbox"] { width:16px; height:16px; accent-color:var(--red); cursor:pointer; }
  </style>
</head>
<body>

  <?= $partial('partials/nav-auth', ['links' => []]) ?>

  <main class="auth-page">
    <div class="auth-card">
      <div class="auth-logo">
        <span class="logo-mark badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
        <h1>Iniciar sesión</h1>
        <p>Ingresa tus credenciales para acceder al sistema</p>
      </div>

      <div class="card" id="credCard">
        <div class="card__body" style="padding:28px">

          <div class="alert alert-error hidden" id="loginError" role="alert">
            Correo electrónico o contraseña incorrectos. Intentos restantes: <strong id="attemptsLeft">3</strong>.
          </div>

          <!-- El token CSRF se envía como header X-CSRF-Token (cookie XSRF-TOKEN)
               desde validations.js → postForm(). Este campo oculto es defensa en
               profundidad: si el JS no corre y el form se envía nativamente,
               Session::verifyCsrf() también acepta el token por POST. -->
          <form id="loginForm" action="/login" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrf ?? '') ?>" />
            <div class="form-group">
              <label class="form-label" for="email">Correo electrónico</label>
              <input class="form-control" type="email" id="email" name="email"
                     placeholder="tu@correo.com" autocomplete="email" required aria-required="true" />
              <span class="form-error hidden" id="emailError">Ingresa un correo válido.</span>
            </div>

            <div class="form-group">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-2)">
                <label class="form-label" for="password" style="margin:0">Contraseña</label>
                <a href="#" class="link-red" style="font-size:12px">¿Olvidaste tu contraseña?</a>
              </div>
              <div class="password-wrapper">
                <input class="form-control" type="password" id="password" name="password"
                       placeholder="••••••••" autocomplete="current-password" required
                       aria-required="true" style="padding-right:44px" />
                <button type="button" class="password-toggle" id="togglePassword" aria-label="Mostrar contraseña">👁</button>
              </div>
              <span class="form-error hidden" id="passwordError">La contraseña es obligatoria.</span>
            </div>

            <div class="form-group">
              <label class="check-label">
                <input type="checkbox" name="remember" />
                Recordar mi sesión
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn-full" id="loginBtn">
              Iniciar sesión
            </button>
          </form>

          <div class="divider">o</div>

          <p class="text-center" style="font-size:var(--font-size-sm);color:var(--muted)">
            ¿No tienes cuenta?
            <a href="/registro" class="link-red" style="font-weight:600">Regístrate gratis</a>
          </p>
        </div>
      </div>

      <!-- Paso 2: ingresar el código recibido. Oculto hasta que se envíe. -->
      <div class="card hidden" id="otpCard">
        <div class="card__body" style="padding:28px">

          <div class="alert alert-error hidden" id="otpError" role="alert"></div>

          <p style="font-size:var(--font-size-sm);color:var(--muted);margin-bottom:var(--space-4)">
            Te enviamos un código de verificación de 6 dígitos a
            <strong id="otpTarget" style="color:var(--ink)">tu destino</strong>.
            Ingrésalo para continuar.
          </p>

          <form id="otpForm" novalidate>
            <div class="form-group">
              <label class="form-label" for="codigo">Código de verificación</label>
              <input class="form-control" type="text" id="codigo" name="codigo"
                     inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                     pattern="[0-9]{6}" placeholder="000000" required aria-required="true"
                     style="letter-spacing:.4em;text-align:center;font-size:1.25rem" />
            </div>

            <button type="submit" class="btn btn-primary btn-full" id="otpBtn">
              Verificar
            </button>
          </form>

          <div class="divider">o</div>

          <p class="text-center" style="font-size:var(--font-size-sm);color:var(--muted)">
            ¿No recibiste el código?
            <a href="#" class="link-red" id="resendBtn" style="font-weight:600">Reenviar</a>
            <span id="resendCooldown" style="color:var(--muted-2)"></span>
          </p>
          <p class="text-center" style="margin-top:var(--space-3)">
            <a href="#" class="link-red" id="backToLogin" style="font-size:12px">Volver al inicio de sesión</a>
          </p>
        </div>
      </div>

      <p class="text-center" style="font-size:12px;color:var(--muted-2);margin-top:var(--space-4)">
        Al iniciar sesión aceptás nuestros <a href="/terminos" class="link-red">Términos de uso</a> y <a href="/terminos" class="link-red">Política de privacidad</a>.
      </p>
    </div>
  </main>

  <script src="../js/main.js"></script>
  <script src="../js/validations.js"></script>
</body>
</html>


