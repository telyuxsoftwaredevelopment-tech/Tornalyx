<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../shared/Session.php';

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

        $usuario = $this->usuarioModel->verificarCredenciales($email, $password);

        if (!$usuario) {
            http_response_code(401);
            $this->jsonError('Correo o contraseña incorrectos.');
            return;
        }

        Session::login($usuario);
        $this->jsonSuccess(['rol' => $usuario['rol']]);
    }

    /**
     * Procesa el formulario de registro (POST /registro).
     */
    public function processRegistro(): void {
        Session::start();

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
        if (strlen($password) < 8) {
            $this->jsonError('La contraseña debe tener al menos 8 caracteres.');
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
        echo json_encode(['success' => true, ...$data]);
    }

    private function jsonError(string $mensaje): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $mensaje]);
    }
}
