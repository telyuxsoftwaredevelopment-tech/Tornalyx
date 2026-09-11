-- ============================================================
-- Migración: eliminar la tabla sesiones (esquema muerto)
--
-- Venía del diseño original, que preveía un session store manual en base de
-- datos. Nunca se implementó: las sesiones reales son archivos PHP nativos
-- en disco (Session::start(), backend/shared/Session.php), y ninguna línea
-- de código leyó ni escribió jamás esta tabla.
--
-- Se elimina para que el modelo relacional describa el sistema que existe.
-- Siempre estuvo vacía, así que no hay datos que migrar; el ON DELETE
-- CASCADE de su FK a usuarios tampoco arrastra nada.
-- ============================================================

USE tornalyx_db;

DROP TABLE IF EXISTS sesiones;

INSERT IGNORE INTO schema_migrations (filename) VALUES ('add_drop_sesiones.sql');
