-- ============================================================
-- Migración: avatar guardado en la base en vez de en disco
-- Render free no tiene disco persistente: cualquier archivo escrito en
-- frontend/uploads se pierde en el próximo redeploy. El avatar pasa a
-- vivir como BLOB en la propia fila del usuario y se sirve dinámicamente
-- desde GET /api/perfil/avatar/{id} (PerfilController::servirAvatar).
-- ============================================================

USE tornalyx_db;

ALTER TABLE usuarios
    ADD COLUMN avatar_data MEDIUMBLOB  NULL DEFAULT NULL AFTER avatar_url,
    ADD COLUMN avatar_mime VARCHAR(30) NULL DEFAULT NULL AFTER avatar_data;

-- Se registra a sí misma (ver add_schema_migrations.sql). El CREATE TABLE
-- de acá abajo es defensivo por si esta migración se corre antes que esa.
CREATE TABLE IF NOT EXISTS schema_migrations (
    filename    VARCHAR(180) NOT NULL PRIMARY KEY,
    applied_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO schema_migrations (filename) VALUES ('add_avatar_blob.sql');
