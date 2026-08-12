# Esquema de red — Tornalyx (Entrega 2)

Arquitectura de red objetivo para el despliegue en un VPS real (no aplica
al hosting actual en Render, que es PaaS sin acceso a la capa de red/SO —
ver `SGDM/backend/database/dcl.sql`, que ya documenta esta misma distinción
para los usuarios de base de datos).

## Diagrama

```mermaid
flowchart TB
    Internet(("Internet"))

    subgraph VPS["VPS (Debian/Ubuntu) — admin_tornalyx"]
        FW["Firewall: ufw\n(deny incoming por defecto)"]
        subgraph Borde["Zona expuesta"]
            Apache["Apache 2 :80/:443\n(php:8.2-apache)"]
        end
        subgraph Interno["Zona interna (solo localhost)"]
            App["App PHP\n(SGDM/backend + frontend)"]
            MySQL["MySQL 8\nbind-address 127.0.0.1"]
            Grafana["Grafana :3000\n(solo IP admin)"]
        end
        F2B["fail2ban\n(banea IPs vía iptables)"]
        AIDE["AIDE\n(integridad de archivos)"]
    end

    Admin(("Admin\nIP fija"))

    Internet -->|":80/:443"| FW
    Admin -->|":22 SSH restringido"| FW
    Admin -->|":3000 Grafana"| FW
    FW --> Apache
    Apache --> App
    App -->|"tornalyx_dml\nlocalhost:3306"| MySQL
    F2B -.->|"lee logs, banea"| Apache
    F2B -.->|"lee logs, banea"| FW
    AIDE -.->|"verifica integridad"| App
```

## Puertos y reglas

| Puerto | Servicio | Origen permitido | Justificación |
|--------|----------|-------------------|----------------|
| 22/tcp | SSH | Solo IP fija del admin | Reduce superficie de ataque; `fail2ban` banea igual si se filtra la IP. |
| 80/tcp | HTTP | Cualquiera | Redirige a HTTPS (no sirve contenido sensible en claro). |
| 443/tcp | HTTPS (Apache) | Cualquiera | Tráfico de la aplicación, con los headers de `SGDM/frontend/.htaccess` (CSP, HSTS, X-Frame-Options). |
| 3306/tcp | MySQL | Nadie desde afuera (`bind-address 127.0.0.1`) | La app y MySQL corren en el mismo host; no hay razón para exponer el puerto de base de datos a la red. |
| 3000/tcp | Grafana | Solo IP fija del admin | Panel de monitoreo, no es de uso público. |

## Segmentación

- **Zona expuesta (borde):** solo Apache recibe tráfico de Internet.
- **Zona interna:** MySQL y Grafana nunca reciben conexiones directas desde
  Internet, solo desde `localhost` (MySQL) o desde la IP del admin
  (Grafana), reforzado por las reglas de `scripts/servidor/firewall-ufw.sh`.
- **Defensa en profundidad:** el firewall es la primera barrera; `fail2ban`
  actúa como segunda barrera baneando IPs con intentos fallidos repetidos
  (SSH y login de Apache); AIDE detecta si un atacante que sí entró modificó
  archivos del sistema o de la aplicación. Detalle de cada pieza en
  `docs/entrega2/seguridad-servidor.md`.
