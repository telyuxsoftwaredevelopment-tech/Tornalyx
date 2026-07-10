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
      <img class="badge" src="../assets/ICONO.png" alt="Tornalyx" width="32" height="32" data-fallback="hide">
      Tornalyx
    </a>
    <div class="nav-right">
      <?php foreach (($links ?? []) as $lnk): ?>
        <a class="<?= e($lnk['class'] ?? '') ?>" href="<?= e($lnk['href'] ?? '#') ?>"><?= e($lnk['text'] ?? '') ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</header>
