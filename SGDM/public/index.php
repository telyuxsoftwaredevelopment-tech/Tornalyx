<?php
/**
 * Punto de entrada del backend Tornalyx (Front Controller).
 * Todas las peticiones llegan aquí via .htaccess y se enrutan.
 */

declare(strict_types=1);

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
// Router simple
// ──────────────────────────────────────────────────────────────
$auth   = new AuthController();
$torneo = new TorneoController();

match(true) {

    // AUTH
    $uri === '/login'    && $method === 'GET'  => $auth->showLogin(),
    $uri === '/login'    && $method === 'POST' => $auth->processLogin(),
    $uri === '/registro' && $method === 'POST' => $auth->processRegistro(),
    $uri === '/logout'   && $method === 'POST' => $auth->logout(),

    // TORNEOS (API JSON)
    $uri === '/api/torneos'          && $method === 'GET'  => $torneo->index(),
    $uri === '/api/torneo/crear'     && $method === 'POST' => $torneo->store(),
    $uri === '/api/torneo/resultado' && $method === 'POST' => $torneo->cargarResultado(),

    // Torneo por ID: /api/torneo/42
    preg_match('#^/api/torneo/(\d+)$#', $uri, $m) && $method === 'GET'
        => $torneo->show((int) $m[1]),

    // 404 por defecto
    default => (function() {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Ruta no encontrada.']);
    })(),
};
