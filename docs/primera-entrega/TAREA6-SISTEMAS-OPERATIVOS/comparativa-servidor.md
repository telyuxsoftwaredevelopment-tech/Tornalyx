# TAREA 6 – SISTEMAS OPERATIVOS
## Comparativa de Sistemas Operativos para Servidor

---

## 1. Introducción

El servidor es el componente más crítico de la infraestructura de Tornalyx. En él se ejecuta:
- El servidor web Apache/Nginx con PHP
- El servidor de base de datos MySQL
- El sistema de archivos de la aplicación

La elección del sistema operativo servidor impacta directamente en el rendimiento, la estabilidad, la seguridad y el costo total de operación del sistema.

Se comparan tres distribuciones Linux de servidor ampliamente utilizadas en entornos de producción:

---

## 2. Sistemas Comparados

| Criterio | **Ubuntu Server 24.04 LTS** | **Debian 12 "Bookworm"** | **AlmaLinux 9** |
|----------|----------------------------|--------------------------|-----------------|
| **Basado en** | Debian | Debian (base) | RHEL 9 |
| **Desarrollador** | Canonical Ltd. | Comunidad Debian | AlmaLinux OS Foundation |
| **Tipo** | Libre y gratuito | Libre y gratuito | Libre y gratuito |
| **Ciclo de soporte LTS** | 5 años (hasta 2029) | ~5 años (hasta 2028) | 10 años (hasta 2032) |
| **Gestor de paquetes** | APT (deb) | APT (deb) | DNF/YUM (rpm) |
| **Init system** | systemd | systemd | systemd |
| **Kernel por defecto** | 6.8 | 6.1 LTS | 5.14 (RHEL 9 base) |
| **Cuota de uso servidor** | ~35% (servidores web) | ~14% | ~3% (RHEL derivados ~15%) |

---

## 3. Comparativa Detallada

### 3.1 Facilidad de instalación y configuración

| Aspecto | Ubuntu Server | Debian 12 | AlmaLinux 9 |
|---------|--------------|-----------|-------------|
| Instalador | ✅ Subiquity (moderno, guiado) | ⚠️ Clásico (textual, más pasos) | ✅ Anaconda (gráfico) |
| Configuración LAMP | ✅ Muy fácil (tasksel) | ✅ Fácil (apt) | ⚠️ Diferente (dnf, repos EPEL) |
| Documentación | ✅ Excelente (docs.ubuntu.com) | ✅ Muy buena (debian.org) | ✅ Buena (almalinux.wiki) |
| Comunidad | ✅ Muy grande | ✅ Grande | ⚠️ Moderada (reciente) |
| **Puntuación** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |

---

### 3.2 Compatibilidad con el stack LAMP (Linux + Apache + MySQL + PHP)

| Componente | Ubuntu Server 24.04 | Debian 12 | AlmaLinux 9 |
|-----------|--------------------|-----------|-----------  |
| Apache 2.4 | ✅ Repositorio oficial | ✅ Repositorio oficial | ✅ Repositorio oficial |
| PHP 8.3 | ✅ PPA o ondrej/php | ✅ backports / sury | ✅ Remi repo (extra) |
| MySQL 8.0 | ✅ mysql-server | ✅ mariadb-server / mysql | ✅ mysql-community |
| Composer | ✅ apt + manual | ✅ apt + manual | ✅ dnf + manual |
| Certbot (SSL) | ✅ snap / apt | ✅ apt | ✅ dnf / snap |
| **Veredicto** | **Stack más directo a instalar** | **Estable pero requiere backports** | **Requiere repos externos** |

---

### 3.3 Seguridad

| Aspecto | Ubuntu Server | Debian 12 | AlmaLinux 9 |
|---------|--------------|-----------|-------------|
| Actualizaciones de seguridad | ✅ Automáticas (unattended-upgrades) | ✅ Disponibles (configuración manual) | ✅ automáticas (dnf-automatic) |
| SELinux / AppArmor | ✅ AppArmor activado por defecto | ⚠️ AppArmor (disponible, no activado) | ✅ SELinux activado por defecto (más estricto) |
| Parches de kernel en caliente | ✅ Livepatch (gratuito hasta 3 máquinas) | ❌ No disponible | ❌ No disponible |
| Soporte de seguridad extendido | ✅ Ubuntu Pro (10 años) | ✅ LTS security updates | ✅ 10 años garantizados |
| CIS Benchmarks | ✅ Guías disponibles | ✅ Guías disponibles | ✅ Fuertemente alineado a RHEL |
| **Veredicto** | **Excelente, con parches en caliente** | **Excelente estabilidad de paquetes** | **SELinux más restrictivo y empresarial** |

---

### 3.4 Rendimiento y estabilidad

| Aspecto | Ubuntu Server | Debian 12 | AlmaLinux 9 |
|---------|--------------|-----------|-------------|
| RAM en reposo (mínima) | ~180 MB | ~120 MB | ~250 MB |
| Estabilidad de paquetes | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Frecuencia de actualizaciones | Alta (últimas versiones) | Moderada (versiones probadas) | Moderada (ciclos RHEL) |
| Uptime esperado | 99.9%+ | 99.9%+ | 99.9%+ |
| **Veredicto** | **Buen balance nuevas funciones/estabilidad** | **Más conservador pero extremadamente estable** | **Alta estabilidad empresarial** |

---

### 3.5 Soporte a largo plazo y comunidad

| Aspecto | Ubuntu Server | Debian 12 | AlmaLinux 9 |
|---------|--------------|-----------|-------------|
| Soporte LTS (años) | 5 años base / 10 Pro | ~5 años | 10 años |
| Foros y StackOverflow | ✅ Miles de respuestas | ✅ Muchas respuestas | ⚠️ Menor volumen |
| Hosting VPS con imagen | ✅ Muy común (DigitalOcean, Linode, AWS) | ✅ Común | ⚠️ Menos oferta |
| **Veredicto** | **Mayor ecosistema de soporte comunitario** | **Sólido soporte comunitario** | **Soporte empresarial fuerte** |

---

## 4. Tabla Resumen de Puntuación

| Criterio (peso) | Ubuntu Server | Debian 12 | AlmaLinux 9 |
|----------------|--------------|-----------|-------------|
| Facilidad de config. LAMP (30%) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| Seguridad (25%) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Estabilidad (20%) | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Comunidad y docs (15%) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| Soporte LTS (10%) | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **TOTAL PONDERADO** | **4.65 / 5** | **4.5 / 5** | **4.0 / 5** |

---

## 5. Sistema Operativo Elegido y Justificación

### ✅ Sistema elegido: **Ubuntu Server 24.04 LTS**

### Justificación técnica:

**1. Ecosistema LAMP ideal para el proyecto**
Ubuntu Server tiene soporte nativo para Apache, PHP 8.x y MySQL sin necesidad de repositorios externos. La configuración del stack LAMP se puede completar con un solo comando:
```bash
sudo tasksel install lamp-server
```
Esto reduce el tiempo de despliegue y minimiza la posibilidad de errores de configuración.

**2. Mayor base de conocimiento disponible**
Siendo el SO de servidor más utilizado en entornos web, la cantidad de documentación, tutoriales y soluciones a problemas específicos es superior. Esto es crítico para un equipo de desarrollo estudiantil.

**3. Seguridad activa**
Ubuntu Server 24.04 LTS incluye AppArmor activado por defecto y el servicio `unattended-upgrades` para parches de seguridad automáticos. Canonical ofrece livepatch gratuito para hasta 5 máquinas personales.

**4. Compatibilidad con hosting educativo y VPS económicos**
La mayoría de proveedores VPS de bajo costo (DigitalOcean, Linode/Akamai, Vultr) ofrecen imágenes de Ubuntu Server preconfiguradas, lo que facilita el despliegue en producción.

**5. Soporte 5 años garantizados (extensible a 10)**
El ciclo LTS garantiza estabilidad y parches hasta abril de 2029, cubriendo el período de desarrollo y operación del proyecto estudiantil.

### Configuración de servidor recomendada para Tornalyx:

```
OS:        Ubuntu Server 24.04 LTS
CPU:       2 vCPU (mínimo)
RAM:       2 GB (mínimo) / 4 GB (recomendado)
Disco:     20 GB SSD
Stack:     Apache 2.4 + PHP 8.3 + MySQL 8.0
SSL:       Certbot (Let's Encrypt) — HTTPS obligatorio
Firewall:  ufw (puertos 22, 80, 443)
```
