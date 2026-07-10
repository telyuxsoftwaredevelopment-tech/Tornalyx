<?php
require_once __DIR__ . '/View.php';

/**
 * Controlador base — capa C (Controlador) del patrón MVC.
 *
 * Centraliza lo que antes estaba duplicado en cada controlador concreto:
 *  - el acceso a la capa de Vista ($this->view / $this->render),
 *  - las respuestas JSON estandarizadas para los endpoints del API.
 *
 * Todos los controladores de la aplicación deben extender esta clase.
 */
abstract class Controller {

    /** Renderizador de vistas (inyección de datos del controlador a la plantilla). */
    protected View $view;

    public function __construct() {
        $this->view = new View();
    }

    /**
     * Renderiza una vista pasándole datos (Controlador → Vista).
     *
     * @param string $view Ruta lógica de la plantilla, p. ej. 'publico/login'.
     * @param array  $data Datos disponibles dentro de la plantilla.
     */
    protected function render(string $view, array $data = []): void {
        $this->view->render($view, $data);
    }

    /**
     * Respuesta JSON genérica.
     *
     * El código de estado solo se fija si se pasa explícitamente; así los
     * llamadores que ya hicieron http_response_code(...) antes de responder
     * (p. ej. un 401 o 404) conservan ese código.
     *
     * @param array    $data
     * @param int|null $status
     */
    protected function json(array $data, ?int $status = null): void {
        if (!headers_sent()) {
            if ($status !== null) {
                http_response_code($status);
            }
            header('Content-Type: application/json');
        }
        echo json_encode($data);
    }

    /**
     * Respuesta JSON de éxito: { "success": true, ...$data }.
     */
    protected function jsonSuccess(array $data = []): void {
        $this->json(array_merge(['success' => true], $data));
    }

    /**
     * Respuesta JSON de error: { "success": false, "error": ..., ...$extra }.
     *
     * @param string   $mensaje
     * @param array    $extra   Datos adicionales (p. ej. intentos_restantes).
     * @param int|null $status  Código HTTP opcional.
     */
    protected function jsonError(string $mensaje, array $extra = [], ?int $status = null): void {
        $this->json(array_merge(['success' => false, 'error' => $mensaje], $extra), $status);
    }
}
