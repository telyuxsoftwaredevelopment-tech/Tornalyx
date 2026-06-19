<?php
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Torneo. Gestiona CRUD de torneos y consultas relacionadas.
 */
class Torneo extends Model {

    protected string $table = 'torneos';

    /**
     * Lista torneos públicos con datos del organizador.
     *
     * @param array $filtros  ['estado'=>..., 'disciplina'=>..., 'formato'=>...]
     * @return array
     */
    public function listarPublicos(array $filtros = []): array {
        $sql    = 'SELECT t.*, u.nombre AS org_nombre, u.apellido AS org_apellido
                   FROM torneos t
                   INNER JOIN usuarios u ON u.id = t.organizador_id
                   WHERE t.publico = 1';
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= ' AND t.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['disciplina'])) {
            $sql .= ' AND t.disciplina = ?';
            $params[] = $filtros['disciplina'];
        }
        if (!empty($filtros['formato'])) {
            $sql .= ' AND t.formato = ?';
            $params[] = $filtros['formato'];
        }

        $sql .= ' ORDER BY t.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lista torneos de un organizador específico.
     *
     * @param int $organizadorId
     * @return array
     */
    public function listarDeOrganizador(int $organizadorId): array {
        $stmt = $this->db->prepare(
            'SELECT * FROM torneos WHERE organizador_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$organizadorId]);
        return $stmt->fetchAll();
    }

    /**
     * Cambia el estado de un torneo.
     *
     * @param int    $torneoId
     * @param string $nuevoEstado
     * @return bool
     */
    public function cambiarEstado(int $torneoId, string $nuevoEstado): bool {
        return $this->update($torneoId, ['estado' => $nuevoEstado]);
    }

    /**
     * Retorna el ID del organizador dueño del torneo al que pertenece un
     * partido, o null si el partido no existe.
     *
     * @param int $partidoId
     * @return int|null
     */
    public function getOrganizadorDePartido(int $partidoId): ?int {
        $stmt = $this->db->prepare(
            'SELECT t.organizador_id
               FROM partidos p
               INNER JOIN torneos t ON t.id = p.torneo_id
               WHERE p.id = ?'
        );
        $stmt->execute([$partidoId]);
        $row = $stmt->fetch();
        return $row ? (int) $row['organizador_id'] : null;
    }

    /**
     * Retorna la tabla de posiciones de un torneo.
     *
     * @param int $torneoId
     * @return array
     */
    public function getPosiciones(int $torneoId): array {
        $stmt = $this->db->prepare(
            'SELECT * FROM posiciones WHERE torneo_id = ? ORDER BY pts DESC, dg DESC'
        );
        $stmt->execute([$torneoId]);
        return $stmt->fetchAll();
    }
}
