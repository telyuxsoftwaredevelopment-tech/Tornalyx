<?php
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Resultado. Registra y consulta resultados de partidos.
 */
class Resultado extends Model {

    protected string $table = 'resultados';

    /**
     * Carga el resultado de un partido y actualiza posiciones.
     *
     * @param int      $partidoId
     * @param int      $golesLocal
     * @param int      $golesVisitante
     * @param int|null $ganadorId    NULL = empate
     * @param int      $cargadoPor   usuario_id
     * @return int ID del resultado insertado.
     */
    public function cargar(
        int $partidoId,
        int $golesLocal,
        int $golesVisitante,
        ?int $ganadorId,
        int $cargadoPor
    ): int {
        $id = $this->insert([
            'partido_id'      => $partidoId,
            'goles_local'     => $golesLocal,
            'goles_visitante' => $golesVisitante,
            'ganador_id'      => $ganadorId,
            'cargado_por'     => $cargadoPor,
        ]);

        // Marcar partido como finalizado
        $this->db->prepare(
            "UPDATE partidos SET estado = 'finalizado' WHERE id = ?"
        )->execute([$partidoId]);

        return $id;
    }

    /**
     * Obtiene resultados de un torneo con datos de partido.
     *
     * @param int $torneoId
     * @return array
     */
    public function listarPorTorneo(int $torneoId): array {
        // Resuelve los nombres de local y visitante (equipo o usuario, según el
        // tipo del partido) para mostrar resultados legibles en el detalle.
        $stmt = $this->db->prepare(
            'SELECT r.*, p.local_id, p.visitante_id, p.fecha_partido, p.tipo_contendiente,
                    ro.numero AS ronda, ro.nombre AS ronda_nombre,
                    COALESCE(el.nombre, TRIM(CONCAT(ul.nombre, \' \', COALESCE(ul.apellido, \'\')))) AS local_nombre,
                    COALESCE(ev.nombre, TRIM(CONCAT(uv.nombre, \' \', COALESCE(uv.apellido, \'\')))) AS visitante_nombre
               FROM resultados r
               INNER JOIN partidos p  ON p.id = r.partido_id
               INNER JOIN rondas   ro ON ro.id = p.ronda_id
               LEFT JOIN equipos  el ON p.tipo_contendiente = \'equipo\'  AND el.id = p.local_id
               LEFT JOIN usuarios ul ON p.tipo_contendiente = \'usuario\' AND ul.id = p.local_id
               LEFT JOIN equipos  ev ON p.tipo_contendiente = \'equipo\'  AND ev.id = p.visitante_id
               LEFT JOIN usuarios uv ON p.tipo_contendiente = \'usuario\' AND uv.id = p.visitante_id
               WHERE p.torneo_id = ?
               ORDER BY ro.numero ASC, p.id ASC'
        );
        $stmt->execute([$torneoId]);
        return $stmt->fetchAll();
    }
}
