<?php
require_once __DIR__ . '/Model.php';

/**
 * Autorización de acceso a documentación restringida.
 *
 * Cada fila representa la solicitud de un usuario para una materia sensible
 * (p. ej. 'ciberseguridad') y su estado: pendiente, aprobado o rechazado. Un
 * administrador la resuelve desde el panel. El flujo es "el usuario solicita →
 * el admin aprueba".
 */
class DocAcceso extends Model {

    protected string $table = 'doc_acceso';

    /**
     * Estado de autorización de un usuario para una materia.
     *
     * @return string|null 'pendiente'|'aprobado'|'rechazado', o null si nunca solicitó.
     */
    public function estado(int $usuarioId, string $materia): ?string {
        $stmt = $this->db->prepare(
            'SELECT estado FROM doc_acceso WHERE usuario_id = ? AND materia = ?'
        );
        $stmt->execute([$usuarioId, $materia]);
        $row = $stmt->fetch();
        return $row ? (string) $row['estado'] : null;
    }

    /**
     * Registra (o reabre) una solicitud de acceso. Si no existía, la crea como
     * pendiente; si estaba rechazada, la vuelve a poner pendiente; si ya estaba
     * pendiente o aprobada, no cambia nada.
     */
    public function solicitar(int $usuarioId, string $materia): void {
        $stmt = $this->db->prepare(
            'INSERT INTO doc_acceso (usuario_id, materia, estado, solicitado_at)
                  VALUES (?, ?, \'pendiente\', NOW())
             ON DUPLICATE KEY UPDATE
                  estado        = IF(estado = \'rechazado\', \'pendiente\', estado),
                  solicitado_at = IF(estado = \'rechazado\', NOW(), solicitado_at),
                  resuelto_at   = IF(estado = \'rechazado\', NULL, resuelto_at),
                  resuelto_por  = IF(estado = \'rechazado\', NULL, resuelto_por)'
        );
        $stmt->execute([$usuarioId, $materia]);
    }

    /**
     * Lista las solicitudes con los datos del usuario, para el panel de admin.
     * Ordena primero las pendientes y, dentro de cada grupo, las más nuevas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarSolicitudes(): array {
        $sql = 'SELECT a.id, a.usuario_id, a.materia, a.estado,
                       a.solicitado_at, a.resuelto_at,
                       u.nombre, u.apellido, u.email
                  FROM doc_acceso a
                  JOIN usuarios u ON u.id = a.usuario_id
              ORDER BY (a.estado = \'pendiente\') DESC, a.solicitado_at DESC';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Resuelve una solicitud por su id: la aprueba o la rechaza y registra quién
     * y cuándo. Devuelve el nuevo estado, o null si la acción es inválida.
     */
    public function resolverPorId(int $id, string $accion, int $adminId): ?string {
        $estado = match ($accion) {
            'aprobar'  => 'aprobado',
            'rechazar' => 'rechazado',
            default    => null,
        };
        if ($estado === null) {
            return null;
        }
        $stmt = $this->db->prepare(
            'UPDATE doc_acceso
                SET estado = ?, resuelto_at = NOW(), resuelto_por = ?
              WHERE id = ?'
        );
        $stmt->execute([$estado, $adminId, $id]);
        return $estado;
    }
}
