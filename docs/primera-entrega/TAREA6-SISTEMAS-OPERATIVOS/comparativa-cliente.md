# TAREA 6 – SISTEMAS OPERATIVOS
## Comparativa de Sistemas Operativos para Clientes (Usuarios del Sistema)

---

## 1. Introducción

El sistema Tornalyx es una aplicación web. Los usuarios finales (participantes, organizadores, administradores) acceden al sistema mediante un navegador web. El sistema operativo del cliente no afecta la funcionalidad de la plataforma siempre que disponga de un navegador moderno; sin embargo, es importante analizar cuál SO brinda la mejor experiencia, seguridad y compatibilidad para el contexto de uso.

Se comparan los dos sistemas operativos de escritorio más relevantes para el entorno estudiantil y empresarial de Uruguay:

---

## 2. Sistemas Comparados

| Criterio | **Windows 11** | **Ubuntu Desktop 24.04 LTS** |
|----------|---------------|------------------------------|
| **Desarrollador** | Microsoft Corporation | Canonical Ltd. |
| **Tipo de licencia** | Propietario (de pago / OEM) | Libre y gratuito (GNU GPL) |
| **Año de lanzamiento** | 2021 | 2024 (LTS cada 2 años) |
| **Soporte** | Hasta oct. 2031 | Hasta abril 2029 (LTS) |
| **Arquitectura** | x86-64 (ARM experimental) | x86-64, ARM64 |
| **Interfaz** | Windows Shell / Fluent UI | GNOME 46 |
| **Cuota de mercado** | ~72% escritorio global | ~3% escritorio |
| **Requiistos mínimos** | 4 GB RAM, TPM 2.0, 64 GB | 2 GB RAM (recomendado 4 GB), 25 GB |

---

## 3. Comparativa Detallada

### 3.1 Facilidad de uso y curva de aprendizaje

| Aspecto | Windows 11 | Ubuntu Desktop |
|---------|-----------|----------------|
| Interfaz familiar | ✅ Muy alta — mayoría de usuarios ya la conoce | ⚠️ Moderada — requiere adaptación inicial |
| Configuración inicial | ✅ Asistente guiado, cuenta Microsoft | ✅ Instalador Ubiquity simple |
| Gestión de software | ✅ .exe / Microsoft Store | ✅ apt / Snap / Ubuntu Software Center |
| Personalización | ✅ Alta (temas, atajos, accesibilidad) | ✅ Alta (extensiones GNOME, temas) |
| **Veredicto** | **Más accesible para usuarios sin experiencia** | **Recomendable para usuarios con perfil técnico** |

---

### 3.2 Compatibilidad con navegadores (crítico para Tornalyx)

| Navegador | Windows 11 | Ubuntu Desktop |
|-----------|-----------|----------------|
| Google Chrome | ✅ Soporte completo | ✅ Soporte completo |
| Mozilla Firefox | ✅ Soporte completo | ✅ Soporte completo (preinstalado) |
| Microsoft Edge | ✅ Preinstalado | ✅ Disponible en .deb |
| Safari | ❌ No disponible | ❌ No disponible |
| Opera / Brave | ✅ Disponible | ✅ Disponible |
| **Veredicto** | **Total compatibilidad con Tornalyx** | **Total compatibilidad con Tornalyx** |

**Conclusión:** ambos SO son 100% compatibles con Tornalyx dado que la aplicación funciona en cualquier navegador moderno. Las tecnologías usadas (HTML5, CSS3, JavaScript Vanilla) no dependen del SO cliente.

---

### 3.3 Seguridad para el usuario final

| Aspecto | Windows 11 | Ubuntu Desktop |
|---------|-----------|----------------|
| Antivirus nativo | ✅ Windows Defender (bueno) | ⚠️ No incluido por defecto (bajo riesgo nativo) |
| Actualizaciones automáticas | ✅ Windows Update | ✅ apt update / livepatch |
| Control de cuentas de usuario | ✅ UAC (User Account Control) | ✅ sudo (solo admin puede ejecutar root) |
| Historial de vulnerabilidades | ⚠️ Mayor superficie de ataque (popularidad) | ✅ Menor cantidad de malware dirigido |
| Firewall nativo | ✅ Windows Firewall | ✅ ufw (uncomplicated firewall) |
| Cifrado de disco | ✅ BitLocker (Pro) | ✅ LUKS (gratis, configurable) |
| **Veredicto** | **Buena seguridad pero mayor exposición a malware** | **Excelente seguridad por modelo de permisos** |

---

### 3.4 Rendimiento y consumo de recursos

| Aspecto | Windows 11 | Ubuntu Desktop |
|---------|-----------|----------------|
| RAM en reposo | ~2.5-3.5 GB | ~800 MB - 1.2 GB |
| Arranque en SSD | ~15-25 segundos | ~8-15 segundos |
| Rendimiento navegador | ✅ Excelente | ✅ Excelente |
| Impacto en hardware viejo | ⚠️ Requiere TPM 2.0 y hardware reciente | ✅ Funciona bien en hardware de 8+ años |
| **Veredicto** | **Requiere hardware moderno** | **Mejor opción en hardware antiguo** |

---

### 3.5 Costo y licenciamiento

| Aspecto | Windows 11 | Ubuntu Desktop |
|---------|-----------|----------------|
| Precio | ~USD 200 (Home) / incluido en laptop | **Gratuito** |
| Soporte empresarial | ✅ Microsoft Support (pago) | ✅ Ubuntu Pro (gratuito hasta 5 PCs) |
| **Veredicto** | **Costo significativo si se adquiere separado** | **Sin costo** |

---

## 4. Tabla Resumen de Puntuación

| Criterio (peso) | Windows 11 | Ubuntu Desktop |
|----------------|-----------|----------------|
| Facilidad de uso (25%) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| Compatibilidad web (25%) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Seguridad (20%) | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Rendimiento (15%) | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Costo (15%) | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **TOTAL** | **4.1 / 5** | **4.5 / 5** |

---

## 5. Conclusión y Recomendación

### Para el contexto de Tornalyx:

**Windows 11** es la opción más realista para la mayoría de los usuarios finales del sistema, dado que:
- Es el SO instalado en la mayor parte de los equipos domésticos y educativos en Uruguay.
- No requiere capacitación adicional.
- Ofrece plena compatibilidad con los navegadores necesarios para Tornalyx.

**Ubuntu Desktop** es recomendable para usuarios con perfil técnico (desarrolladores del sistema, administradores avanzados) dado que:
- Consume menos recursos.
- Es más seguro por diseño.
- Tiene costo cero.

**Recomendación final:** dado que Tornalyx es una aplicación web, el SO del cliente es transparente a la aplicación. Se recomienda documentar que el sistema es compatible con **ambos sistemas operativos** y cualquier otro que incluya un navegador moderno (Chrome 100+, Firefox 100+, Edge 100+).
