-- ============================================================
-- Migración: campos de perfil público del usuario
-- Agrega la biografía breve (máx. 500 palabras, validado en el
-- backend) y la ubicación que se muestran en /perfil.
-- avatar_url ya existía en el esquema original.
-- ============================================================

USE tornalyx_db;

ALTER TABLE usuarios
    ADD COLUMN bio       TEXT         NULL DEFAULT NULL AFTER avatar_url,
    ADD COLUMN ubicacion VARCHAR(120) NULL DEFAULT NULL AFTER bio;
