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
     * @param string $rol        'participante' | 'administrador'
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

        // Tiempo constante: si el usuario no existe, igual ejecutamos un
        // password_verify contra un hash dummy para no revelar (por diferencia
        // de tiempo de respuesta) qué correos están registrados.
        // Hash con formato bcrypt válido (60 chars) para que password_verify
        // realice el trabajo completo aun cuando el usuario no exista.
        $hash = $usuario['password']
            ?? '$2y$12$abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQ';

        $passwordOk = password_verify($password, $hash);

        if (!$usuario || !$passwordOk) {
            return null;
        }
        return $usuario;
    }

    /**
     * Indica si un email ya está en uso, opcionalmente ignorando un usuario
     * (para permitir que en una edición el usuario conserve su propio email).
     * A diferencia de findByEmail(), considera también cuentas suspendidas.
     *
     * @param string   $email
     * @param int|null $exceptoId Usuario a excluir de la comprobación.
     * @return bool
     */
    public function emailExiste(string $email, ?int $exceptoId = null): bool {
        $sql    = 'SELECT 1 FROM usuarios WHERE email = ?';
        $params = [$email];
        if ($exceptoId !== null) {
            $sql     .= ' AND id <> ?';
            $params[] = $exceptoId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Crea un usuario desde el panel de administración, permitiendo fijar rol
     * y estado (a diferencia de registrar(), pensado para el alta pública).
     *
     * @param array $d nombre, apellido, email, password, fecha_nac, rol, estado
     * @return int ID del nuevo usuario.
     */
    public function crearComoAdmin(array $d): int {
        $hash = password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->insert([
            'nombre'    => $d['nombre'],
            'apellido'  => $d['apellido'],
            'email'     => $d['email'],
            'password'  => $hash,
            'fecha_nac' => $d['fecha_nac'],
            'rol'       => $d['rol'],
            'estado'    => $d['estado'],
        ]);
    }

    /**
     * Actualiza los campos indicados de un usuario. Si viene 'password', se
     * hashea antes de guardar; si no viene, la contraseña queda intacta.
     *
     * @param int   $id
     * @param array $campos Subconjunto de columnas a actualizar.
     * @return bool
     */
    public function actualizar(int $id, array $campos): bool {
        if (isset($campos['password'])) {
            $campos['password'] = password_hash($campos['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        return $this->update($id, $campos);
    }

    /**
     * Cantidad de administradores activos. Sirve para impedir que se quede el
     * sistema sin ningún administrador operativo.
     *
     * @return int
     */
    public function contarAdministradoresActivos(): int {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador' AND estado = 'activo'"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Busca jugadores por nombre o apellido para el buscador público.
     * Devuelve solo datos que es seguro mostrar de un tercero.
     *
     * @param string $q     Texto a buscar (mínimo 2 caracteres, valida el controlador).
     * @param int    $limite
     * @return array
     */
    public function buscar(string $q, int $limite = 20): array {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, apellido, rol, avatar_url, ubicacion
               FROM usuarios
              WHERE estado = 'activo'
                AND (nombre LIKE ? OR apellido LIKE ? OR CONCAT(nombre, ' ', apellido) LIKE ?)
              ORDER BY nombre ASC
              LIMIT " . (int) $limite
        );
        $patron = '%' . $q . '%';
        $stmt->execute([$patron, $patron, $patron]);
        return $stmt->fetchAll();
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
                'SELECT id, nombre, apellido, email, rol, estado, fecha_nac, created_at
                   FROM usuarios WHERE rol = ?'
            );
            $stmt->execute([$rol]);
        } else {
            $stmt = $this->db->query(
                'SELECT id, nombre, apellido, email, rol, estado, fecha_nac, created_at
                   FROM usuarios'
            );
        }
        return $stmt->fetchAll();
    }
}
