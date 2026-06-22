<?php
/**
 * Punto de entrada del backend Tornalyx (Front Controller).
 * Todas las peticiones llegan aquí via .htaccess y se enrutan.
 */

declare(strict_types=1);

// ──────────────────────────────────────────────────────────────
// Manejador global de errores: evita filtrar stack traces y rutas
// del servidor al cliente (information disclosure). Los detalles se
// registran en el log; al cliente solo le llega un mensaje genérico.
// ──────────────────────────────────────────────────────────────
set_exception_handler(static function (Throwable $e): void {
    error_log('Unhandled exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'error' => 'Ocurrió un error en el servidor. Intentá más tarde.']);
});

require_once __DIR__ . '/../app/shared/Session.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/TorneoController.php';

Session::start();

// Obtener la ruta y el método HTTP
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Eliminar trailing slash
$uri = rtrim($uri, '/') ?: '/';

// ──────────────────────────────────────────────────────────────
// Protección CSRF: toda petición que cambia estado (POST) debe
// presentar un token válido. El logout queda exento porque es
// idempotente y accesible también por GET.
// ──────────────────────────────────────────────────────────────
if ($method === 'POST' && $uri !== '/logout') {
    Session::requireCsrf();
}

// ──────────────────────────────────────────────────────────────
// Router simple
// ──────────────────────────────────────────────────────────────
// Helper: renderiza una vista estática (sin necesidad de BD).
$view = static function (string $path): void {
    include __DIR__ . '/../app/views/' . $path;
};

// Los controladores se instancian de forma perezosa: solo las rutas
// que realmente acceden a datos abren conexión a la BD. Así las páginas
// públicas (home, registro, listado) se sirven aunque la BD esté caída.
match(true) {

    // ─── Páginas públicas (HTML) ───────────────────────────────
    $uri === '/'               && $method === 'GET' => $view('publico/index.html'),
    $uri === '/registro'       && $method === 'GET' => $view('publico/registro.html'),
    $uri === '/torneos'        && $method === 'GET' => $view('publico/torneos.html'),
    $uri === '/torneo-detalle' && $method === 'GET' => $view('publico/torneo-detalle.html'),
    $uri === '/terminos'       && $method === 'GET' => $view('publico/terminos.html'),

    // ─── AUTH ──────────────────────────────────────────────────
    $uri === '/login'           && $method === 'GET'  => (new AuthController())->showLogin(),
    $uri === '/login'           && $method === 'POST' => (new AuthController())->processLogin(),
    $uri === '/login/codigo'    && $method === 'POST' => (new AuthController())->enviarCodigo2fa(),
    $uri === '/login/verificar' && $method === 'POST' => (new AuthController())->verify2fa(),
    $uri === '/login/reenviar'  && $method === 'POST' => (new AuthController())->resend2fa(),
    $uri === '/api/2fa/estado'  && $method === 'GET'  => (new AuthController())->estado2fa(),
    $uri === '/registro'        && $method === 'POST' => (new AuthController())->processRegistro(),
    $uri === '/logout'   && in_array($method, ['GET', 'POST'], true) => (new AuthController())->logout(),
    $uri === '/api/me'   && $method === 'GET' => (new AuthController())->me(),

    // ─── Paneles privados (HTML, requieren sesión) ─────────────
    $uri === '/perfil' && $method === 'GET'
        => (static function () use ($view) {
            // Cualquier usuario autenticado puede ver su perfil.
            Session::requireRole(['participante', 'organizador', 'administrador']);
            $view('participante/perfil.html');
        })(),
    $uri === '/organizador/dashboard' && $method === 'GET'
        => (static function () use ($view) {
            Session::requireRole(['organizador', 'administrador']);
            $view('organizador/dashboard.html');
        })(),
    $uri === '/admin/dashboard' && $method === 'GET'
        => (static function () use ($view) {
            Session::requireRole('administrador');
            $view('admin/dashboard.html');
        })(),

    // ─── TORNEOS (API JSON) ────────────────────────────────────
    $uri === '/api/torneos'          && $method === 'GET'  => (new TorneoController())->index(),
    $uri === '/api/torneo/crear'     && $method === 'POST' => (new TorneoController())->store(),
    $uri === '/api/torneo/resultado' && $method === 'POST' => (new TorneoController())->cargarResultado(),

    // Torneo por ID: /api/torneo/42
    preg_match('#^/api/torneo/(\d+)$#', $uri, $m) && $method === 'GET'
        => (new TorneoController())->show((int) $m[1]),

    // ─── 404 por defecto ───────────────────────────────────────
    default => (function() {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Ruta no encontrada.']);
    })(),
};
