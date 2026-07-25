<?php
/**
 * Punto de entrada del backend Tornalyx (Front Controller del patrón MVC).
 * Todas las peticiones llegan aquí via .htaccess, se enrutan con Router y se
 * resuelven contra un Controlador (capa C) o una Vista (capa V).
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

require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/View.php';
require_once __DIR__ . '/../app/shared/Session.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/TorneoController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/DocsController.php';

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
// Tabla de rutas (Front Controller → Router)
// ──────────────────────────────────────────────────────────────
// Los controladores se instancian dentro de los closures (de forma perezosa):
// solo las rutas que realmente acceden a datos abren conexión a la BD. Así las
// páginas públicas (home, registro, listado) se sirven aunque la BD esté caída.
$router = new Router();
$view   = new View();

// ─── Páginas públicas (vistas renderizadas en servidor) ────────
$router->get('/',               static fn() => $view->render('publico/index',          ['title' => 'Tornalyx · La plataforma que organiza todo']));
$router->get('/registro',       static fn() => $view->render('publico/registro',       ['title' => 'Tornalyx | Crear cuenta']));
$router->get('/torneos',        static fn() => $view->render('publico/torneos',        ['title' => 'Tornalyx | Buscar Torneos']));
$router->get('/torneo-detalle', static fn() => $view->render('publico/torneo-detalle', ['title' => 'Tornalyx | Copa Regional Fútbol 2026']));
$router->get('/terminos',       static fn() => $view->render('publico/terminos',       ['title' => 'Tornalyx | Términos y Condiciones']));
$router->get('/documentacion',  static fn() => (new DocsController())->show());
$router->post('/documentacion/solicitar', static fn() => (new DocsController())->solicitarAcceso());
$router->post('/documentacion/codigo',    static fn() => (new DocsController())->enviarCodigo());
$router->post('/documentacion/verificar', static fn() => (new DocsController())->verificarCodigo());
$router->get('/documentacion/aprobar',    static fn() => (new DocsController())->revisarSolicitud());
$router->post('/documentacion/resolver',  static fn() => (new DocsController())->resolverSolicitud());

// ─── AUTH ──────────────────────────────────────────────────────
$router->get('/login',            static fn() => (new AuthController())->showLogin());
$router->post('/login',           static fn() => (new AuthController())->processLogin());
$router->post('/login/verificar', static fn() => (new AuthController())->verify2fa());
$router->post('/login/reenviar',  static fn() => (new AuthController())->resend2fa());
$router->get('/api/2fa/estado',   static fn() => (new AuthController())->estado2fa());
$router->post('/registro',        static fn() => (new AuthController())->processRegistro());
$router->map(['GET', 'POST'], '/logout', static fn() => (new AuthController())->logout());
$router->get('/api/me',           static fn() => (new AuthController())->me());

// ─── Paneles privados (vistas que requieren sesión) ────────────
$router->get('/perfil', static function () use ($view) {
    // Cualquier usuario autenticado puede ver su perfil.
    Session::requireRole(['participante', 'organizador', 'administrador']);
    $view->render('participante/perfil', ['title' => 'Tornalyx | Mi Perfil']);
});
$router->get('/organizador/dashboard', static function () use ($view) {
    Session::requireRole(['organizador', 'administrador']);
    $view->render('organizador/dashboard', ['title' => 'Tornalyx | Panel Organizador']);
});
$router->get('/admin/dashboard', static function () use ($view) {
    Session::requireRole('administrador');
    $view->render('admin/dashboard', ['title' => 'Tornalyx | Panel de Administración']);
});

// ─── TORNEOS (API JSON) ────────────────────────────────────────
$router->get('/api/torneos',           static fn() => (new TorneoController())->index());
$router->get('/api/torneos/mios',      static fn() => (new TorneoController())->mios());
$router->post('/api/torneo/crear',     static fn() => (new TorneoController())->store());
$router->post('/api/torneo/resultado', static fn() => (new TorneoController())->cargarResultado());

// Torneo por ID: /api/torneo/42 (la restricción \d+ evita que "abc" matchee).
$router->get('#^/api/torneo/(\d+)$#', static fn($id) => (new TorneoController())->show((int) $id));

// ─── ADMIN (API JSON) ──────────────────────────────────────────
$router->get('/api/admin/stats',    static fn() => (new AdminController())->stats());
$router->get('/api/admin/usuarios', static fn() => (new AdminController())->usuarios());
$router->post('/api/admin/usuario/crear',      static fn() => (new AdminController())->crearUsuario());
$router->post('/api/admin/usuario/actualizar', static fn() => (new AdminController())->actualizarUsuario());
$router->get('/api/admin/doc-solicitudes',         static fn() => (new AdminController())->docSolicitudes());
$router->post('/api/admin/doc-solicitud/resolver', static fn() => (new AdminController())->resolverDocSolicitud());

// ─── 404 por defecto ───────────────────────────────────────────
$router->fallback(static function (): void {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ruta no encontrada.']);
});

$router->dispatch($uri, $method);
