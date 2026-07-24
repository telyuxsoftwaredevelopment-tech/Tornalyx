-- ============================================================
-- TORNALYX SGDM – Esquema de Base de Datos
-- Motor: MySQL 8.x
-- Codificación: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS tornalyx_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tornalyx_db;

-- ──────────────────────────────────────────────────────────────
-- TABLA: usuarios
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(60)  NOT NULL,
    apellido     VARCHAR(60)  NOT NULL,
    email        VARCHAR(120) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,          -- bcrypt hash
    fecha_nac    DATE         NOT NULL,
    rol          ENUM('participante','organizador','administrador') NOT NULL DEFAULT 'participante',
    estado       ENUM('activo','suspendido','pendiente') NOT NULL DEFAULT 'activo',
    avatar_url   VARCHAR(255) DEFAULT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_rol    (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: torneos
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS torneos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizador_id  INT UNSIGNED NOT NULL,
    nombre          VARCHAR(120) NOT NULL,
    descripcion     TEXT         DEFAULT NULL,
    disciplina      VARCHAR(60)  NOT NULL,
    formato         ENUM('liga','eliminacion_directa','suizo','grupos_playoff') NOT NULL,
    estado          ENUM('borrador','inscripcion','en_curso','finalizado','cancelado') NOT NULL DEFAULT 'borrador',
    max_participantes INT UNSIGNED NOT NULL DEFAULT 16,
    publico         TINYINT(1)   NOT NULL DEFAULT 1,
    banner_url      VARCHAR(255) DEFAULT NULL,
    fecha_inicio    DATE         DEFAULT NULL,
    fecha_fin       DATE         DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_torneo_organizador
        FOREIGN KEY (organizador_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_estado     (estado),
    INDEX idx_disciplina (disciplina),
    INDEX idx_org        (organizador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: equipos
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS equipos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    capitan_id  INT UNSIGNED NOT NULL,
    torneo_id   INT UNSIGNED NOT NULL,
    nombre      VARCHAR(80)  NOT NULL,
    estado      ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipo_capitan
        FOREIGN KEY (capitan_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_equipo_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_nombre_torneo (nombre, torneo_id),
    INDEX idx_torneo (torneo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: inscripciones  (participante individual ↔ torneo)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inscripciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    torneo_id       INT UNSIGNED NOT NULL,
    equipo_id       INT UNSIGNED DEFAULT NULL,
    estado          ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_inscripcion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inscripcion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_inscripcion_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    CONSTRAINT fk_inscripcion_equipo
        FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE SET NULL,
    UNIQUE KEY uq_usuario_torneo (usuario_id, torneo_id),
    INDEX idx_torneo (torneo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: rondas
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rondas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id   INT UNSIGNED NOT NULL,
    numero      TINYINT UNSIGNED NOT NULL,
    nombre      VARCHAR(60) DEFAULT NULL,          -- "Semifinal", "Ronda 3", etc.
    estado      ENUM('pendiente','en_curso','finalizada') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_ronda_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_torneo_ronda (torneo_id, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: partidos
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS partidos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id       INT UNSIGNED NOT NULL,
    ronda_id        INT UNSIGNED NOT NULL,
    local_id        INT UNSIGNED NOT NULL,          -- equipo o usuario
    visitante_id    INT UNSIGNED NOT NULL,
    tipo_contendiente ENUM('usuario','equipo') NOT NULL DEFAULT 'equipo',
    estado          ENUM('pendiente','en_curso','finalizado','aplazado') NOT NULL DEFAULT 'pendiente',
    fecha_partido   DATETIME DEFAULT NULL,
    lugar           VARCHAR(120) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_partido_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    CONSTRAINT fk_partido_ronda
        FOREIGN KEY (ronda_id) REFERENCES rondas(id) ON DELETE CASCADE,
    INDEX idx_torneo (torneo_id),
    INDEX idx_ronda  (ronda_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: resultados
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS resultados (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partido_id      INT UNSIGNED NOT NULL UNIQUE,
    goles_local     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    goles_visitante TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ganador_id      INT UNSIGNED DEFAULT NULL,      -- NULL = empate
    observaciones   TEXT DEFAULT NULL,
    cargado_por     INT UNSIGNED NOT NULL,          -- usuario_id del que cargó
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resultado_partido
        FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_resultado_cargado
        FOREIGN KEY (cargado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: posiciones  (caché de tabla de posiciones)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS posiciones (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    torneo_id       INT UNSIGNED NOT NULL,
    contendiente_id INT UNSIGNED NOT NULL,
    tipo            ENUM('usuario','equipo') NOT NULL,
    pj              SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- partidos jugados
    pg              SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- ganados
    pe              SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- empatados
    pp              SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- perdidos
    gf              SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- goles/puntos a favor
    gc              SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- goles/puntos en contra
    dg              SMALLINT          NOT NULL DEFAULT 0,  -- diferencia
    pts             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_posicion_torneo
        FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_torneo_contendiente (torneo_id, contendiente_id, tipo),
    INDEX idx_torneo_pts (torneo_id, pts DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: sesiones  (PHP session store manual)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sesiones (
    id          VARCHAR(128) PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    ip          VARCHAR(45)  NOT NULL,
    user_agent  VARCHAR(255) DEFAULT NULL,
    expires_at  TIMESTAMP    NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sesion_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: login_otps  (códigos de verificación 2FA por email)
-- Un código activo por usuario; se reemplaza en cada solicitud.
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS login_otps (
    usuario_id   INT UNSIGNED PRIMARY KEY,
    code_hash    VARCHAR(255) NOT NULL,            -- bcrypt del código de 6 dígitos
    expires_at   TIMESTAMP    NOT NULL,
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_sent_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_otp_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: doc_acceso  (autorización a documentación restringida)
-- Solicitud/estado por (usuario, materia); el admin aprueba/rechaza.
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS doc_acceso (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT UNSIGNED NOT NULL,
    materia       VARCHAR(40)  NOT NULL,
    estado        ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    solicitado_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resuelto_at   TIMESTAMP    NULL DEFAULT NULL,
    resuelto_por  INT UNSIGNED NULL DEFAULT NULL,
    CONSTRAINT fk_docacceso_usuario
        FOREIGN KEY (usuario_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_docacceso_resuelto
        FOREIGN KEY (resuelto_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uq_usuario_materia (usuario_id, materia),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- TABLA: doc_otps  (código OTP para ver un documento restringido)
-- Separada de login_otps; un código activo por (usuario, materia).
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS doc_otps (
    usuario_id   INT UNSIGNED NOT NULL,
    materia      VARCHAR(40)  NOT NULL,
    code_hash    VARCHAR(255) NOT NULL,
    expires_at   TIMESTAMP    NOT NULL,
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_sent_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, materia),
    CONSTRAINT fk_docotp_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
