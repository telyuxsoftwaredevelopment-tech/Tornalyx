-- ============================================================
-- TORNALYX SGDM – Datos de prueba (seed)
-- Ejecutar DESPUÉS de schema.sql
-- ============================================================

USE tornalyx_db;

-- ──────────────────────────────────────────────────────────────
-- Usuarios de prueba
-- Contraseñas: todos usan "Password123!" hasheado con bcrypt
-- Hash de ejemplo (para pruebas): $2y$12$...
-- ──────────────────────────────────────────────────────────────
INSERT INTO usuarios (nombre, apellido, email, password, fecha_nac, rol, estado) VALUES
('Admin',    'Sistema',  'admin@tornalyx.com',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1990-01-01', 'administrador', 'activo'),
('Carlos',   'García',   'carlos@ejemplo.com',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1995-03-15', 'organizador',   'activo'),
('María',    'López',    'maria@ejemplo.com',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1998-07-22', 'participante',  'activo'),
('Rodrigo',  'Martínez', 'rodrigo@ejemplo.com',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2000-11-05', 'participante',  'activo'),
('Sofía',    'Díaz',     'sofia@ejemplo.com',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1997-09-30', 'organizador',   'activo');

-- ──────────────────────────────────────────────────────────────
-- Torneos de prueba
-- ──────────────────────────────────────────────────────────────
INSERT INTO torneos (organizador_id, nombre, descripcion, disciplina, formato, estado, max_participantes, publico, fecha_inicio, fecha_fin) VALUES
(2, 'Copa Regional Fútbol 2026',   'Liga de fútbol amateur zona norte. Todos contra todos, 12 equipos.', 'Fútbol',  'liga',                 'en_curso',    12, 1, '2026-06-01', '2026-07-31'),
(2, 'Torneo Ajedrez Escolar 2026', 'Sistema suizo de 7 rondas. Categoría libre.',                       'Ajedrez', 'suizo',                'inscripcion', 32, 1, '2026-07-15', '2026-07-20'),
(5, 'Esports Cup – League of Legends', 'Eliminación directa. 16 equipos. Bo3 hasta semifinal.',        'Esports', 'eliminacion_directa',  'finalizado',  16, 1, '2026-05-01', '2026-05-15');

-- ──────────────────────────────────────────────────────────────
-- Equipos de prueba (torneo 1 - fútbol)
-- ──────────────────────────────────────────────────────────────
INSERT INTO equipos (capitan_id, torneo_id, nombre, estado) VALUES
(3, 1, 'Halcones FC',   'aprobado'),
(4, 1, 'Real Norte',    'aprobado'),
(3, 1, 'Atlético Sur',  'aprobado'),
(4, 1, 'Defensores',    'aprobado');

-- ──────────────────────────────────────────────────────────────
-- Inscripciones
-- ──────────────────────────────────────────────────────────────
INSERT INTO inscripciones (usuario_id, torneo_id, equipo_id, estado) VALUES
(3, 1, 1, 'aprobada'),
(4, 1, 2, 'aprobada'),
(3, 2, NULL, 'aprobada'),
(4, 2, NULL, 'pendiente');

-- ──────────────────────────────────────────────────────────────
-- Posiciones iniciales (torneo 1)
-- ──────────────────────────────────────────────────────────────
INSERT INTO posiciones (torneo_id, contendiente_id, tipo, pj, pg, pe, pp, gf, gc, dg, pts) VALUES
(1, 1, 'equipo', 7, 6, 1, 0, 22, 10, 12, 19),
(1, 2, 'equipo', 7, 5, 1, 1, 18, 10,  8, 16),
(1, 3, 'equipo', 7, 4, 1, 2, 15, 12,  3, 13),
(1, 4, 'equipo', 7, 3, 2, 2, 13, 14, -1, 11);
