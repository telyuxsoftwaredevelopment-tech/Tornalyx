# TAREA 6 – SISTEMAS OPERATIVOS
## Comparativa de Sistemas Operativos para Servidor

---

## 1. Introducción

El servidor es el componente más crítico de la infraestructura de Tornalyx. En él se ejecuta:
- El servidor web Apache con PHP
- El servidor de base de datos MySQL
- El sistema de archivos de la aplicación

La elección del sistema operativo servidor impacta directamente en el rendimiento, la estabilidad, la seguridad y el costo total de operación del sistema.

Se comparan tres distribuciones Linux de servidor ampliamente utilizadas en entornos de producción:

---

## 2. Sistemas Comparados

| Criterio | **AlmaLinux 8.10** | **Ubuntu Server 24.04 LTS** | **Debian 12 "Bookworm"** |
|----------|--------------------|-----------------------------|--------------------------|
| **Basado en** | RHEL 8 | Debian | Debian (base) |
| **Desarrollador** | AlmaLinux OS Foundation | Canonical Ltd. | Comunidad Debian |
| **Tipo** | Libre y gratuito | Libre y gratuito | Libre y gratuito |
| **Ciclo de soporte** | 10 años (hasta 2029) | 5 años (hasta 2029) | ~5 años (hasta 2028) |
| **Gestor de paquetes** | DNF/YUM (rpm) | APT (deb) | APT (deb) |
| **Init system** | systemd | systemd | systemd |
| **Kernel por defecto** | 4.18 (RHEL 8 base) | 6.8 | 6.1 LTS |
| **Control de acceso obligatorio** | SELinux (activo) | AppArmor (activo) | AppArmor (disponible) |

---

## 3. Comparativa Detallada

### 3.1 Seguridad

| Aspecto | AlmaLinux 8.10 | Ubuntu Server | Debian 12 |
|---------|----------------|---------------|-----------|
| Control de acceso obligatorio | ✅ SELinux en modo *enforcing* por defecto | ✅ AppArmor activado por defecto | ⚠️ AppArmor disponible, no activado |
| Granularidad de la política | ✅ Por tipo de archivo y proceso (contextos) | ⚠️ Por ruta de ejecutable (perfiles) | ⚠️ Por ruta de ejecutable |
| Actualizaciones automáticas | ✅ `dnf-automatic` | ✅ `unattended-upgrades` | ✅ Requiere configuración manual |
| Alineación con CIS Benchmarks | ✅ Hereda las guías de RHEL | ✅ Guías disponibles | ✅ Guías disponibles |
| Parches de kernel en caliente | ❌ No disponible sin suscripción | ✅ Livepatch (gratuito, hasta 5 equipos) | ❌ No disponible |
| **Puntuación** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ |

SELinux es la diferencia de fondo. AppArmor restringe a los programas según la ruta del ejecutable; SELinux etiqueta cada archivo y cada proceso con un contexto y decide en base a esas etiquetas. Para un servidor web esto es concreto: aunque un atacante lograra escribir un archivo dentro del directorio de la aplicación, si ese archivo no tiene el contexto `httpd_sys_content_t`, Apache no lo sirve.

---

### 3.2 Soporte a largo plazo

| Aspecto | AlmaLinux 8.10 | Ubuntu Server | Debian 12 |
|---------|----------------|---------------|-----------|
| Años de soporte | ✅ 10 (hasta marzo de 2029) | ⚠️ 5 gratuitos (10 con Ubuntu Pro) | ⚠️ ~5 años |
| Costo del soporte extendido | ✅ Sin costo | ⚠️ Requiere suscripción Ubuntu Pro | ✅ Sin costo |
| Estabilidad de la API del sistema | ✅ Congelada durante todo el ciclo | ⚠️ Cambios entre versiones LTS | ✅ Muy estable |
| **Puntuación** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |

---

### 3.3 Estabilidad y rendimiento

| Aspecto | AlmaLinux 8.10 | Ubuntu Server | Debian 12 |
|---------|----------------|---------------|-----------|
| RAM en reposo (aproximada) | ~200 MB | ~180 MB | ~120 MB |
| Estabilidad de paquetes | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Frecuencia de cambios | Baja (ciclos RHEL) | Alta (versiones recientes) | Moderada (versiones probadas) |
| **Puntuación** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |

---

### 3.4 Compatibilidad con el stack LAMP

| Componente | AlmaLinux 8.10 | Ubuntu Server 24.04 | Debian 12 |
|-----------|----------------|---------------------|-----------|
| Apache 2.4 | ✅ Repositorio oficial (`httpd`) | ✅ Repositorio oficial (`apache2`) | ✅ Repositorio oficial |
| PHP 8.2 | ✅ Módulo AppStream | ✅ Repositorio oficial | ⚠️ Backports / sury |
| PHP 8.3 | ⚠️ Requiere repositorio Remi | ✅ PPA ondrej/php | ⚠️ sury |
| MySQL 8.0 | ✅ Módulo AppStream | ✅ `mysql-server` | ⚠️ MariaDB por defecto |
| Certbot (SSL) | ⚠️ Requiere EPEL | ✅ snap / apt | ✅ apt |
| Instalación del stack completo | ⚠️ Varios comandos y repos extra | ✅ Un solo comando (`tasksel`) | ✅ Directo con apt |
| **Puntuación** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |

Esta es la debilidad de AlmaLinux para el proyecto y conviene decirlo con claridad: montar el stack lleva más pasos y obliga a habilitar EPEL para algunas herramientas.

---

### 3.5 Comunidad y documentación

| Aspecto | AlmaLinux 8.10 | Ubuntu Server | Debian 12 |
|---------|----------------|---------------|-----------|
| Foros y StackOverflow | ⚠️ Menor volumen propio | ✅ Miles de respuestas | ✅ Muchas respuestas |
| Documentación oficial | ✅ almalinux.org + toda la de RHEL | ✅ Excelente (docs.ubuntu.com) | ✅ Muy buena (debian.org) |
| Imágenes en proveedores VPS | ⚠️ Menos oferta | ✅ Muy común | ✅ Común |
| **Puntuación** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |

Como contrapeso: al ser compatible binariamente con RHEL, casi toda la documentación de Red Hat y de Rocky Linux aplica sin cambios, de modo que el volumen real de material disponible es mayor que el que sugiere el nombre de la distribución.

---

## 4. Tabla Resumen de Puntuación

Los pesos responden a la naturaleza del proyecto: se trata de un sistema que va a quedar publicado y en operación, dentro de una asignatura cuyo objetivo es aprender a administrar servidores. Por eso la seguridad y el soporte a largo plazo pesan más que la comodidad de la instalación inicial, que es un costo que se paga una sola vez.

| Criterio (peso) | AlmaLinux 8.10 | Ubuntu Server | Debian 12 |
|----------------|----------------|---------------|-----------|
| Seguridad (30%) | ⭐⭐⭐⭐⭐ (5) | ⭐⭐⭐⭐ (4) | ⭐⭐⭐⭐ (4) |
| Soporte a largo plazo (25%) | ⭐⭐⭐⭐⭐ (5) | ⭐⭐⭐ (3) | ⭐⭐⭐ (3) |
| Estabilidad (20%) | ⭐⭐⭐⭐⭐ (5) | ⭐⭐⭐⭐ (4) | ⭐⭐⭐⭐⭐ (5) |
| Compatibilidad LAMP (15%) | ⭐⭐⭐ (3) | ⭐⭐⭐⭐⭐ (5) | ⭐⭐⭐⭐ (4) |
| Comunidad y documentación (10%) | ⭐⭐⭐ (3) | ⭐⭐⭐⭐⭐ (5) | ⭐⭐⭐⭐ (4) |
| **TOTAL PONDERADO** | **4.50 / 5** | **4.00 / 5** | **3.95 / 5** |

Cálculo del total de AlmaLinux:

```
(5 × 0.30) + (5 × 0.25) + (5 × 0.20) + (3 × 0.15) + (3 × 0.10)
   1.50    +    1.25    +    1.00    +    0.45    +    0.30    = 4.50
```

---

## 5. Sistema Operativo Elegido y Justificación

### ✅ Sistema elegido: **AlmaLinux 8.10 (Cerulean Leopard)**

### Justificación técnica:

**1. SELinux activo por defecto**
AlmaLinux aplica control de acceso obligatorio desde la instalación, sin configuración adicional. Cada archivo y cada proceso llevan un contexto, y la política decide qué puede tocar qué. Para una aplicación web expuesta a internet esto agrega una barrera que los permisos de Unix por sí solos no dan: aunque se lograra escribir un archivo dentro del directorio publicado, Apache no lo serviría si no tiene el contexto correspondiente.

**2. Diez años de soporte, sin costo**
La rama 8 recibe parches de seguridad hasta marzo de 2029. Ubuntu Server ofrece cinco años gratuitos y llega a diez solo con una suscripción Ubuntu Pro. Para un sistema pensado para quedar operativo más allá del curso, la diferencia es relevante.

**3. Compatibilidad binaria con Red Hat Enterprise Linux**
RHEL es el estándar de facto en servidores empresariales. Todo lo que se aprende administrando AlmaLinux (`dnf`, `firewalld`, `systemctl`, SELinux, `semanage`) se traslada sin cambios a un entorno profesional, y toda la documentación de Red Hat aplica directamente.

**4. Estabilidad de los ciclos RHEL**
Las versiones de los paquetes quedan congeladas durante todo el ciclo de vida y solo reciben parches de seguridad retroportados. Un servidor en producción no cambia de comportamiento por una actualización.

**5. Disponible como imagen de WSL**
AlmaLinux 8 se distribuye como imagen para Windows Subsystem for Linux, lo que permite desarrollar y administrar el servidor desde los equipos con Windows 11 del equipo sin montar una máquina virtual aparte.

### Contrapartidas asumidas:

Ninguna elección es gratuita, y las de esta conviene tenerlas presentes:

- **El stack LAMP lleva más trabajo de instalación.** Hay que habilitar EPEL para Certbot y el repositorio Remi si se quiere PHP 8.3. En Ubuntu el stack se resuelve con un solo comando.
- **SELinux agrega una fuente de errores.** Un servicio que no arranca o un 403 inesperado suelen ser de contexto, no de permisos. Se mitiga con el script `gestion_permisos.sh`, que incluye una opción para revisar y corregir los contextos.
- **Menos volumen de respuestas específicas en foros.** Se compensa con la documentación de RHEL y de Rocky Linux, que es aplicable sin adaptaciones.

### Configuración de servidor recomendada para Tornalyx:

```
OS:        AlmaLinux 8.10 (Cerulean Leopard)
CPU:       2 vCPU (mínimo)
RAM:       2 GB (mínimo) / 4 GB (recomendado)
Disco:     20 GB SSD
Stack:     Apache 2.4 (httpd) + PHP 8.2 (AppStream) + MySQL 8.0
SSL:       Certbot vía EPEL (Let's Encrypt) — HTTPS obligatorio
Firewall:  firewalld (servicios ssh, http, https)
SELinux:   enforcing
Entorno:   WSL2 durante el desarrollo (requiere systemd=true en /etc/wsl.conf)
```
