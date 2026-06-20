<?php
// Asegura que las variables SMTP_* del archivo .env estén cargadas en el
// entorno aunque todavía no se haya tocado la base de datos. Requerir este
// archivo ejecuta loadDotEnv() pero NO abre conexión (getDB es perezoso).
require_once __DIR__ . '/../../config/database.php';

/**
 * Cliente SMTP mínimo en PHP puro (sin dependencias externas) para enviar
 * correos transaccionales, p. ej. el código de verificación de doble factor.
 *
 * Soporta STARTTLS (puerto 587) y TLS implícito (puerto 465) con AUTH LOGIN,
 * que cubre Gmail y la mayoría de proveedores. Toda la configuración se lee de
 * variables de entorno (.env):
 *
 *   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_FROM, SMTP_FROM_NAME
 *
 * Diseño deliberado: ante cualquier fallo se lanza una excepción. El llamador
 * NUNCA debe asumir que el código llegó si no hubo envío exitoso (en 2FA
 * obligatorio, un fallo silencioso dejaría a la persona sin poder entrar).
 */
class Mailer {

    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $from;
    private string $fromName;
    private int    $timeout = 15;

    public function __construct() {
        $this->host     = (string) (getenv('SMTP_HOST') ?: '');
        $this->port     = (int)    (getenv('SMTP_PORT') ?: 587);
        $this->user     = (string) (getenv('SMTP_USER') ?: '');
        $this->pass     = (string) (getenv('SMTP_PASS') ?: '');
        $this->from     = (string) (getenv('SMTP_FROM') ?: $this->user);
        $this->fromName = (string) (getenv('SMTP_FROM_NAME') ?: 'Tornalyx');
    }

    /**
     * Indica si hay configuración SMTP suficiente para intentar un envío.
     */
    public function isConfigured(): bool {
        return $this->host !== '' && $this->user !== '' && $this->pass !== '';
    }

    /**
     * Envía un correo multipart (texto + HTML).
     *
     * @throws RuntimeException si la conexión, autenticación o entrega fallan.
     */
    public function send(string $toEmail, string $toName, string $subject, string $html, string $text): void {
        if (!$this->isConfigured()) {
            throw new RuntimeException('SMTP no configurado (faltan SMTP_HOST/SMTP_USER/SMTP_PASS).');
        }

        $implicitTls = ($this->port === 465);
        $transport   = $implicitTls ? 'ssl://' : 'tcp://';

        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'SNI_enabled'       => true,
            ],
        ]);

        $errno  = 0;
        $errstr = '';
        $conn = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $ctx
        );
        if ($conn === false) {
            throw new RuntimeException("No se pudo conectar al servidor SMTP: {$errstr} ({$errno})");
        }
        stream_set_timeout($conn, $this->timeout);

        try {
            $this->expect($conn, 220);

            $ehlo = $this->ehloName();
            $this->cmd($conn, "EHLO {$ehlo}", 250);

            if (!$implicitTls) {
                $this->cmd($conn, 'STARTTLS', 220);
                $ok = stream_socket_enable_crypto(
                    $conn,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                );
                if ($ok !== true) {
                    throw new RuntimeException('No se pudo establecer TLS (STARTTLS) con el servidor SMTP.');
                }
                // El estándar exige re-emitir EHLO tras activar TLS.
                $this->cmd($conn, "EHLO {$ehlo}", 250);
            }

            // Autenticación AUTH LOGIN (usuario y clave en base64).
            $this->cmd($conn, 'AUTH LOGIN', 334);
            $this->cmd($conn, base64_encode($this->user), 334);
            $this->cmd($conn, base64_encode($this->pass), 235);

            // Sobre del mensaje.
            $this->cmd($conn, 'MAIL FROM:<' . $this->from . '>', 250);
            $this->cmd($conn, 'RCPT TO:<' . $toEmail . '>', 250);
            $this->cmd($conn, 'DATA', 354);

            $message = $this->buildMessage($toEmail, $toName, $subject, $html, $text);
            fwrite($conn, $message . "\r\n.\r\n");
            $this->expect($conn, 250);

            // Cierre cordial; si el servidor no responde 221 no es un fallo de
            // entrega (el mensaje ya fue aceptado con el 250 anterior).
            @fwrite($conn, "QUIT\r\n");
        } finally {
            @fclose($conn);
        }
    }

    // ──────────────────────────────────────────────────────
    // Internos
    // ──────────────────────────────────────────────────────

    /**
     * Envía un comando y verifica el código de respuesta esperado.
     */
    private function cmd($conn, string $command, int $expectedCode): string {
        fwrite($conn, $command . "\r\n");
        return $this->expect($conn, $expectedCode);
    }

    /**
     * Lee la respuesta del servidor y verifica su código.
     *
     * @throws RuntimeException si el código no coincide.
     */
    private function expect($conn, int $code): string {
        $response = $this->readResponse($conn);
        $actual   = (int) substr($response, 0, 3);
        if ($actual !== $code) {
            throw new RuntimeException("SMTP: se esperaba {$code} pero llegó: " . trim($response));
        }
        return $response;
    }

    /**
     * Lee una respuesta SMTP completa (soporta respuestas multilínea como
     * "250-...\r\n250 ...\r\n").
     */
    private function readResponse($conn): string {
        $data = '';
        while (($line = fgets($conn, 515)) !== false) {
            $data .= $line;
            // En una respuesta multilínea, la última línea tiene un espacio en
            // la 4.ª posición ("250 ..."); las intermedias, un guion ("250-...").
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
            $meta = stream_get_meta_data($conn);
            if (!empty($meta['timed_out'])) {
                throw new RuntimeException('SMTP: tiempo de espera agotado al leer la respuesta.');
            }
        }
        if ($data === '') {
            throw new RuntimeException('SMTP: el servidor no respondió.');
        }
        return $data;
    }

    /**
     * Arma el mensaje RFC 5322 multipart/alternative (texto + HTML).
     */
    private function buildMessage(string $toEmail, string $toName, string $subject, string $html, string $text): string {
        $boundary  = 'b_' . bin2hex(random_bytes(12));
        $fromValue = $this->encodeHeader($this->fromName) . ' <' . $this->from . '>';
        $toValue   = ($toName !== '' ? $this->encodeHeader($toName) . ' ' : '') . '<' . $toEmail . '>';
        $messageId = '<' . bin2hex(random_bytes(16)) . '@' . $this->ehloName() . '>';

        $headers = [
            'Date: '         . date('r'),
            'From: '         . $fromValue,
            'To: '           . $toValue,
            'Subject: '      . $this->encodeHeader($subject),
            'Message-ID: '   . $messageId,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body = "--{$boundary}\r\n"
              . "Content-Type: text/plain; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($text)) . "\r\n"
              . "--{$boundary}\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($html)) . "\r\n"
              . "--{$boundary}--\r\n";

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;

        // Dot-stuffing: una línea que empiece con "." debe duplicar el punto
        // para no confundirse con el terminador de DATA ("\r\n.\r\n").
        return preg_replace('/^\./m', '..', $message);
    }

    /**
     * Codifica un encabezado con caracteres no ASCII (acentos, etc.).
     */
    private function encodeHeader(string $value): string {
        if (preg_match('/[^\x20-\x7e]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    /**
     * Nombre de host válido para el comando EHLO y el Message-ID.
     */
    private function ehloName(): string {
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        return preg_match('/^[A-Za-z0-9.\-]+$/', (string) $host) ? (string) $host : 'localhost';
    }
}
