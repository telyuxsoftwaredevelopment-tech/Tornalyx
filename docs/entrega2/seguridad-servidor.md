# Seguridad de servidor — Tornalyx (Entrega 2)

Implementaciones de seguridad a nivel de sistema operativo para el VPS real
(no aplica a Render, que no da acceso de root). Scripts en
`scripts/servidor/`, arquitectura en `docs/entrega2/esquema-red.md`.

## Piezas

| Rol | Herramienta | Qué hace | Script/config |
|-----|-------------|----------|----------------|
| Firewall | `ufw` | Deniega todo el tráfico entrante por defecto; solo abre 22 (SSH, restringido a la IP del admin), 80/443 (HTTP/HTTPS) y 3000 (Grafana, restringido a la IP del admin). | `scripts/servidor/firewall-ufw.sh` |
| IPS (Intrusion Prevention) | `fail2ban` | Lee `/var/log/apache2/*.log` y el log de `sshd`; banea automáticamente (vía `iptables`) las IPs con intentos fallidos repetidos de login. No solo detecta, actúa. | `scripts/servidor/fail2ban-tornalyx.local` |
| IDS (Intrusion Detection) | `AIDE` | Calcula un hash de referencia de los archivos del sistema y de la aplicación; corridas posteriores (`aide --check`) detectan modificaciones no autorizadas (ej.: un atacante que reemplazó un archivo PHP). | `scripts/servidor/instalar-seguridad.sh` (inicializa la base) |
| Control complementario a nivel de app | `LoginThrottle` (ya existente) | Bloquea intentos de login por email/IP a nivel de aplicación, independiente del firewall — capa adicional para cuando el atacante no dispara los umbrales de fail2ban. | `SGDM/backend/shared/LoginThrottle.php` |

## Por qué esta combinación

- El firewall reduce la superficie de ataque desde el día uno (nadie llega
  a MySQL o a Grafana desde afuera).
- `fail2ban` cubre el caso de fuerza bruta contra SSH y contra el login de
  Apache a nivel de red/IP — complementa, no reemplaza, al
  `LoginThrottle.php` de la aplicación (que actúa por email, no por IP, y
  sigue funcionando aunque `fail2ban` no esté disponible, ej. detrás de un
  proxy que oculte la IP real).
- AIDE cubre el escenario en que un atacante ya entró (por una credencial
  filtrada, por ejemplo) y modificó archivos: sin un IDS de integridad, ese
  cambio pasaría desapercibido hasta el próximo síntoma visible.

## Aplicación en el servidor real

Este documento y los scripts se entregan listos para aplicar, pero no se
ejecutaron contra un servidor en vivo (no hay VPS provisionado en este
momento). Pasos para aplicarlos cuando exista el VPS:

```bash
scp -r scripts/servidor admin@<ip-del-vps>:/tmp/tornalyx-seguridad
ssh admin@<ip-del-vps>
sudo ADMIN_IP=<tu-ip-fija> /tmp/tornalyx-seguridad/instalar-seguridad.sh
```

Verificación posterior en el servidor (no reproducible en esta máquina de
desarrollo, que no tiene `ufw`/`fail2ban`/`aide` ni acceso a un VPS):

```bash
sudo ufw status verbose
sudo fail2ban-client status
sudo fail2ban-client status apache-auth
sudo aide --check
```
