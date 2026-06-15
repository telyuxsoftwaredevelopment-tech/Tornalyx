<?php
/**
 * Gestión de sesiones PHP para Tornalyx.
 * Inicia la sesión, almacena usuario autenticado y provee helpers.
 */
class Session {

    /**
     * Inicia la sesión con configuración segura.
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => false,    // Cambiar a true en producción con HTTPS
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    /**
     * Almacena los datos del usuario en la sesión.
     *
     * @param array $usuario Fila de la tabla usuarios.
     */
    public static function login(array $usuario): void {
        session_regenerate_id(true);
        $_SESSION['usuario_id']  = $usuario['id'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
    }

    /**
     * Destruye la sesión activa (logout).
     */
    public static function logout(): void {
        session_unset();
        session_destroy();
    }

    /**
     * Verifica si hay un usuario autenticado.
     *
     * @return bool
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Retorna el ID del usuario autenticado.
     *
     * @return int|null
     */
    public static function getUserId(): ?int {
        return $_SESSION['usuario_id'] ?? null;
    }

    /**
     * Retorna el rol del usuario autenticado.
     *
     * @return string|null
     */
    public static function getUserRole(): ?string {
        return $_SESSION['usuario_rol'] ?? null;
    }

    /**
     * Verifica que el usuario tenga el rol requerido; redirige si no.
     *
     * @param string|array $rolesPermitidos
     * @param string       $redirect URL de redirección si no tiene permiso.
     */
    public static function requireRole(string|array $rolesPermitidos, string $redirect = '/login'): void {
        self::start();
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirect);
            exit;
        }
        $roles = (array) $rolesPermitidos;
        if (!in_array(self::getUserRole(), $roles, true)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
    }
}
