<?php
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Usuario. Gestiona registro, autenticación y perfil.
 */
class Usuario extends Model {

    protected string $table = 'usuarios';

    /**
     * Busca un usuario por email.
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE email = ? AND estado != "suspendido" LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Registra un nuevo usuario con la contraseña hasheada.
     *
     * @param string $nombre
     * @param string $apellido
     * @param string $email
     * @param string $password   Contraseña en texto plano.
     * @param string $fechaNac   YYYY-MM-DD
     * @param string $rol        'participante' | 'organizador'
     * @return int ID del nuevo usuario.
     */
    public function registrar(
        string $nombre,
        string $apellido,
        string $email,
        string $password,
        string $fechaNac,
        string $rol = 'participante'
    ): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->insert([
            'nombre'    => $nombre,
            'apellido'  => $apellido,
            'email'     => $email,
            'password'  => $hash,
            'fecha_nac' => $fechaNac,
            'rol'       => $rol,
        ]);
    }

    /**
     * Verifica credenciales de login.
     *
     * @param string $email
     * @param string $password
     * @return array|null Usuario si válido, null si no.
     */
    public function verificarCredenciales(string $email, string $password): ?array {
        $usuario = $this->findByEmail($email);
        if (!$usuario) return null;
        if (!password_verify($password, $usuario['password'])) return null;
        return $usuario;
    }

    /**
     * Retorna lista de usuarios filtrada por rol (sin exponer passwords).
     *
     * @param string|null $rol
     * @return array
     */
    public function listar(?string $rol = null): array {
        if ($rol) {
            $stmt = $this->db->prepare(
                'SELECT id, nombre, apellido, email, rol, estado, created_at
                   FROM usuarios WHERE rol = ?'
            );
            $stmt->execute([$rol]);
        } else {
            $stmt = $this->db->query(
                'SELECT id, nombre, apellido, email, rol, estado, created_at
                   FROM usuarios'
            );
        }
        return $stmt->fetchAll();
    }
}
