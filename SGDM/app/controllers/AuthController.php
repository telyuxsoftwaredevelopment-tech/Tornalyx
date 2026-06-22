<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/OtpCode.php';
require_once __DIR__ . '/../shared/Session.php';
require_once __DIR__ . '/../shared/LoginThrottle.php';
require_once __DIR__ . '/../shared/Mailer.php';
require_once __DIR__ . '/../shared/WhatsAppSender.php';

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
        // Si hay un desafío MFA pendiente (p. ej. tras registrarse o recargar),
        // el frontend lo detecta consultando GET /api/2fa/estado y muestra el
        // paso correcto. No inyectamos scripts inline (la CSP los bloquea).
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

        // Credenciales correctas: en vez de iniciar sesión, abrimos el segundo
        // factor. La sesión NO queda autenticada hasta verificar el código.
        // Todavía no enviamos nada: el usuario elige el canal (email/WhatsApp).
        $this->iniciarMfa($usuario);
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

        // Reenviar por el mismo canal que se eligió al enviar el primer código.
        $canal = $pending['canal'] ?? 'email';
        if ($canal === 'whatsapp' && !$this->whatsappDisponible($usuario)) {
            $canal = 'email';
        }

        $codigo = $otp->generar($userId);
        try {
            if ($canal === 'whatsapp') {
                (new WhatsAppSender())->send((string) $usuario['telefono'], $codigo);
                $target = $this->maskPhone((string) $usuario['telefono']);
            } else {
                $this->enviarCodigo($usuario, $codigo);
                $target = $this->maskEmail((string) $usuario['email']);
            }
        } catch (Throwable $e) {
            error_log('Fallo al reenviar código MFA (' . $canal . '): ' . $e->getMessage());
            http_response_code(500);
            $this->jsonError('No pudimos reenviar el código. Intentá más tarde.');
            return;
        }

        $_SESSION['pending_2fa']['canal']   = $canal;
        $_SESSION['pending_2fa']['expires'] = time() + 600;
        $this->jsonSuccess(['canal' => $canal, 'target_masked' => $target]);
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
        $telefono  = trim(filter_input(INPUT_POST, 'telefono', FILTER_DEFAULT)                ?? '');
        $password  = filter_input(INPUT_POST, 'password',      FILTER_DEFAULT)                ?? '';
        $fechaNac  = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_DEFAULT)             ?? '';
        $rol       = filter_input(INPUT_POST, 'rol', FILTER_DEFAULT)                          ?? 'participante';

        // Validaciones básicas en backend
        if (empty($nombre) || empty($email) || empty($password) || empty($fechaNac) || empty($telefono)) {
            $this->jsonError('Todos los campos son obligatorios.');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError('Correo electrónico inválido.');
            return;
        }
        // Teléfono en formato internacional E.164: "+" y 8 a 15 dígitos.
        $telefono = $this->normalizarTelefono($telefono);
        if (!$this->telefonoEsValido($telefono)) {
            $this->jsonError('Ingresá un teléfono válido en formato internacional, p. ej. +59899123456.');
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

        $id = $this->usuarioModel->registrar($nombre, $apellido, $email, $password, $fechaNac, $rol, $telefono);
        $usuario = $this->usuarioModel->findById($id);

        // 2FA obligatorio para todos: tras crear la cuenta también abrimos el
        // desafío MFA (elegir canal) antes de dejar la sesión iniciada.
        $this->iniciarMfa($usuario);
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

    /**
     * Devuelve los datos del usuario autenticado (GET /api/me) para que el
     * frontend rellene nombre, rol, etc. Nunca expone la contraseña.
     */
    public function me(): void {
        Session::start();
        if (!Session::isLoggedIn()) {
            http_response_code(401);
            $this->jsonError('No autenticado.');
            return;
        }
        $u = $this->usuarioModel->findById((int) Session::getUserId());
        if (!$u) {
            http_response_code(401);
            $this->jsonError('No autenticado.');
            return;
        }
        $this->jsonSuccess([
            'id'       => (int) $u['id'],
            'nombre'   => $u['nombre'],
            'apellido' => $u['apellido'],
            'email'    => $u['email'],
            'rol'      => $u['rol'],
        ]);
    }

    // ──────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────

    /**
     * Abre el desafío MFA: deja un estado "pendiente" en la sesión (que NO
     * queda autenticada) y responde con los canales disponibles para que el
     * usuario elija. Todavía NO se genera ni envía el código.
     */
    private function iniciarMfa(array $usuario): void {
        $userId = (int) $usuario['id'];

        // Renovamos el id de sesión al establecer el desafío para cerrar la
        // ventana de fijación de sesión. Los datos de $_SESSION se conservan.
        session_regenerate_id(true);

        $_SESSION['pending_2fa'] = [
            'user_id'      => $userId,
            'expires'      => time() + 600,
            'step'         => 'choose',
            'email_masked' => $this->maskEmail($usuario['email']),
            'phone_masked' => !empty($usuario['telefono']) ? $this->maskPhone($usuario['telefono']) : null,
        ];

        $this->jsonSuccess(array_merge(
            ['mfa_choose' => true],
            $this->canalesDisponibles($usuario)
        ));
    }

    /**
     * Genera y envía el código por el canal elegido (POST /login/codigo).
     */
    public function enviarCodigo2fa(): void {
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

        $canal = (string) (filter_input(INPUT_POST, 'canal', FILTER_DEFAULT) ?? 'email');
        if (!in_array($canal, ['email', 'whatsapp'], true)) {
            $canal = 'email';
        }

        $usuario = $this->usuarioModel->findById((int) $pending['user_id']);
        if (!$usuario) {
            unset($_SESSION['pending_2fa']);
            http_response_code(440);
            $this->jsonError('Tu sesión de verificación expiró. Iniciá sesión de nuevo.');
            return;
        }

        // WhatsApp solo si está configurado y el usuario tiene teléfono; si no,
        // se rechaza el canal (no caemos silenciosamente a otro).
        if ($canal === 'whatsapp' && !$this->whatsappDisponible($usuario)) {
            $this->jsonError('WhatsApp no está disponible para tu cuenta. Usá el correo.');
            return;
        }

        $otp    = new OtpCode();
        $codigo = $otp->generar((int) $usuario['id']);
        try {
            if ($canal === 'whatsapp') {
                (new WhatsAppSender())->send((string) $usuario['telefono'], $codigo);
                $target = $this->maskPhone((string) $usuario['telefono']);
            } else {
                $this->enviarCodigo($usuario, $codigo);
                $target = $this->maskEmail((string) $usuario['email']);
            }
        } catch (Throwable $e) {
            error_log('Fallo al enviar código MFA (' . $canal . '): ' . $e->getMessage());
            $otp->invalidar((int) $usuario['id']);
            http_response_code(500);
            $this->jsonError('No pudimos enviar el código. Probá con otro canal.');
            return;
        }

        $_SESSION['pending_2fa']['step']    = 'sent';
        $_SESSION['pending_2fa']['canal']   = $canal;
        $_SESSION['pending_2fa']['expires'] = time() + 600;

        $this->jsonSuccess([
            'twofa'         => true,
            'canal'         => $canal,
            'target_masked' => $target,
        ]);
    }

    /**
     * Devuelve el estado del desafío MFA pendiente (GET /api/2fa/estado) para
     * que el frontend muestre el paso correcto sin depender de scripts inline.
     */
    public function estado2fa(): void {
        Session::start();

        $pending = $_SESSION['pending_2fa'] ?? null;
        if (!is_array($pending) || empty($pending['user_id']) || time() > ($pending['expires'] ?? 0)) {
            $this->jsonSuccess(['pending' => false]);
            return;
        }

        $channels = ['email' => $pending['email_masked'] ?? ''];
        if (!empty($pending['phone_masked']) && (new WhatsAppSender())->isConfigured()) {
            $channels['whatsapp'] = $pending['phone_masked'];
        }

        $resp = [
            'pending'  => true,
            'step'     => $pending['step'] ?? 'choose',
            'channels' => $channels,
        ];
        if (($pending['step'] ?? '') === 'sent') {
            $resp['canal']         = $pending['canal'] ?? 'email';
            $resp['target_masked'] = ($pending['canal'] ?? '') === 'whatsapp'
                ? ($pending['phone_masked'] ?? '')
                : ($pending['email_masked'] ?? '');
        }
        $this->jsonSuccess($resp);
    }

    /**
     * Indica si el canal WhatsApp está disponible para un usuario (tiene
     * teléfono cargado y el proveedor está configurado).
     */
    private function whatsappDisponible(array $usuario): bool {
        return !empty($usuario['telefono']) && (new WhatsAppSender())->isConfigured();
    }

    /**
     * Canales por los que se puede mandar el código (email siempre; WhatsApp
     * solo si está disponible), con el destino enmascarado.
     */
    private function canalesDisponibles(array $usuario): array {
        $channels = ['email' => $this->maskEmail((string) $usuario['email'])];
        if ($this->whatsappDisponible($usuario)) {
            $channels['whatsapp'] = $this->maskPhone((string) $usuario['telefono']);
        }
        return ['channels' => $channels];
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
     * Enmascara un teléfono dejando visibles los últimos 4 dígitos.
     * Ej.: "+59899123456" → "+598•••••3456".
     */
    private function maskPhone(string $telefono): string {
        $digits = preg_replace('/\D/', '', $telefono);
        if (strlen($digits) <= 4) {
            return '+' . $digits;
        }
        $last = substr($digits, -4);
        return '+' . str_repeat('•', strlen($digits) - 4) . $last;
    }

    /**
     * Normaliza un teléfono a formato E.164: deja solo "+" inicial y dígitos.
     */
    private function normalizarTelefono(string $telefono): string {
        $telefono = trim($telefono);
        $tienePlus = str_starts_with($telefono, '+');
        $digits = preg_replace('/\D/', '', $telefono);
        return ($tienePlus ? '+' : '') . $digits;
    }

    /**
     * Valida formato internacional E.164: "+" seguido de 8 a 15 dígitos, sin
     * empezar en 0.
     */
    private function telefonoEsValido(string $telefono): bool {
        return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $telefono);
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
