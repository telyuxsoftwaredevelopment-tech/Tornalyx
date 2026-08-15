# Bloque A — Checklist de evidencia (Entrega 2, 24 pts)

Mapeo de cada criterio de la rúbrica a la evidencia concreta en el repo.
Las líneas citadas están confirmadas al momento de escribir este documento
(ver Task 1 del plan `2026-08-12-entrega2-bloque-a-seguridad.md`); si el
código se movió, volver a correr los `grep` de ese plan antes de entregar.

| # | Criterio | Estado | Evidencia | Nota |
|---|----------|--------|-----------|------|
| 1 | Validaciones en frontend y backend | ✅ | `SGDM/frontend/js/validations.js`; `SGDM/backend/controllers/AuthController.php` (líneas 181-197, 367-372) | Frontend valida para UX, backend revalida siempre (nunca confía en el cliente). Líneas re-verificadas el 13/08: el registro público ahora también valida longitud de nombre/apellido/email y rango de fecha de nacimiento (1900-hoy), agregado tras un bug real que rompía el registro con un año de 5 dígitos. |
| 2 | Encriptación de contraseñas | ✅ | `SGDM/backend/models/Usuario.php:45` (`password_hash(..., PASSWORD_BCRYPT, ['cost' => 12])`), `:74` (`password_verify` con hash dummy para tiempo constante) | Bcrypt costo 12, nunca se guarda ni se loguea texto plano. |
| 3 | Esquema de red con implementaciones de seguridad | ✅ | `docs/entrega2/esquema-red.md` | Ver Task 3 de este plan. |
| 4 | Seguridad de servidor (IDS, IPS, firewall, fail2ban) | ✅ | `docs/entrega2/seguridad-servidor.md`, `scripts/servidor/firewall-ufw.sh`, `scripts/servidor/fail2ban-tornalyx.local`, `scripts/servidor/instalar-seguridad.sh` | Ver Task 4 de este plan. Requiere aplicarse en el VPS real (no en Render). |
| 5 | Modelo relacional normalizado | ✅ | `docs/entrega2/modelo-relacional.md`; esquema real en `SGDM/backend/database/migrations/schema.sql` (15 tablas) | Ver Task 2 de este plan. |
| 6 | DCL implementado | ✅ | `SGDM/backend/database/dcl.sql` | 6 usuarios: `tornalyx_ddl`, `tornalyx_dcl`, `tornalyx_dml`, `tornalyx_monitor`, `tornalyx_backup`, `tornalyx_dev`. |
| 7 | Configuración de usuarios de BD con restricciones | ✅ | `SGDM/backend/database/dcl.sql` (mismo archivo que #6) | Cada usuario tiene privilegios mínimos por función (ver comentarios del archivo). |
| 8 | Modelos alineados al modelo relacional | ✅ | `SGDM/backend/models/*.php` (14 archivos, 13 sin contar `Model.php`) | 13 modelos de tablas de negocio heredan de `Model.php` (la base); tablas de unión simples (`avisos_leidos`, `asistencias`) se manejan directamente desde el modelo relacionado. **Nota (13/08):** la tabla `sesiones` del schema no tiene ningún modelo ni código que la lea/escriba — las sesiones reales son archivos PHP nativos en disco (`Session::start()`). Es esquema muerto, no un modelo faltante: no afecta este criterio, pero conviene mencionarlo si preguntan por esa tabla puntual. |
| 9 | Integración con PHP POO (gestión de usuarios funcionando) | ✅ | `SGDM/backend/models/Usuario.php`, `SGDM/backend/controllers/AuthController.php` | Registro, login, 2FA por email, edición de perfil — todo con clases (`Model`, `Controller` como base). |
| 10 | Implementación con Apache | ✅ | `Dockerfile:1-43`, `SGDM/docker/vhost.conf`, `SGDM/frontend/.htaccess` | Apache 2 vía imagen `php:8.2-apache`, `mod_rewrite` + `mod_headers`, CSP/HSTS/X-Frame-Options ya configurados. |
