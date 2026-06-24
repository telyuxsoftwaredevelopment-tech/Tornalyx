<?php
// Asegura que las variables WHATSAPP_* del .env estén cargadas (no abre BD).
require_once __DIR__ . '/../../config/database.php';

/**
 * Envío de códigos por WhatsApp usando la Cloud API de Meta (sin dependencias).
 *
 * Manda un mensaje de PLANTILLA de autenticación (categoría "authentication"),
 * que es lo único que Meta permite para mensajes iniciados por el negocio fuera
 * de una ventana de conversación. La plantilla debe estar creada y aprobada en
 * el WhatsApp Manager; su nombre/idioma se configuran por entorno.
 *
 * Config (.env):
 *   WHATSAPP_TOKEN          Token de acceso (Bearer) del sistema/app.
 *   WHATSAPP_PHONE_ID       ID del número remitente (Phone Number ID).
 *   WHATSAPP_TEMPLATE_NAME  Nombre de la plantilla de autenticación aprobada.
 *   WHATSAPP_TEMPLATE_LANG  Código de idioma de la plantilla (def. "es").
 *   WHATSAPP_TEMPLATE_BUTTON 'copy' (def.) si la plantilla tiene botón de
 *                           copiar código, o 'none' si no tiene botón.
 *   WHATSAPP_API_VERSION    Versión de Graph API (def. "v21.0").
 *
 * Diseño: ante cualquier fallo lanza excepción; el llamador nunca debe asumir
 * que el código llegó. Mientras no esté configurado, isConfigured() devuelve
 * false y la app ni siquiera ofrece el canal WhatsApp.
 */
class WhatsAppSender {

    private string $token;
    private string $phoneId;
    private string $template;
    private string $lang;
    private string $button;
    private string $apiVersion;
    private int    $timeout = 15;

    public function __construct() {
        $this->token      = (string) (getenv('WHATSAPP_TOKEN')          ?: '');
        $this->phoneId    = (string) (getenv('WHATSAPP_PHONE_ID')       ?: '');
        $this->template   = (string) (getenv('WHATSAPP_TEMPLATE_NAME')  ?: '');
        $this->lang       = (string) (getenv('WHATSAPP_TEMPLATE_LANG')  ?: 'es');
        $this->button     = strtolower((string) (getenv('WHATSAPP_TEMPLATE_BUTTON') ?: 'copy'));
        $this->apiVersion = (string) (getenv('WHATSAPP_API_VERSION')    ?: 'v21.0');
    }

    /**
     * Indica si hay configuración suficiente para intentar un envío.
     */
    public function isConfigured(): bool {
        return $this->token !== '' && $this->phoneId !== '' && $this->template !== '';
    }

    /**
     * Envía el código por WhatsApp al teléfono indicado.
     *
     * @param string $toPhone Teléfono destino (se normaliza a solo dígitos).
     * @param string $codigo  Código de un solo uso.
     * @throws RuntimeException si la configuración falta o el envío falla.
     */
    public function send(string $toPhone, string $codigo): void {
        if (!$this->isConfigured()) {
            throw new RuntimeException('WhatsApp no configurado (faltan WHATSAPP_TOKEN/PHONE_ID/TEMPLATE_NAME).');
        }

        // Meta espera el número solo con dígitos: sin "+", espacios ni guiones.
        $to = preg_replace('/\D/', '', $toPhone);
        if ($to === '') {
            throw new RuntimeException('Teléfono destino inválido.');
        }

        // La plantilla de autenticación lleva el código en el cuerpo y, si tiene
        // botón "copiar código", el mismo valor en el botón (sub_type=url, idx 0).
        $components = [[
            'type'       => 'body',
            'parameters' => [['type' => 'text', 'text' => $codigo]],
        ]];
        if ($this->button !== 'none') {
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [['type' => 'text', 'text' => $codigo]],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $this->template,
                'language'   => ['code' => $this->lang],
                'components' => $components,
            ],
        ];

        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneId}/messages";
        $this->post($url, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * POST JSON autenticado a la Graph API. Lanza si la respuesta no es 2xx.
     */
    private function post(string $url, string $json): void {
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_HTTPHEADER     => $headers,
            ]);
            $body = curl_exec($ch);
            if ($body === false) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('Error de red al llamar a WhatsApp: ' . $err);
            }
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
        } else {
            // Fallback sin cURL.
            $ctx = stream_context_create(['http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers) . "\r\n",
                'content'       => $json,
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
            ]]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body === false) {
                throw new RuntimeException('Error de red al llamar a WhatsApp.');
            }
            $status = 0;
            if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
        }

        if ($status < 200 || $status >= 300) {
            $detail  = '';
            $decoded = json_decode((string) $body, true);
            if (isset($decoded['error']['message'])) {
                $detail = $decoded['error']['message'];
            }
            throw new RuntimeException("WhatsApp respondió HTTP {$status}: {$detail}");
        }
    }
}
