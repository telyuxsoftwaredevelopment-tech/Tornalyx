-- ============================================================
-- TORNALYX SGDM — DCL (Data Control Language)
-- Usuarios de MySQL con privilegios mínimos por función.
--
-- Requisito: Fullstack segunda entrega ("Configuración de usuarios de BD
-- con las restricciones pertinentes" / "DCL implementado").
--
-- Separación por lenguaje SQL (además de por función): los tres usuarios
-- de aplicación quedan divididos exactamente en lo que cada uno de DDL,
-- DCL y DML necesita, sin superposición:
--   tornalyx_ddl  — solo Data Definition Language (CREATE/ALTER/DROP/INDEX),
--                   más SELECT/INSERT acotado a la tabla schema_migrations
--                   (el registro de migraciones aplicadas). No puede leer ni
--                   escribir una sola fila de datos de negocio.
--   tornalyx_dcl  — solo Data Control Language (GRANT/REVOKE/CREATE USER).
--                   No puede tocar estructura ni datos directamente; su
--                   trabajo es administrar los permisos de los demás.
--   tornalyx_dml  — solo Data Manipulation Language (SELECT/INSERT/UPDATE/
--                   DELETE). Es el usuario que usa la app en runtime.
--
-- Corresponde al SERVIDOR REAL (bare-metal / VM administrado por
-- admin_tornalyx), no al docker-compose.yml de desarrollo: ahí
-- MYSQL_USER/MYSQL_PASSWORD reciben privilegios completos sobre la base
-- por defecto de la imagen oficial de MySQL, y eso es intencional
-- únicamente para desarrollo local (ver comentario en docker-compose.yml).
--
-- Uso:
--   1. Reemplazar los placeholders CAMBIAR_* por contraseñas fuertes
--      generadas (ver política de contraseñas: mínimo 16 caracteres para
--      cuentas de administración, nunca reutilizadas).
--   2. Ejecutar como root de MySQL:
--        mysql -u root -p < dcl.sql
--   3. Cargar cada contraseña en la variable de entorno que le corresponde
--      (nunca en el código, nunca todas en el mismo .env):
--        - tornalyx_dml -> DB_PASS del .env de la app
--          (SGDM/backend/config/database.php).
--        - tornalyx_ddl -> DB_DDL_PASS, exportada a mano antes de correr
--          scripts/servidor/desplegar.sh (o 'desplegar.sh solo-migrar');
--          nunca en el .env de la app: la app en runtime no debe poder
--          tocar el esquema.
--        - tornalyx_dcl -> uso exclusivamente manual por admin_tornalyx,
--          igual que root hoy. No vive en ningún .env ni la usa ningún
--          script; es la cuenta para dar de alta o ajustar el resto de
--          los usuarios sin repartir la contraseña de root.
--   4. Poner DB_AUTO_MIGRATE=0 en el .env de la app. Con el valor por
--      defecto (1) la app aplica sola las migraciones al conectarse, cosa
--      que en este servidor es imposible por diseño: tornalyx_dml no tiene
--      DDL. Acá el esquema lo aplica desplegar.sh con tornalyx_ddl.
--
-- Cada usuario está atado a 'localhost' porque la app, el backup y el
-- monitoreo corren en el mismo host que MySQL. Si en algún despliegue la
-- base vive en otro host, reemplazar 'localhost' por la IP del cliente
-- (nunca usar '%' salvo necesidad justificada).
-- ============================================================

-- ──────────────────────────────────────────────────────────────
-- tornalyx_ddl — usuario DDL: aplica el esquema y las migraciones
-- (SGDM/backend/database/migrations/*.sql) sobre tornalyx_db. Lo usa
-- únicamente scripts/servidor/desplegar.sh al desplegar (variables
-- DB_DDL_USER/DB_DDL_PASS, jamás las credenciales de la app). Solo
-- estructura: sin SELECT/INSERT/UPDATE/DELETE sobre las tablas de negocio,
-- no puede leer ni escribir un dato.
-- ──────────────────────────────────────────────────────────────
CREATE USER IF NOT EXISTS 'tornalyx_ddl'@'localhost' IDENTIFIED BY 'CAMBIAR_PASSWORD_DDL';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'tornalyx_ddl'@'localhost';
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES ON tornalyx_db.* TO 'tornalyx_ddl'@'localhost';

-- Única excepción al "sin DML", y acotada a UNA tabla: el mecanismo de
-- migraciones lleva su propio registro en schema_migrations, y tanto
-- schema.sql como cada add_*.sql terminan insertándose a sí mismos ahí con
-- INSERT IGNORE. Sin este permiso tornalyx_ddl no puede aplicar ni un solo
-- archivo. Al ser un GRANT a nivel de TABLA (no de base), sigue sin poder
-- leer ni escribir una fila de datos de negocio.
--
-- Un GRANT a nivel de tabla exige que la tabla ya exista (MySQL y MariaDB lo
-- rechazan con ERROR 1146 si no está), y este archivo corre ANTES de que
-- desplegar.sh aplique el esquema. Por eso se crean acá la base y esa única
-- tabla, vacías: schema.sql las vuelve a declarar con IF NOT EXISTS y es
-- quien las rellena, así que esto no duplica ni pisa nada.
CREATE DATABASE IF NOT EXISTS tornalyx_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tornalyx_db.schema_migrations (
    filename    VARCHAR(180) NOT NULL PRIMARY KEY,
    applied_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    error       VARCHAR(500) NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

GRANT SELECT, INSERT ON tornalyx_db.schema_migrations TO 'tornalyx_ddl'@'localhost';

-- Las migraciones que además traen backfill de datos (hoy solo
-- add_rol_torneo_simplificado.sql, con su UPDATE sobre usuarios) quedan
-- deliberadamente fuera del alcance de este usuario: desplegar.sh las
-- detecta, no las aplica y avisa para correrlas con tornalyx_dcl, que sí
-- tiene DML sobre tornalyx_db.

-- ──────────────────────────────────────────────────────────────
-- tornalyx_dcl — usuario DCL: administra permisos y cuentas del resto de
-- los usuarios de la aplicación (GRANT/REVOKE/CREATE USER). Uso
-- EXCLUSIVAMENTE manual por admin_tornalyx, igual que root hoy: sus
-- credenciales no viven en ningún .env ni las usa ningún script.
--
-- Solo puede otorgar (WITH GRANT OPTION) exactamente los privilegios que
-- él mismo tiene: nada sobre otras bases, sin SUPER/FILE/SHUTDOWN/RELOAD.
-- CREATE USER y PROCESS son privilegios globales en MySQL (no se pueden
-- acotar a una base), por eso van sobre *.*; el resto queda limitado a
-- tornalyx_db.
-- ──────────────────────────────────────────────────────────────
CREATE USER IF NOT EXISTS 'tornalyx_dcl'@'localhost' IDENTIFIED BY 'CAMBIAR_PASSWORD_DCL';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'tornalyx_dcl'@'localhost';
GRANT CREATE USER ON *.* TO 'tornalyx_dcl'@'localhost' WITH GRANT OPTION;
GRANT PROCESS ON *.* TO 'tornalyx_dcl'@'localhost' WITH GRANT OPTION;
GRANT SELECT, INSERT, UPDATE, DELETE,
      CREATE, ALTER, DROP, INDEX, REFERENCES,
      LOCK TABLES, SHOW VIEW, EVENT, TRIGGER
  ON tornalyx_db.* TO 'tornalyx_dcl'@'localhost' WITH GRANT OPTION;

-- ──────────────────────────────────────────────────────────────
-- tornalyx_dml — usuario DML: la aplicación en runtime
-- (SGDM/backend/config/database.php). Solo CRUD sobre las tablas de negocio, sin
-- DDL (CREATE/ALTER/DROP) ni acceso a otras bases o tablas de sistema.
-- ──────────────────────────────────────────────────────────────
CREATE USER IF NOT EXISTS 'tornalyx_dml'@'localhost' IDENTIFIED BY 'CAMBIAR_PASSWORD_DML';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'tornalyx_dml'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON tornalyx_db.* TO 'tornalyx_dml'@'localhost';

-- ──────────────────────────────────────────────────────────────
-- tornalyx_monitor — usuario de solo lectura para monitoreo_bd.sh
-- (variables DB_MONITOR_USER/DB_MONITOR_PASS). PROCESS es un privilegio
-- global (no se puede limitar a una base), pero solo permite VER los
-- procesos en ejecución, no matarlos ni modificarlos.
-- ──────────────────────────────────────────────────────────────
CREATE USER IF NOT EXISTS 'tornalyx_monitor'@'localhost' IDENTIFIED BY 'CAMBIAR_PASSWORD_MONITOR';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'tornalyx_monitor'@'localhost';
GRANT SELECT ON tornalyx_db.* TO 'tornalyx_monitor'@'localhost';
GRANT PROCESS ON *.* TO 'tornalyx_monitor'@'localhost';
GRANT SELECT ON mysql.slow_log TO 'tornalyx_monitor'@'localhost';

-- ──────────────────────────────────────────────────────────────
-- tornalyx_backup — usuario para mysqldump (variables DB_BACKUP_USER/
-- DB_BACKUP_PASS), usado por el usuario de sistema backup_tornalyx (ver
-- docs/entrega2/analisis-usuarios-sistema.md y respaldo.sh). Solo lectura
-- + LOCK TABLES/SHOW VIEW, imprescindibles para un dump consistente; sin
-- permisos de escritura.
-- ──────────────────────────────────────────────────────────────
CREATE USER IF NOT EXISTS 'tornalyx_backup'@'localhost' IDENTIFIED BY 'CAMBIAR_PASSWORD_BACKUP';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'tornalyx_backup'@'localhost';
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON tornalyx_db.* TO 'tornalyx_backup'@'localhost';

-- ──────────────────────────────────────────────────────────────
-- tornalyx_dev — usuario de desarrollo, acotado a la base tornalyx_dev
-- (nunca a tornalyx_db de producción). Usado por dev_tornalyx (ver
-- docs/entrega2/analisis-usuarios-sistema.md: "Acceso a MySQL de desarrollo").
-- Sí puede crear/alterar tablas dentro de SU base para poder iterar el
-- esquema y correr migraciones/seeders de prueba.
-- ──────────────────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS tornalyx_dev
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'tornalyx_dev'@'localhost' IDENTIFIED BY 'CAMBIAR_PASSWORD_DEV';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'tornalyx_dev'@'localhost';
GRANT ALL PRIVILEGES ON tornalyx_dev.* TO 'tornalyx_dev'@'localhost';

FLUSH PRIVILEGES;

-- ──────────────────────────────────────────────────────────────
-- Verificación rápida de lo aplicado:
--   SHOW GRANTS FOR 'tornalyx_ddl'@'localhost';
--   SHOW GRANTS FOR 'tornalyx_dcl'@'localhost';
--   SHOW GRANTS FOR 'tornalyx_dml'@'localhost';
--   SHOW GRANTS FOR 'tornalyx_monitor'@'localhost';
--   SHOW GRANTS FOR 'tornalyx_backup'@'localhost';
--   SHOW GRANTS FOR 'tornalyx_dev'@'localhost';
-- ──────────────────────────────────────────────────────────────
