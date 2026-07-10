-- ============================================================
-- TORNALYX SGDM – Migración: baja del MFA por WhatsApp
-- Revierte add_whatsapp_mfa.sql. Ejecutar SOLO en bases que ya
-- habían aplicado esa migración (el 2FA pasa a ser solo por email).
-- En una base creada con el schema.sql actual la columna ya no existe.
-- ============================================================

USE tornalyx_db;

-- MySQL no soporta "DROP COLUMN IF EXISTS"; si la columna ya no está,
-- esta sentencia da error y se puede ignorar.
ALTER TABLE usuarios
    DROP COLUMN telefono;
