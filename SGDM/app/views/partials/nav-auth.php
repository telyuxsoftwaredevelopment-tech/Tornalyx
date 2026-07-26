<?php
/**
 * Parcial: navbar mínima de las páginas de autenticación (login, registro,
 * términos). Parametrizado por $links, que el controlador/vista le pasa:
 *   $partial('partials/nav-auth', ['links' => [
 *       ['class' => 'ghost-link', 'href' => '/login', 'text' => 'Iniciar sesión'],
 *       ...
 *   ]]);
 */
?>
<header class="nav">
  <div class="wrap nav-in">
    <a class="brand" href="/">
      <span class="badge badge-crop" style="background-image:url(../assets/ICONO.png)" role="img" aria-label="Tornalyx"></span>
      Tornalyx
    </a>
    <div class="nav-right nav-right-static">
      <?php foreach (($links ?? []) as $lnk): ?>
        <a class="<?= e($lnk['class'] ?? '') ?>" href="<?= e($lnk['href'] ?? '#') ?>"><?= e($lnk['text'] ?? '') ?></a>
      <?php endforeach; ?>
      <label class="theme-toggle" aria-label="Cambiar tema claro/oscuro">
        <input type="checkbox" class="theme-toggle__input" />
        <span class="theme-toggle__track">
          <span class="theme-toggle__thumb">
            <span class="theme-toggle__icon theme-toggle__icon--sun">☀</span>
            <span class="theme-toggle__icon theme-toggle__icon--moon">☾</span>
          </span>
        </span>
      </label>
    </div>
  </div>
</header>
