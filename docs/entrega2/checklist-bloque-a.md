# Bloque A — Checklist de evidencia (Entrega 2, 24 pts)

Mapeo de cada criterio de la rúbrica a la evidencia concreta en el repo.
Las líneas citadas están confirmadas al momento de escribir este documento
(ver Task 1 del plan `2026-08-12-entrega2-bloque-a-seguridad.md`); si el
código se movió, volver a correr los `grep` de ese plan antes de entregar.

| # | Criterio | Estado | Evidencia | Nota |
|---|----------|--------|-----------|------|
| 1 | Validaciones en frontend y backend | ✅ | `SGDM/frontend/js/validations.js`; `SGDM/backend/controllers/AuthController.php` (líneas 177-191, 358-363) | Frontend valida para UX, backend revalida siempre (nunca confía en el cliente). |
| 2 | Encriptación de contraseñas | ✅ | `SGDM/backend/models/Usuario.php:45` (`password_hash(..., PASSWORD_BCRYPT, ['cost' => 12])`), `:74` (`password_verify` con hash dummy para tiempo constante) | Bcrypt costo 12, nunca se guarda ni se loguea texto plano. |
| 3 | Esquema de red con implementaciones de seguridad | ✅ | `docs/entrega2/esquema-red.md` | Ver Task 3 de este plan. |
| 4 | Seguridad de servidor (IDS, IPS, firewall, fail2ban) | ✅ | `docs/entrega2/seguridad-servidor.md`, `scripts/servidor/firewall-ufw.sh`, `scripts/servidor/fail2ban-tornalyx.local`, `scripts/servidor/instalar-seguridad.sh` | Ver Task 4 de este plan. Requiere aplicarse en el VPS real (no en Render). |
| 5 | Modelo relacional normalizado | ✅ | `docs/entrega2/modelo-relacional.md`; esquema real en `SGDM/backend/database/migrations/schema.sql` (15 tablas) | Ver Task 2 de este plan. |
| 6 | DCL implementado | ✅ | `SGDM/backend/database/dcl.sql` | 6 usuarios: `tornalyx_ddl`, `tornalyx_dcl`, `tornalyx_dml`, `tornalyx_monitor`, `tornalyx_backup`, `tornalyx_dev`. |
| 7 | Configuración de usuarios de BD con restricciones | ✅ | `SGDM/backend/database/dcl.sql` (mismo archivo que #6) | Cada usuario tiene privilegios mínimos por función (ver comentarios del archivo). |
| 8 | Modelos alineados al modelo relacional | ✅ | `SGDM/backend/models/*.php` (13 archivos) | Un modelo por tabla de negocio (`Usuario`, `Torneo`, `Equipo`, `Partido`, etc.), heredan de `SGDM/backend/models/Model.php`. |
| 9 | Integración con PHP POO (gestión de usuarios funcionando) | ✅ | `SGDM/backend/models/Usuario.php`, `SGDM/backend/controllers/AuthController.php` | Registro, login, 2FA por email, edición de perfil — todo con clases (`Model`, `Controller` como base). |
| 10 | Implementación con Apache | ✅ | `Dockerfile:1-43`, `SGDM/docker/vhost.conf`, `SGDM/frontend/.htaccess` | Apache 2 vía imagen `php:8.2-apache`, `mod_rewrite` + `mod_headers`, CSP/HSTS/X-Frame-Options ya configurados. |
