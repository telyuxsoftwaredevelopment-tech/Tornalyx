-- ============================================================
-- TORNALYX SGDM – Migración: MFA por WhatsApp (campo teléfono)
-- Ejecutar sobre una base ya creada con schema.sql.
-- ============================================================

USE tornalyx_db;

-- Teléfono en formato internacional (E.164), p. ej. +59899123456.
-- Nullable para no romper filas existentes; en el registro nuevo es obligatorio.
ALTER TABLE usuarios
    ADD COLUMN telefono VARCHAR(20) DEFAULT NULL AFTER email;
