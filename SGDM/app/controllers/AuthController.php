<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/OtpCode.php';
require_once __DIR__ . '/../shared/Session.php';
require_once __DIR__ . '/../shared/LoginThrottle.php';
require_once __DIR__ . '/../shared/Mailer.php';

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
        Session::start();
        if (Session::isLoggedIn()) {
            $this->redirectByRole();
            return;
        }
        // Si hay un desafío 2FA pendiente (p. ej. tras registrarse o recargar),
        // avisamos al frontend para que muestre directamente el paso del código.
        $pending = $_SESSION['pending_2fa'] ?? null;
        if (is_array($pending) && time() <= ($pending['expires'] ?? 0)) {
            $masked = json_encode($pending['email_masked'] ?? '', JSON_UNESCAPED_UNICODE);
            echo "<script>window.__2FA_PENDING__=true;window.__2FA_EMAIL__={$masked};</script>\n";
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

        // Credenciales correctas: en vez de iniciar sesión, lanzamos el segundo
        // factor (código por email). La sesión NO queda autenticada hasta que
        // se verifique el código en verify2fa().
        $this->iniciarDesafio2FA($usuario);
    }

    /**
     * Verifica el código de doble factor (POST /login/verificar).
     */
    public function verify2fa(): void {
        Session::start();

        $pending = $_SESSION['pending_2fa'] ?? null;
        if (!is_array($pending) || empty($pending['user_id'])) {
            http_response_code(440);
            $this->jsonError('Tu sesión de verificación expiró. Iniciá sesión de nuevo.');
            return;
        }
        if (time() > ($pending['expires'] ?? 0)) {
            unset($_SESSION['pending_2fa']);
            (new OtpCode())->invalidar((int) $pending['user_id']);
            http_response_code(440);
            $this->jsonError('Tu sesión de verificación expiró. Iniciá sesión de nuevo.');
            return;
        }

        $codigo = preg_replace('/\D/', '', (string) (filter_input(INPUT_POST, 'codigo', FILTER_DEFAULT) ?? ''));
        if (strlen($codigo) !== 6) {
            $this->jsonError('Ingresá el código de 6 dígitos.');
            return;
        }

        $userId = (int) $pending['user_id'];
        $otp    = new OtpCode();
        $error  = null;

        if (!$otp->verificar($userId, $codigo, $error)) {
            http_response_code(401);
            $this->jsonError($error ?? 'Código incorrecto.');
            return;
        }

        // Código válido: ahora sí iniciamos la sesión autenticada.
        $usuario = $this->usuarioModel->findById($userId);
        unset($_SESSION['pending_2fa']);
        if (!$usuario) {
            $this->jsonError('No se pudo completar el inicio de sesión.');
            return;
        }
        Session::login($usuario);
        $this->jsonSuccess(['rol' => $usuario['rol']]);
    }

    /**
     * Reenvía el código de doble factor (POST /login/reenviar).
     */
    public function resend2fa(): void {
        Session::start();

        $pending = $_SESSION['pending_2fa'] ?? null;
        if (!is_array($pending) || empty($pending['user_id'])) {
            http_response_code(440);
            $this->jsonError('Tu sesión de verificación expiró. Iniciá sesión de nuevo.');
            return;
        }

        $userId = (int) $pending['user_id'];
        $otp    = new OtpCode();

        $cooldown = $otp->cooldownRestante($userId);
        if ($cooldown > 0) {
            http_response_code(429);
            $this->jsonError("Esperá {$cooldown} segundos para reenviar el código.");
            return;
        }

        $usuario = $this->usuarioModel->findById($userId);
        if (!$usuario) {
            unset($_SESSION['pending_2fa']);
            $this->jsonError('No se pudo reenviar el código. Iniciá sesión de nuevo.');
            return;
        }

        $codigo = $otp->generar($userId);
        try {
            $this->enviarCodigo($usuario, $codigo);
        } catch (Throwable $e) {
            error_log('Fallo al reenviar OTP: ' . $e->getMessage());
            http_response_code(500);
            $this->jsonError('No pudimos reenviar el código. Intentá más tarde.');
            return;
        }

        $_SESSION['pending_2fa']['expires'] = time() + 600;
        $this->jsonSuccess(['email_masked' => $this->maskEmail($usuario['email'])]);
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

        // 2FA obligatorio para todos: tras crear la cuenta también exigimos el
        // código por email antes de dejar la sesión iniciada.
        $this->iniciarDesafio2FA($usuario);
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
     * Genera y envía un código 2FA, deja el desafío "pendiente" en la sesión
     * (que NO queda autenticada) y responde al cliente indicando que debe
     * pedir el código. Si el envío del correo falla, aborta sin dejar estado.
     */
    private function iniciarDesafio2FA(array $usuario): void {
        $userId = (int) $usuario['id'];
        $otp    = new OtpCode();
        $codigo = $otp->generar($userId);

        try {
            $this->enviarCodigo($usuario, $codigo);
        } catch (Throwable $e) {
            error_log('Fallo al enviar OTP: ' . $e->getMessage());
            $otp->invalidar($userId);
            http_response_code(500);
            $this->jsonError('No pudimos enviar el código de verificación. Intentá más tarde.');
            return;
        }

        // Renovamos el id de sesión al establecer el desafío para cerrar la
        // ventana de fijación de sesión. Los datos de $_SESSION se conservan.
        session_regenerate_id(true);

        $masked = $this->maskEmail($usuario['email']);
        $_SESSION['pending_2fa'] = [
            'user_id'      => $userId,
            'expires'      => time() + 600,
            'email_masked' => $masked,
        ];

        $this->jsonSuccess([
            'twofa'        => true,
            'email_masked' => $masked,
        ]);
    }

    /**
     * Envía el correo con el código de verificación (texto + HTML).
     */
    private function enviarCodigo(array $usuario, string $codigo): void {
        $nombre  = (string) ($usuario['nombre'] ?? '');
        $subject = 'Tu código de verificación de Tornalyx';

        $text = "Hola {$nombre},\r\n\r\n"
              . "Tu código de verificación es: {$codigo}\r\n\r\n"
              . "Vence en 10 minutos. Si no intentaste iniciar sesión, ignorá este correo.\r\n\r\n"
              . 'Tornalyx';

        $html = $this->plantillaCodigo($nombre, $codigo);

        (new Mailer())->send((string) $usuario['email'], $nombre, $subject, $html, $text);
    }

    /**
     * Plantilla HTML simple para el correo del código.
     */
    private function plantillaCodigo(string $nombre, string $codigo): string {
        $nombreSafe = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $codigoSafe = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!DOCTYPE html>
<html lang="es"><body style="margin:0;background:#0f0f10;font-family:Arial,Helvetica,sans-serif;color:#e9e9ea;padding:24px">
  <div style="max-width:480px;margin:0 auto;background:#1a1a1c;border:1px solid #2a2a2d;border-radius:12px;padding:32px">
    <h1 style="font-size:20px;margin:0 0 16px">Verificación en dos pasos</h1>
    <p style="font-size:14px;line-height:1.6;color:#bdbdbf">Hola {$nombreSafe}, usá este código para completar tu inicio de sesión en Tornalyx:</p>
    <div style="font-size:34px;font-weight:700;letter-spacing:10px;text-align:center;color:#fff;background:#0f0f10;border:1px solid #2a2a2d;border-radius:10px;padding:18px;margin:20px 0">{$codigoSafe}</div>
    <p style="font-size:13px;color:#8a8a8d">El código vence en 10 minutos. Si no intentaste iniciar sesión, ignorá este mensaje.</p>
  </div>
</body></html>
HTML;
    }

    /**
     * Enmascara un correo para mostrarlo sin revelarlo por completo.
     * Ej.: "rodrigo@gmail.com" → "r******@gmail.com".
     */
    private function maskEmail(string $email): string {
        $parts = explode('@', $email);
        if (count($parts) !== 2 || $parts[0] === '') {
            return '***';
        }
        $name = $parts[0];
        return substr($name, 0, 1)
             . str_repeat('*', max(1, strlen($name) - 1))
             . '@' . $parts[1];
    }

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
