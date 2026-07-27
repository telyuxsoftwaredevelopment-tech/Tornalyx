<?php
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Avisos: novedades del torneo que funcionan como notificaciones
 * para los participantes (cambios de horario, fixture publicado, resultados,
 * inscripciones abiertas y mensajes del organizador).
 */
class Aviso extends Model {

    protected string $table = 'avisos';

    /**
     * Publica un aviso. $torneoId null = aviso global del sistema.
     *
     * @return int ID del aviso.
     */
    public function publicar(?int $torneoId, ?int $autorId, string $tipo, string $titulo, ?string $cuerpo = null): int {
        return $this->insert([
            'torneo_id' => $torneoId,
            'autor_id'  => $autorId,
            'tipo'      => $tipo,
            'titulo'    => $titulo,
            'cuerpo'    => $cuerpo,
        ]);
    }

    /**
     * Avisos de un torneo, del más reciente al más antiguo.
     *
     * @return array
     */
    public function listarPorTorneo(int $torneoId, int $limite = 20): array {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.tipo, a.titulo, a.cuerpo, a.created_at,
                    TRIM(CONCAT(u.nombre, \' \', COALESCE(u.apellido, \'\'))) AS autor
               FROM avisos a
               LEFT JOIN usuarios u ON u.id = a.autor_id
              WHERE a.torneo_id = ?
              ORDER BY a.created_at DESC
              LIMIT ' . (int) $limite
        );
        $stmt->execute([$torneoId]);
        return $stmt->fetchAll();
    }

    /**
     * Avisos dirigidos a un usuario: los de los torneos en los que participa
     * (o que organiza) más los globales, con marca de leído.
     *
     * @return array
     */
    public function paraUsuario(int $usuarioId, int $limite = 30): array {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.tipo, a.titulo, a.cuerpo, a.created_at,
                    t.id AS torneo_id, t.nombre AS torneo,
                    (l.usuario_id IS NOT NULL) AS leido
               FROM avisos a
               LEFT JOIN torneos t ON t.id = a.torneo_id
               LEFT JOIN avisos_leidos l ON l.aviso_id = a.id AND l.usuario_id = ?
              WHERE a.torneo_id IS NULL
                 OR t.organizador_id = ?
                 OR EXISTS (SELECT 1 FROM inscripciones i
                             WHERE i.torneo_id = a.torneo_id AND i.usuario_id = ?)
              ORDER BY a.created_at DESC
              LIMIT ' . (int) $limite
        );
        $stmt->execute([$usuarioId, $usuarioId, $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Cantidad de avisos sin leer de un usuario (badge del navbar).
     */
    public function sinLeer(int $usuarioId): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
               FROM avisos a
               LEFT JOIN torneos t ON t.id = a.torneo_id
              WHERE NOT EXISTS (SELECT 1 FROM avisos_leidos l
                                 WHERE l.aviso_id = a.id AND l.usuario_id = ?)
                AND ( a.torneo_id IS NULL
                   OR t.organizador_id = ?
                   OR EXISTS (SELECT 1 FROM inscripciones i
                               WHERE i.torneo_id = a.torneo_id AND i.usuario_id = ?) )'
        );
        $stmt->execute([$usuarioId, $usuarioId, $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca como leídos todos los avisos visibles para el usuario.
     */
    public function marcarLeidos(int $usuarioId): void {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO avisos_leidos (aviso_id, usuario_id)
             SELECT a.id, ?
               FROM avisos a
               LEFT JOIN torneos t ON t.id = a.torneo_id
              WHERE a.torneo_id IS NULL
                 OR t.organizador_id = ?
                 OR EXISTS (SELECT 1 FROM inscripciones i
                             WHERE i.torneo_id = a.torneo_id AND i.usuario_id = ?)'
        );
        $stmt->execute([$usuarioId, $usuarioId, $usuarioId]);
    }
}
