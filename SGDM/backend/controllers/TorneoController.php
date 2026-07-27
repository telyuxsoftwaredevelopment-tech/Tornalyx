<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Torneo.php';
require_once __DIR__ . '/../models/Resultado.php';
require_once __DIR__ . '/../models/Posicion.php';
require_once __DIR__ . '/../models/Partido.php';
require_once __DIR__ . '/../models/Aviso.php';
require_once __DIR__ . '/../models/Inscripcion.php';
require_once __DIR__ . '/../shared/Session.php';

/**
 * Controlador de torneos.
 * Maneja listado, creación, edición, resultados y posiciones.
 */
class TorneoController extends Controller {

    /** Formatos de torneo aceptados (coinciden con el ENUM de la tabla). */
    private const FORMATOS = ['liga', 'eliminacion_directa', 'suizo', 'grupos_playoff'];

    /** Estados aceptados (coinciden con el ENUM de la tabla). */
    private const ESTADOS = ['borrador', 'inscripcion', 'en_curso', 'finalizado', 'cancelado'];

    /** Límites de participantes admitidos al crear un torneo. */
    private const MIN_PARTICIPANTES = 2;
    private const MAX_PARTICIPANTES = 512;

    private Torneo    $torneoModel;
    private Resultado $resultadoModel;

    public function __construct() {
        parent::__construct();
        $this->torneoModel    = new Torneo();
        $this->resultadoModel = new Resultado();
    }

    /**
     * Lista torneos públicos con filtros (GET /api/torneos).
     */
    public function index(): void {
        $estado  = (string) (filter_input(INPUT_GET, 'estado',  FILTER_DEFAULT) ?? '');
        $formato = (string) (filter_input(INPUT_GET, 'formato', FILTER_DEFAULT) ?? '');

        $filtros = [
            // Solo se aceptan valores del ENUM; cualquier otra cosa se ignora
            // en vez de devolver un listado vacío sin explicación.
            'estado'     => in_array($estado,  self::ESTADOS,  true) ? $estado  : '',
            'formato'    => in_array($formato, self::FORMATOS, true) ? $formato : '',
            'disciplina' => trim((string) (filter_input(INPUT_GET, 'disciplina', FILTER_DEFAULT) ?? '')),
        ];
        $torneos = $this->torneoModel->listarPublicos(array_filter($filtros));
        $this->jsonSuccess(['torneos' => array_map([$this, 'normalizar'], $torneos)]);
    }

    /**
     * Torneos del usuario autenticado (GET /api/torneos/mios).
     * El organizador ve los suyos; el administrador ve todos.
     */
    public function mios(): void {
        if (!$this->requireApiRole(['organizador', 'administrador'])) {
            return;
        }

        $torneos = Session::getUserRole() === 'administrador'
            ? $this->torneoModel->listarTodosConMetricas()
            : $this->torneoModel->listarDeOrganizador((int) Session::getUserId());

        $this->jsonSuccess(['torneos' => array_map([$this, 'normalizar'], $torneos)]);
    }

    /**
     * Detalle de un torneo (GET /api/torneo/{id}).
     *
     * @param int $id
     */
    public function show(int $id): void {
        $torneo = $this->torneoModel->findConOrganizador($id);
        if (!$torneo) {
            http_response_code(404);
            $this->jsonError('Torneo no encontrado.');
            return;
        }

        // Los torneos no públicos (borradores) solo son visibles para su
        // organizador dueño y para administradores. Para cualquier otro se
        // responde 404 (no 403) para no revelar siquiera su existencia.
        if (!$torneo['publico']) {
            $esDueno = Session::getUserId() === (int) $torneo['organizador_id'];
            $esAdmin = Session::getUserRole() === 'administrador';
            if (!$esDueno && !$esAdmin) {
                http_response_code(404);
                $this->jsonError('Torneo no encontrado.');
                return;
            }
        }
        $this->jsonSuccess([
            'torneo'     => $this->normalizar($torneo),
            'posiciones' => $this->torneoModel->getPosiciones($id),
            'resultados' => $this->resultadoModel->listarPorTorneo($id),
            'equipos'    => $this->torneoModel->getEquipos($id),
            // Fixture completo (con o sin resultado) y novedades del torneo:
            // así el detalle muestra calendario, rival, horario, lugar y estado.
            'partidos'   => (new Partido())->listarPorTorneo($id),
            'avisos'     => (new Aviso())->listarPorTorneo($id, 10),
            // Inscripción del usuario en sesión, para saber qué botón mostrar.
            'mi_inscripcion' => Session::isLoggedIn()
                ? (new Inscripcion())->deUsuario((int) Session::getUserId(), $id)
                : null,
        ]);
    }

    /**
     * Crea un nuevo torneo (POST /api/torneo/crear).
     * Requiere rol organizador o administrador.
     */
    public function store(): void {
        if (!$this->requireApiRole(['organizador', 'administrador'])) {
            return;
        }

        // Los textos se guardan crudos (ya validados) y se escapan al mostrarlos.
        // Sanitizarlos acá guardaría entidades HTML en la BD y el nombre saldría
        // como "Copa &#34;A&#34;" en cada vista.
        $nombre      = trim((string) (filter_input(INPUT_POST, 'nombre',       FILTER_DEFAULT) ?? ''));
        $descripcion = trim((string) (filter_input(INPUT_POST, 'descripcion',  FILTER_DEFAULT) ?? ''));
        $reglamento  = trim((string) (filter_input(INPUT_POST, 'reglamento',   FILTER_DEFAULT) ?? ''));
        $premios     = trim((string) (filter_input(INPUT_POST, 'premios',      FILTER_DEFAULT) ?? ''));
        $discord     = trim((string) (filter_input(INPUT_POST, 'discord_url',  FILTER_DEFAULT) ?? ''));
        $disciplina  = trim((string) (filter_input(INPUT_POST, 'disciplina',   FILTER_DEFAULT) ?? ''));
        $formato     = trim((string) (filter_input(INPUT_POST, 'formato',      FILTER_DEFAULT) ?? ''));
        $fechaInicio = trim((string) (filter_input(INPUT_POST, 'fecha_inicio', FILTER_DEFAULT) ?? ''));
        $fechaFin    = trim((string) (filter_input(INPUT_POST, 'fecha_fin',    FILTER_DEFAULT) ?? ''));
        $maxPart     = filter_input(INPUT_POST, 'max_participantes', FILTER_VALIDATE_INT);
        $publicar    = filter_var(
            filter_input(INPUT_POST, 'publicar', FILTER_DEFAULT) ?? '1',
            FILTER_VALIDATE_BOOLEAN
        );
        $porEquipos  = filter_var(
            filter_input(INPUT_POST, 'requiere_equipos', FILTER_DEFAULT) ?? '0',
            FILTER_VALIDATE_BOOLEAN
        );

        // ── Validación campo por campo: el front necesita saber QUÉ falló ──
        if ($nombre === '' || $disciplina === '' || $formato === '') {
            $this->jsonError('Nombre, disciplina y formato son obligatorios.', ['campo' => 'nombre']);
            return;
        }
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
            $this->jsonError('El nombre debe tener entre 3 y 120 caracteres.', ['campo' => 'nombre']);
            return;
        }
        if (mb_strlen($disciplina) > 60) {
            $this->jsonError('La disciplina no puede superar los 60 caracteres.', ['campo' => 'disciplina']);
            return;
        }
        if (mb_strlen($descripcion) > 2000) {
            $this->jsonError('La descripción no puede superar los 2000 caracteres.', ['campo' => 'descripcion']);
            return;
        }
        if (mb_strlen($reglamento) > 8000) {
            $this->jsonError('El reglamento no puede superar los 8000 caracteres.', ['campo' => 'reglamento']);
            return;
        }
        if (mb_strlen($premios) > 2000) {
            $this->jsonError('Los premios no pueden superar los 2000 caracteres.', ['campo' => 'premios']);
            return;
        }
        if ($discord !== '' && !filter_var($discord, FILTER_VALIDATE_URL)) {
            $this->jsonError('El enlace del canal oficial no es una URL válida.', ['campo' => 'discord_url']);
            return;
        }
        if (!in_array($formato, self::FORMATOS, true)) {
            $this->jsonError('Formato de torneo inválido.', ['campo' => 'formato']);
            return;
        }

        $maxPart = $maxPart ?: 16;
        if ($maxPart < self::MIN_PARTICIPANTES || $maxPart > self::MAX_PARTICIPANTES) {
            $this->jsonError(sprintf(
                'La cantidad de participantes debe estar entre %d y %d.',
                self::MIN_PARTICIPANTES, self::MAX_PARTICIPANTES
            ), ['campo' => 'max_participantes']);
            return;
        }

        if ($fechaInicio !== '' && !$this->esFechaValida($fechaInicio)) {
            $this->jsonError('La fecha de inicio no es válida.', ['campo' => 'fecha_inicio']);
            return;
        }
        if ($fechaFin !== '' && !$this->esFechaValida($fechaFin)) {
            $this->jsonError('La fecha de fin no es válida.', ['campo' => 'fecha_fin']);
            return;
        }
        if ($fechaInicio !== '' && $fechaFin !== '' && $fechaFin < $fechaInicio) {
            $this->jsonError('La fecha de fin no puede ser anterior a la de inicio.', ['campo' => 'fecha_fin']);
            return;
        }

        $organizadorId = (int) Session::getUserId();
        if ($this->torneoModel->existeNombreDeOrganizador($organizadorId, $nombre)) {
            $this->jsonError('Ya tenés un torneo con ese nombre.', ['campo' => 'nombre'], 409);
            return;
        }

        // Un borrador queda privado hasta que el organizador lo publique; un
        // torneo publicado abre inscripciones y aparece en el listado público.
        $id = $this->torneoModel->insert([
            'organizador_id'    => $organizadorId,
            'nombre'            => $nombre,
            'descripcion'       => $descripcion !== '' ? $descripcion : null,
            'reglamento'        => $reglamento  !== '' ? $reglamento  : null,
            'premios'           => $premios     !== '' ? $premios     : null,
            'discord_url'       => $discord     !== '' ? $discord     : null,
            'disciplina'        => $disciplina,
            'formato'           => $formato,
            'requiere_equipos'  => $porEquipos ? 1 : 0,
            'max_participantes' => $maxPart,
            'fecha_inicio'      => $fechaInicio !== '' ? $fechaInicio : null,
            'fecha_fin'         => $fechaFin    !== '' ? $fechaFin    : null,
            'estado'            => $publicar ? 'inscripcion' : 'borrador',
            'publico'           => $publicar ? 1 : 0,
        ]);

        // Inscripciones abiertas: se avisa a la comunidad (RF-05).
        if ($publicar) {
            (new Aviso())->publicar(
                $id,
                $organizadorId,
                'inscripcion',
                'Inscripciones abiertas: ' . $nombre,
                sprintf('Nuevo torneo de %s. Ya podés inscribirte.', $disciplina)
            );
        }

        // Se devuelve el torneo completo para que el panel lo pinte sin recargar.
        $torneo = $this->torneoModel->findConOrganizador($id);
        $this->jsonSuccess([
            'id'      => $id,
            'torneo'  => $torneo ? $this->normalizar($torneo) : null,
            'mensaje' => $publicar
                ? 'Torneo creado y publicado. Ya acepta inscripciones.'
                : 'Torneo guardado como borrador.',
        ]);
    }

    /**
     * Valida una fecha en formato YYYY-MM-DD (el que envía <input type="date">),
     * rechazando fechas inexistentes como 2026-02-31.
     */
    private function esFechaValida(string $fecha): bool {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }

    /**
     * Normaliza una fila de torneo para el cliente: los contadores y flags
     * llegan de MySQL como strings según cómo se haya ejecutado la consulta,
     * y el front hace comparaciones estrictas con ellos.
     *
     * @param array $torneo
     * @return array
     */
    private function normalizar(array $torneo): array {
        foreach (['id', 'organizador_id', 'max_participantes', 'publico', 'requiere_equipos',
                  'inscriptos', 'equipos', 'partidos', 'partidos_jugados'] as $campo) {
            if (isset($torneo[$campo])) {
                $torneo[$campo] = (int) $torneo[$campo];
            }
        }
        return $torneo;
    }

    /**
     * Carga el resultado de un partido (POST /api/torneo/resultado).
     * Requiere rol organizador o administrador.
     *
     * El ganador se deduce del marcador (no se acepta del cliente, que podría
     * mandar un id ajeno al partido) y, al terminar, se rehace la tabla de
     * posiciones para que quede al día sin intervención manual.
     */
    public function cargarResultado(): void {
        if (!$this->requireApiRole(['organizador', 'administrador'])) {
            return;
        }

        $partidoId      = filter_input(INPUT_POST, 'partido_id',      FILTER_VALIDATE_INT);
        $golesLocal     = filter_input(INPUT_POST, 'goles_local',     FILTER_VALIDATE_INT);
        $golesVisitante = filter_input(INPUT_POST, 'goles_visitante', FILTER_VALIDATE_INT);

        if (!$partidoId || $golesLocal === null || $golesVisitante === null
            || $golesLocal === false || $golesVisitante === false
            || $golesLocal < 0 || $golesVisitante < 0) {
            $this->jsonError('Datos de resultado inválidos.');
            return;
        }

        // Control de propiedad: un organizador solo puede cargar resultados de
        // partidos de SUS torneos. El administrador puede cargar cualquiera.
        $organizadorDelPartido = $this->torneoModel->getOrganizadorDePartido($partidoId);
        if ($organizadorDelPartido === null) {
            http_response_code(404);
            $this->jsonError('Partido no encontrado.');
            return;
        }
        if (Session::getUserRole() !== 'administrador'
            && $organizadorDelPartido !== Session::getUserId()) {
            http_response_code(403);
            $this->jsonError('No tenés permiso para cargar resultados de este torneo.');
            return;
        }

        $partidoModel = new Partido();
        $partido      = $partidoModel->findById($partidoId);
        $ganadorId    = null;
        if ($golesLocal > $golesVisitante) {
            $ganadorId = (int) $partido['local_id'];
        } elseif ($golesVisitante > $golesLocal) {
            $ganadorId = (int) $partido['visitante_id'];
        }

        $id = $this->resultadoModel->cargar(
            $partidoId, $golesLocal, $golesVisitante, $ganadorId, Session::getUserId()
        );

        $torneoId = (int) $partido['torneo_id'];
        (new Posicion())->recalcular($torneoId);

        $this->jsonSuccess([
            'resultado_id' => $id,
            'posiciones'   => $this->torneoModel->getPosiciones($torneoId),
            'partidos'     => $partidoModel->listarPorTorneo($torneoId),
        ]);
    }
}
