# Política de respaldos — Tornalyx (Entrega 2)

## Qué se respalda

| Dato | Origen | Motivo |
|------|--------|--------|
| Base de datos completa (`tornalyx_db`) | MySQL, vía `mysqldump` con el usuario `tornalyx_backup` (`SGDM/backend/database/dcl.sql:108-110`) | Contiene usuarios, torneos, partidos, resultados — todo el estado de negocio, no reconstruible. |
| Avatares subidos por usuarios | `SGDM/frontend/uploads/avatars/` (ver `SGDM/backend/controllers/PerfilController.php:33`) | Único dato binario que vive fuera de git y fuera de la base. |
| Código de la aplicación | — | **No se respalda por separado**: ya vive versionado en git/GitHub, que es su propio sistema de respaldo distribuido. |

## Tipo de respaldo

- **Lógico completo (full) de la base**, vía `mysqldump --single-transaction`
  (consistente sin bloquear escrituras en tablas InnoDB, que es el motor de
  todas las tablas de `schema.sql`). No se usa incremental/binlog: el
  tamaño de la base de un sistema de gestión de torneos universitario no
  justifica la complejidad operativa de un point-in-time recovery.
- **Copia completa (full) del directorio de avatares** vía `tar`, ya que es
  la manera más simple de mantener consistencia con el `avatar_url` que
  apunta a esos archivos en la base.

## Cronograma

| Respaldo | Frecuencia | Horario | Retención |
|----------|------------|---------|-----------|
| Base de datos | Diario | 03:00 (hora del servidor, baja carga) | 7 diarios + 4 semanales (domingo) + 3 mensuales (día 1) |
| Avatares | Diario | 03:05 (después de la base, mismo cron) | Igual que la base: 7 diarios + 4 semanales + 3 mensuales |

La retención escalonada (diario/semanal/mensual) evita que un error
detectado tarde (ej.: una semana después) ya no tenga respaldo disponible,
sin guardar 30 copias diarias completas indefinidamente.

## Dónde se guardan

- Copia local en el propio VPS: `/var/backups/tornalyx/` (fuera del
  `DocumentRoot` de Apache, así nunca queda servible por HTTP).
- Recomendado para producción real (fuera del alcance de este entregable
  académico): sincronizar `/var/backups/tornalyx/` a un storage externo
  (ej. `rclone` a un bucket S3/Backblaze) para sobrevivir a la pérdida
  completa del VPS. El script de la Task 2 deja un `TODO` explícito marcado
  para este punto, no lo implementa.

## Prueba de restauración

Un respaldo que nunca se probó restaurar no es un respaldo confiable. Antes
de dar por buena la política en el servidor real:

```bash
gunzip -c /var/backups/tornalyx/db/tornalyx_db_YYYY-MM-DD.sql.gz | \
  mysql -u tornalyx_dev -p tornalyx_dev   # restaurar contra la base de DEV, nunca contra producción
```
