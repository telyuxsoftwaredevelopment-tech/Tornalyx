<?php
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/../shared/Session.php';

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

    /**
     * Autorización para endpoints JSON.
     *
     * Session::requireRole() redirige a /login, lo que sirve para vistas pero
     * rompe a un cliente fetch (recibiría el HTML del login y fallaría al
     * parsear JSON). Acá se responde con 401/403 y un cuerpo JSON, para que el
     * front pueda avisar al usuario y mandarlo al login él mismo.
     *
     * @param string[] $roles
     * @return bool true si la petición puede continuar.
     */
    protected function requireApiRole(array $roles): bool {
        if (!Session::isLoggedIn()) {
            $this->jsonError('Tu sesión expiró. Iniciá sesión de nuevo.', ['login' => true], 401);
            return false;
        }
        if (!in_array(Session::getUserRole(), $roles, true)) {
            $this->jsonError('No tenés permisos para realizar esta acción.', [], 403);
            return false;
        }
        return true;
    }

    /**
     * Exige sesión activa, sin restricción de rol.
     *
     * Los roles de torneo (organizador/participante) ya no son un rol de
     * cuenta: se derivan de torneos.organizador_id y de inscripciones. Los
     * endpoints que dependen de esa pertenencia validan la propiedad ellos
     * mismos (p. ej. puedeGestionar()); acá solo se exige estar logueado.
     */
    protected function requireApiLogin(): bool {
        if (!Session::isLoggedIn()) {
            $this->jsonError('Tu sesión expiró. Iniciá sesión de nuevo.', ['login' => true], 401);
            return false;
        }
        return true;
    }

    /**
     * Valida una fecha de nacimiento: formato YYYY-MM-DD, existente, no
     * futura y con año dentro de un rango humano razonable. `Y` en
     * DateTime::createFromFormat acepta años de más de 4 dígitos (p. ej.
     * "20001-07-13" por un typo pasa el formato igual), y sin el piso de
     * 1900 el INSERT/UPDATE explota contra la columna DATE de la base.
     * Usado por el registro público y el alta/edición de usuarios del admin.
     */
    protected function esFechaNacValida(string $fecha): bool {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($d === false || $d->format('Y-m-d') !== $fecha) {
            return false;
        }
        $anio = (int) $d->format('Y');
        return $anio >= 1900 && $d <= new DateTimeImmutable('today');
    }

    /**
     * Contraseñas rechazadas por frecuentes. Se comparan en minúsculas y
     * también contra la contraseña reducida a solo letras, para que los
     * disfraces mínimos ("Password1!", "Futbol2024!") caigan igual.
     *
     * No pretende ser exhaustiva —para eso haría falta una lista tipo
     * Have I Been Pwned— pero corta las que aparecen primero en cualquier
     * diccionario de fuerza bruta, incluidas las locales.
     */
    private const PASSWORDS_COMUNES = [
        '123456', '1234567', '12345678', '123456789', '1234567890',
        'password', 'passw0rd', 'contrasena', 'contraseña', 'qwerty',
        'qwertyuiop', 'abc123', 'admin', 'administrador', 'usuario',
        'bienvenido', 'welcome', 'letmein', 'iloveyou', 'monkey', 'dragon',
        'secreto', 'cambiame', 'hola', 'holamundo', 'football', 'futbol',
        'master', 'sunshine', 'princesa', 'tornalyx', 'uruguay',
        'montevideo', 'peñarol', 'nacional',
    ];

    /**
     * Política de contraseña del proyecto, en un solo lugar: la usan el
     * registro público (AuthController), el alta/edición desde el panel
     * (AdminController) y el cambio de contraseña del perfil
     * (PerfilController).
     *
     * Se valida SIEMPRE en el servidor: el checklist en vivo de
     * frontend/js/validations.js es solo ayuda visual y se puede evadir
     * desactivando JavaScript o llamando al endpoint directamente.
     *
     * No se aplica al login: las cuentas creadas bajo una política anterior
     * siguen entrando con su contraseña, y solo se les exige la nueva regla
     * cuando la cambian.
     */
    protected function passwordEsFuerte(string $password): bool {
        if (strlen($password) < 8
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/[0-9]/', $password)
            || !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        $normalizada = mb_strtolower($password, 'UTF-8');
        if (in_array($normalizada, self::PASSWORDS_COMUNES, true)) {
            return false;
        }
        // "Futbol2024!" -> "futbol": la palabra base tampoco puede ser común.
        $soloLetras = (string) preg_replace('/[^\p{L}]/u', '', $normalizada);
        return mb_strlen($soloLetras) < 4
            || !in_array($soloLetras, self::PASSWORDS_COMUNES, true);
    }

    /**
     * Mensaje único para cuando passwordEsFuerte() falla, para que los tres
     * formularios digan exactamente lo mismo que valida el servidor.
     */
    protected function mensajePasswordDebil(): string {
        return 'La contraseña debe tener al menos 8 caracteres e incluir '
             . 'mayúsculas, minúsculas, números y un símbolo, y no puede ser '
             . 'una contraseña común.';
    }
}
