<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../shared/Session.php';
require_once __DIR__ . '/../shared/LoginThrottle.php';

/**
 * Controlador de autenticación.
 * Maneja login, registro y logout.
 */
class AuthController {

    private Usuario $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /**
     * Muestra el formulario de login (GET /login).
     */
    public function showLogin(): void {
        if (Session::isLoggedIn()) {
            $this->redirectByRole();
        }
        include __DIR__ . '/../views/publico/login.html';
    }

    /**
     * Procesa el formulario de login (POST /login).
     */
    public function processLogin(): void {
        Session::start();

        $email    = filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL) ?? '';
        $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT)        ?? '';

        if (empty($email) || empty($password)) {
            $this->jsonError('Campos requeridos.');
            return;
        }

        // Protección contra fuerza bruta: bloquear tras varios fallos.
        if (LoginThrottle::isBlocked($email)) {
            http_response_code(429);
            $this->jsonError('Demasiados intentos fallidos. Esperá unos minutos e intentá de nuevo.');
            return;
        }

        $usuario = $this->usuarioModel->verificarCredenciales($email, $password);

        if (!$usuario) {
            LoginThrottle::registerFailure($email);
            http_response_code(401);
            $this->jsonError('Correo o contraseña incorrectos.', [
                'intentos_restantes' => LoginThrottle::remaining($email),
            ]);
            return;
        }

        LoginThrottle::clear($email);
        Session::login($usuario);
        $this->jsonSuccess(['rol' => $usuario['rol']]);
    }

    /**
     * Procesa el formulario de registro (POST /registro).
     */
    public function processRegistro(): void {
        Session::start();

        // Anti-enumeración: limitar la cantidad de altas por IP. No cambia el
        // mensaje informativo de "correo ya registrado" (buena UX), pero frena
        // los barridos automatizados que prueban muchos correos.
        if (LoginThrottle::isRegistrationBlocked()) {
            http_response_code(429);
            $this->jsonError('Demasiados intentos de registro. Esperá unos minutos e intentá de nuevo.');
            return;
        }
        LoginThrottle::registerSignupAttempt();

        $nombre    = trim(filter_input(INPUT_POST, 'nombre',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $apellido  = trim(filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $email     = filter_input(INPUT_POST, 'email',         FILTER_SANITIZE_EMAIL)         ?? '';
        $password  = filter_input(INPUT_POST, 'password',      FILTER_DEFAULT)                ?? '';
        $fechaNac  = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_DEFAULT)             ?? '';
        $rol       = filter_input(INPUT_POST, 'rol', FILTER_DEFAULT)                          ?? 'participante';

        // Validaciones básicas en backend
        if (empty($nombre) || empty($email) || empty($password) || empty($fechaNac)) {
            $this->jsonError('Todos los campos son obligatorios.');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Correo electrónico inválido.');
            return;
        }
        if (!$this->passwordEsFuerte($password)) {
            $this->jsonError('La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas y números.');
            return;
        }
        if (!in_array($rol, ['participante', 'organizador'], true)) {
            $rol = 'participante';
        }

        // Verificar email duplicado
        if ($this->usuarioModel->findByEmail($email)) {
            $this->jsonError('El correo ya está registrado.');
            return;
        }

        $id = $this->usuarioModel->registrar($nombre, $apellido, $email, $password, $fechaNac, $rol);
        $usuario = $this->usuarioModel->findById($id);
        Session::login($usuario);

        $this->jsonSuccess(['rol' => $rol]);
    }

    /**
     * Cierra la sesión del usuario (POST /logout).
     */
    public function logout(): void {
        Session::start();
        Session::logout();
        header('Location: /');
        exit;
    }

    // ──────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────

    /**
     * Valida la robustez de la contraseña en el servidor (no confiar en el
     * medidor del cliente, que es solo visual y se puede evadir).
     */
    private function passwordEsFuerte(string $password): bool {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }

    private function redirectByRole(): void {
        $rol = Session::getUserRole();
        match($rol) {
            'administrador' => header('Location: /admin/dashboard'),
            'organizador'   => header('Location: /organizador/dashboard'),
            default         => header('Location: /perfil'),
        };
        exit;
    }

    private function jsonSuccess(array $data = []): void {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
    }

    private function jsonError(string $mensaje, array $extra = []): void {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => false, 'error' => $mensaje], $extra));
    }
}
