<?php
/**
 * Pie de página público, compartido por las vistas PHP (documentacion.php y
 * doc-aprobacion.php). Antes estaba copiado íntegro en las dos, 56 líneas en
 * cada una: cambiar un enlace obligaba a acordarse de tocar ambas.
 *
 * Se incluye con el helper de View: <?= $partial('footer-publico') ?>
 */
?>
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
          <a href="/torneos">Torneos</a>
          <a href="/jugadores">Jugadores</a>
          <a href="/documentacion">Documentación</a>
        </div>
        <div class="foot-col">
          <h5>Formatos</h5>
          <a href="/torneos">Liga</a>
          <a href="/torneos">Eliminación directa</a>
          <a href="/torneos">Sistema suizo</a>
        </div>
        <div class="foot-col">
          <h5>Conéctate</h5>
          <div class="foot-socials">
            <a href="https://x.com/Tornalyx" target="_blank" rel="noopener noreferrer" class="foot-social-link" aria-label="X (Twitter)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
            </a>
            <a href="https://youtube.com/@Tornalyx" target="_blank" rel="noopener noreferrer" class="foot-social-link" aria-label="YouTube">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
            </a>
            <a href="https://instagram.com/telyuxsoftwaredevelopment" target="_blank" rel="noopener noreferrer" class="foot-social-link" aria-label="Instagram">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
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
          <a href="/terminos">Términos y Condiciones</a>
          <span class="foot-sep" aria-hidden="true">·</span>
          <span>Hecho con ♡</span>
        </div>
      </div>
    </div>
  </footer>
