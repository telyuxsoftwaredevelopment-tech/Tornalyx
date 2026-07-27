<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Torneo.php';
require_once __DIR__ . '/../models/Partido.php';
require_once __DIR__ . '/../models/Posicion.php';
require_once __DIR__ . '/../shared/Session.php';

/**
 * Controlador del perfil del usuario autenticado.
 * Expone los datos reales del perfil (sin seeds) y permite editarlos:
 * datos personales, biografía, ubicación, contraseña y foto de perfil.
 */
class PerfilController extends Controller {

    /** Roles que pueden operar sobre su propio perfil. */
    private const ROLES = ['participante', 'organizador', 'administrador'];

    /** Límite de la biografía, en palabras. */
    private const BIO_MAX_PALABRAS = 500;

    /** Tamaño máximo del avatar en bytes (2 MB). */
    private const AVATAR_MAX_BYTES = 2 * 1024 * 1024;

    /** MIME reales admitidos para el avatar y su extensión de guardado. */
    private const AVATAR_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** Carpeta física (dentro de frontend/) donde se guardan los avatares. */
    private const AVATAR_DIR = __DIR__ . '/../../frontend/uploads/avatars';

    /** Prefijo público con el que se sirve esa carpeta. */
    private const AVATAR_URL = '/uploads/avatars';

    private Usuario $usuarioModel;
    private Torneo  $torneoModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
        $this->torneoModel  = new Torneo();
    }

    /**
     * Datos completos del perfil (GET /api/perfil): usuario + historial de
     * torneos + equipos + estadísticas agregadas, todo desde la base.
     */
    public function datos(): void {
        if (!$this->requireApiRole(self::ROLES)) {
            return;
        }
        $userId  = (int) Session::getUserId();
        $usuario = $this->usuarioModel->findById($userId);
        if (!$usuario) {
            $this->jsonError('No autenticado.', [], 401);
            return;
        }

        $torneos = $this->torneoModel->listarDeParticipante($userId);
        $equipos = $this->torneoModel->equiposDeUsuario($userId);

        $activos = count(array_filter($torneos, static fn($t) =>
            in_array($t['estado'], ['inscripcion', 'en_curso'], true)
            && $t['inscripcion_estado'] === 'aprobada'
        ));
        $finalizados = count(array_filter($torneos, static fn($t) => $t['estado'] === 'finalizado'));

        $this->jsonSuccess([
            'usuario' => $this->usuarioPublico($usuario),
            'torneos' => $torneos,
            'equipos' => $equipos,
            'stats'   => [
                'torneos'     => count($torneos),
                'equipos'     => count($equipos),
                'activos'     => $activos,
                'finalizados' => $finalizados,
            ],
        ]);
    }

    /**
     * Actualiza datos personales, bio y ubicación (POST /api/perfil/actualizar).
     */
    public function actualizar(): void {
        if (!$this->requireApiRole(self::ROLES)) {
            return;
        }
        $userId = (int) Session::getUserId();

        $nombre    = trim((string) (filter_input(INPUT_POST, 'nombre',    FILTER_DEFAULT) ?? ''));
        $apellido  = trim((string) (filter_input(INPUT_POST, 'apellido',  FILTER_DEFAULT) ?? ''));
        $email     = (string) (filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
        $fechaNac  = trim((string) (filter_input(INPUT_POST, 'fecha_nac', FILTER_DEFAULT) ?? ''));
        $ubicacion = trim((string) (filter_input(INPUT_POST, 'ubicacion', FILTER_DEFAULT) ?? ''));
        $bio       = trim((string) (filter_input(INPUT_POST, 'bio',       FILTER_DEFAULT) ?? ''));

        if ($nombre === '' || $apellido === '' || $email === '' || $fechaNac === '') {
            $this->jsonError('Nombre, apellido, correo y fecha de nacimiento son obligatorios.');
            return;
        }
        if (mb_strlen($nombre) > 60 || mb_strlen($apellido) > 60) {
            $this->jsonError('Nombre y apellido no pueden superar los 60 caracteres.');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
            $this->jsonError('Correo electrónico inválido.');
            return;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaNac);
        if ($d === false || $d->format('Y-m-d') !== $fechaNac) {
            $this->jsonError('La fecha de nacimiento no es válida.');
            return;
        }
        if (mb_strlen($ubicacion) > 120) {
            $this->jsonError('La ubicación no puede superar los 120 caracteres.');
            return;
        }
        $palabras = $bio === '' ? 0 : count(preg_split('/\s+/u', $bio, -1, PREG_SPLIT_NO_EMPTY));
        if ($palabras > self::BIO_MAX_PALABRAS) {
            $this->jsonError(sprintf(
                'La descripción no puede superar las %d palabras (tiene %d).',
                self::BIO_MAX_PALABRAS, $palabras
            ));
            return;
        }
        if ($this->usuarioModel->emailExiste($email, $userId)) {
            $this->jsonError('Ese correo ya está en uso por otra cuenta.', [], 409);
            return;
        }

        $this->usuarioModel->actualizar($userId, [
            'nombre'    => $nombre,
            'apellido'  => $apellido,
            'email'     => $email,
            'fecha_nac' => $fechaNac,
            'ubicacion' => $ubicacion !== '' ? $ubicacion : null,
            'bio'       => $bio !== '' ? $bio : null,
        ]);

        // El nav y los saludos usan el nombre guardado en sesión.
        $_SESSION['usuario_nombre'] = $nombre;

        $usuario = $this->usuarioModel->findById($userId);
        $this->jsonSuccess(['usuario' => $this->usuarioPublico($usuario ?? [])]);
    }

    /**
     * Cambia la contraseña verificando la actual (POST /api/perfil/password).
     */
    public function password(): void {
        if (!$this->requireApiRole(self::ROLES)) {
            return;
        }
        $userId  = (int) Session::getUserId();
        $actual  = (string) (filter_input(INPUT_POST, 'actual', FILTER_DEFAULT) ?? '');
        $nueva   = (string) (filter_input(INPUT_POST, 'nueva',  FILTER_DEFAULT) ?? '');

        $usuario = $this->usuarioModel->findById($userId);
        if (!$usuario || !password_verify($actual, (string) $usuario['password'])) {
            $this->jsonError('La contraseña actual no es correcta.', [], 401);
            return;
        }
        $esFuerte = strlen($nueva) >= 8
            && preg_match('/[A-Z]/', $nueva)
            && preg_match('/[a-z]/', $nueva)
            && preg_match('/[0-9]/', $nueva);
        if (!$esFuerte) {
            $this->jsonError('La nueva contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas y números.');
            return;
        }

        $this->usuarioModel->actualizar($userId, ['password' => $nueva]);
        $this->jsonSuccess();
    }

    /**
     * Sube o reemplaza la foto de perfil (POST /api/perfil/avatar).
     * Valida tipo real (finfo + getimagesize) y tamaño; guarda con nombre
     * aleatorio bajo public/uploads/avatars y borra la foto anterior.
     */
    public function avatar(): void {
        if (!$this->requireApiRole(self::ROLES)) {
            return;
        }
        $userId = (int) Session::getUserId();

        $file = $_FILES['avatar'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError('No se recibió ninguna imagen.');
            return;
        }
        if ((int) $file['size'] > self::AVATAR_MAX_BYTES) {
            $this->jsonError('La imagen no puede superar los 2 MB.');
            return;
        }

        // Tipo real del archivo, no el que declara el cliente.
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::AVATAR_MIMES[$mime]) || getimagesize($file['tmp_name']) === false) {
            $this->jsonError('Formato no admitido. Usá JPG, PNG o WebP.');
            return;
        }

        if (!is_dir(self::AVATAR_DIR) && !mkdir(self::AVATAR_DIR, 0755, true)) {
            $this->jsonError('No se pudo guardar la imagen. Intentá más tarde.', [], 500);
            return;
        }

        $nombre  = 'u' . $userId . '-' . bin2hex(random_bytes(8)) . '.' . self::AVATAR_MIMES[$mime];
        $destino = self::AVATAR_DIR . '/' . $nombre;
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            $this->jsonError('No se pudo guardar la imagen. Intentá más tarde.', [], 500);
            return;
        }

        // Borrar la foto anterior solo si vivía en nuestra carpeta de avatares.
        $usuario  = $this->usuarioModel->findById($userId);
        $anterior = (string) ($usuario['avatar_url'] ?? '');
        if (str_starts_with($anterior, self::AVATAR_URL . '/')) {
            $fisico = self::AVATAR_DIR . '/' . basename($anterior);
            if (is_file($fisico)) {
                @unlink($fisico);
            }
        }

        $url = self::AVATAR_URL . '/' . $nombre;
        $this->usuarioModel->actualizar($userId, ['avatar_url' => $url]);
        $this->jsonSuccess(['avatar_url' => $url]);
    }

    /**
     * Busca jugadores por nombre o apellido (GET /api/jugadores?q=).
     * Público y acotado: solo datos básicos, nunca el correo ni la fecha de
     * nacimiento de terceros.
     */
    public function buscar(): void {
        $q = trim((string) (filter_input(INPUT_GET, 'q', FILTER_DEFAULT) ?? ''));
        if (mb_strlen($q) < 2) {
            $this->jsonSuccess(['jugadores' => []]);
            return;
        }
        $this->jsonSuccess(['jugadores' => $this->usuarioModel->buscar($q)]);
    }

    /**
     * Ficha pública de un jugador (GET /api/jugador/{id}): su perfil visible,
     * los torneos en los que participa, su rendimiento acumulado y sus
     * próximos enfrentamientos con rival, fecha y lugar.
     */
    public function publico(int $id): void {
        $u = $this->usuarioModel->findById($id);
        if (!$u || $u['estado'] === 'suspendido') {
            $this->jsonError('Jugador no encontrado.', [], 404);
            return;
        }

        $this->jsonSuccess([
            'jugador' => [
                'id'         => (int) $u['id'],
                'nombre'     => $u['nombre'],
                'apellido'   => $u['apellido'],
                'rol'        => $u['rol'],
                'bio'        => $u['bio']        ?? null,
                'ubicacion'  => $u['ubicacion']  ?? null,
                'avatar_url' => $u['avatar_url'] ?? null,
                'created_at' => $u['created_at'] ?? null,
            ],
            'torneos'   => $this->torneoModel->listarDeParticipante($id),
            'equipos'   => $this->torneoModel->equiposDeUsuario($id),
            'resumen'   => (new Posicion())->resumenDeContendiente($id),
            'proximos'  => (new Partido())->proximosDeUsuario($id),
        ]);
    }

    /**
     * Subconjunto del usuario que es seguro exponer al cliente.
     */
    private function usuarioPublico(array $u): array {
        return [
            'id'         => (int) ($u['id'] ?? 0),
            'nombre'     => $u['nombre']     ?? '',
            'apellido'   => $u['apellido']   ?? '',
            'email'      => $u['email']      ?? '',
            'rol'        => $u['rol']        ?? '',
            'fecha_nac'  => $u['fecha_nac']  ?? null,
            'ubicacion'  => $u['ubicacion']  ?? null,
            'bio'        => $u['bio']        ?? null,
            'avatar_url' => $u['avatar_url'] ?? null,
            'created_at' => $u['created_at'] ?? null,
        ];
    }
}
