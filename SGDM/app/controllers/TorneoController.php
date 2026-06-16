<?php
require_once __DIR__ . '/../models/Torneo.php';
require_once __DIR__ . '/../models/Resultado.php';
require_once __DIR__ . '/../helpers/Session.php';

/**
 * Controlador de torneos.
 * Maneja listado, creación, edición, resultados y posiciones.
 */
class TorneoController {

    private Torneo    $torneoModel;
    private Resultado $resultadoModel;

    public function __construct() {
        $this->torneoModel    = new Torneo();
        $this->resultadoModel = new Resultado();
    }

    /**
     * Lista torneos públicos con filtros (GET /torneos).
     */
    public function index(): void {
        $filtros = [
            'estado'     => filter_input(INPUT_GET, 'estado',     FILTER_DEFAULT) ?? '',
            'disciplina' => filter_input(INPUT_GET, 'disciplina', FILTER_DEFAULT) ?? '',
            'formato'    => filter_input(INPUT_GET, 'formato',    FILTER_DEFAULT) ?? '',
        ];
        $torneos = $this->torneoModel->listarPublicos(array_filter($filtros));
        $this->jsonSuccess(['torneos' => $torneos]);
    }

    /**
     * Detalle de un torneo (GET /torneo/{id}).
     *
     * @param int $id
     */
    public function show(int $id): void {
        $torneo = $this->torneoModel->findById($id);
        if (!$torneo || (!$torneo['publico'] && !Session::isLoggedIn())) {
            http_response_code(404);
            $this->jsonError('Torneo no encontrado.');
            return;
        }
        $posiciones = $this->torneoModel->getPosiciones($id);
        $resultados = $this->resultadoModel->listarPorTorneo($id);
        $this->jsonSuccess([
            'torneo'     => $torneo,
            'posiciones' => $posiciones,
            'resultados' => $resultados,
        ]);
    }

    /**
     * Crea un nuevo torneo (POST /torneo/crear).
     * Requiere rol organizador o administrador.
     */
    public function store(): void {
        Session::requireRole(['organizador', 'administrador']);

        $nombre      = trim(filter_input(INPUT_POST, 'nombre',      FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $disciplina  = trim(filter_input(INPUT_POST, 'disciplina',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $formato     = filter_input(INPUT_POST, 'formato',   FILTER_DEFAULT) ?? '';
        $maxPart     = filter_input(INPUT_POST, 'max_participantes', FILTER_VALIDATE_INT) ?: 16;
        $fechaInicio = filter_input(INPUT_POST, 'fecha_inicio', FILTER_DEFAULT) ?? '';
        $fechaFin    = filter_input(INPUT_POST, 'fecha_fin',    FILTER_DEFAULT) ?? '';

        if (empty($nombre) || empty($disciplina) || empty($formato)) {
            $this->jsonError('Nombre, disciplina y formato son obligatorios.');
            return;
        }

        $formatosValidos = ['liga', 'eliminacion_directa', 'suizo', 'grupos_playoff'];
        if (!in_array($formato, $formatosValidos, true)) {
            $this->jsonError('Formato de torneo inválido.');
            return;
        }

        $id = $this->torneoModel->insert([
            'organizador_id'   => Session::getUserId(),
            'nombre'           => $nombre,
            'descripcion'      => $descripcion,
            'disciplina'       => $disciplina,
            'formato'          => $formato,
            'max_participantes'=> $maxPart,
            'fecha_inicio'     => $fechaInicio ?: null,
            'fecha_fin'        => $fechaFin    ?: null,
            'estado'           => 'borrador',
            'publico'          => 1,
        ]);

        $this->jsonSuccess(['id' => $id]);
    }

    /**
     * Carga el resultado de un partido (POST /torneo/resultado).
     * Requiere rol organizador o administrador.
     */
    public function cargarResultado(): void {
        Session::requireRole(['organizador', 'administrador']);

        $partidoId      = filter_input(INPUT_POST, 'partido_id',      FILTER_VALIDATE_INT);
        $golesLocal     = filter_input(INPUT_POST, 'goles_local',     FILTER_VALIDATE_INT);
        $golesVisitante = filter_input(INPUT_POST, 'goles_visitante', FILTER_VALIDATE_INT);

        if (!$partidoId || $golesLocal === null || $golesVisitante === null) {
            $this->jsonError('Datos de resultado inválidos.');
            return;
        }

        $ganadorId = null;
        if ($golesLocal > $golesVisitante) {
            $ganadorId = filter_input(INPUT_POST, 'local_id', FILTER_VALIDATE_INT);
        } elseif ($golesVisitante > $golesLocal) {
            $ganadorId = filter_input(INPUT_POST, 'visitante_id', FILTER_VALIDATE_INT);
        }

        $id = $this->resultadoModel->cargar(
            $partidoId, $golesLocal, $golesVisitante, $ganadorId, Session::getUserId()
        );
        $this->jsonSuccess(['resultado_id' => $id]);
    }

    // ──────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────

    private function jsonSuccess(array $data = []): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, ...$data]);
    }

    private function jsonError(string $mensaje): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $mensaje]);
    }
}
