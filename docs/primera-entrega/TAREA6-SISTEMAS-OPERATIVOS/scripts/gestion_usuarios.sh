#!/bin/bash
# ============================================================
# TORNALYX – SGDM
# Script de Gestión de Usuarios del Sistema Operativo
# Tarea 6 – Sistemas Operativos | Primera Entrega
#
# Uso: sudo bash gestion_usuarios.sh [opción]
# ============================================================

# ──────────────────────────────────────────────────────────────
# SECCIÓN 1: CONSTANTES Y COLORES
# Definimos constantes de color para mejorar la legibilidad
# de los mensajes en la terminal.
# ──────────────────────────────────────────────────────────────

# Códigos ANSI de color para la salida en terminal
RED='\033[0;31m'      # Rojo: errores y advertencias críticas
GREEN='\033[0;32m'    # Verde: operaciones exitosas
YELLOW='\033[1;33m'   # Amarillo: advertencias no críticas
BLUE='\033[0;34m'     # Azul: información y títulos
CYAN='\033[0;36m'     # Cian: datos de usuario y detalles
NC='\033[0m'          # NC = No Color: resetea el color al predeterminado

# Archivo de log donde se registran todas las acciones del script
# Toda acción queda registrada con fecha, hora y usuario que ejecutó el script
LOG_FILE="/var/log/tornalyx/gestion_usuarios.log"

# ──────────────────────────────────────────────────────────────
# SECCIÓN 2: FUNCIONES AUXILIARES
# ──────────────────────────────────────────────────────────────

# Función: log_accion
# Registra una acción en el archivo de log con timestamp.
# Argumentos:
#   $1 - Mensaje a registrar
log_accion() {
    # "date" genera la fecha y hora actual en formato ISO 8601
    # "$(whoami)" obtiene el nombre del usuario que ejecuta el script
    # ">>" redirige la salida al final del archivo (append), no sobreescribe
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$(whoami)] $1" >> "$LOG_FILE" 2>/dev/null
}

# Función: imprimir_titulo
# Imprime un encabezado con formato para secciones del menú.
# Argumentos:
#   $1 - Texto del título
imprimir_titulo() {
    # Imprime una línea de separación visual
    echo -e "\n${BLUE}══════════════════════════════════════════${NC}"
    # "-e" activa la interpretación de secuencias de escape (\n, colores, etc.)
    echo -e "${BLUE}  🏆 TORNALYX – Gestión de Usuarios${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}══════════════════════════════════════════${NC}\n"
}

# Función: verificar_root
# Verifica que el script se ejecute con privilegios de superusuario (root).
# El UID (User ID) del usuario root es siempre 0.
# Si no es root, muestra un error y termina el script con código de error 1.
verificar_root() {
    # $EUID es una variable especial de Bash que contiene el UID efectivo del proceso
    # Si el UID efectivo no es 0 (root), el usuario no tiene privilegios suficientes
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}❌ Error: Este script debe ejecutarse con sudo o como root.${NC}"
        echo -e "${YELLOW}   Uso: sudo bash gestion_usuarios.sh${NC}"
        # "exit 1" termina el script con código de salida 1 (error)
        exit 1
    fi
}

# Función: validar_nombre_usuario
# Valida que un nombre de usuario cumpla las reglas de Linux:
#   - Solo letras minúsculas, números, guiones y guiones bajos
#   - Longitud entre 3 y 32 caracteres
#   - No puede comenzar con un número
# Argumentos:
#   $1 - Nombre de usuario a validar
# Retorna: 0 si es válido, 1 si no lo es
validar_nombre_usuario() {
    local usuario="$1"

    # "^[a-z_][a-z0-9_-]*$" es una expresión regular que comprueba:
    #   ^        → inicio de la cadena
    #   [a-z_]   → primer carácter: letra minúscula o guion bajo
    #   [a-z0-9_-]* → siguientes caracteres: letras, números, _, -
    #   $        → fin de la cadena
    # =~ es el operador de comparación de expresión regular en Bash
    if [[ ! "$usuario" =~ ^[a-z_][a-z0-9_-]*$ ]]; then
        echo -e "${RED}❌ Nombre de usuario inválido: '$usuario'${NC}"
        echo -e "${YELLOW}   Solo minúsculas, números, _ y - (debe iniciar con letra)${NC}"
        return 1  # Retorna 1 = error
    fi

    # Comprueba la longitud del nombre: mínimo 3, máximo 32 caracteres
    # ${#usuario} es la longitud de la cadena almacenada en $usuario
    if [[ ${#usuario} -lt 3 || ${#usuario} -gt 32 ]]; then
        echo -e "${RED}❌ El nombre debe tener entre 3 y 32 caracteres.${NC}"
        return 1
    fi

    return 0  # Retorna 0 = éxito (nombre válido)
}

# Función: usuario_existe
# Verifica si un usuario del sistema operativo ya existe.
# Argumentos:
#   $1 - Nombre de usuario a buscar
# Retorna: 0 si existe, 1 si no existe
usuario_existe() {
    # "id" es un comando de Linux que muestra información de un usuario
    # Si el usuario existe, "id" retorna 0; si no existe, retorna 1
    # "2>/dev/null" suprime el mensaje de error cuando el usuario no existe
    id "$1" &>/dev/null
    return $?   # Retorna el código de salida del comando "id"
}

# ──────────────────────────────────────────────────────────────
# SECCIÓN 3: FUNCIONES PRINCIPALES
# ──────────────────────────────────────────────────────────────

# Función: crear_usuario
# Crea un nuevo usuario en el sistema con todas las configuraciones necesarias.
crear_usuario() {
    imprimir_titulo "Crear nuevo usuario"

    # Solicita el nombre de usuario al operador
    read -p "$(echo -e "${CYAN}Nombre de usuario:${NC} ")" nombre_usuario

    # Valida el nombre ingresado usando la función auxiliar
    # Si la validación falla (retorna 1), el script termina la función
    validar_nombre_usuario "$nombre_usuario" || return 1

    # Verifica si el usuario ya existe antes de intentar crearlo
    if usuario_existe "$nombre_usuario"; then
        echo -e "${YELLOW}⚠️  El usuario '$nombre_usuario' ya existe en el sistema.${NC}"
        return 1
    fi

    # Solicita el nombre completo (GECOS - información descriptiva del usuario)
    read -p "$(echo -e "${CYAN}Nombre completo (para registro):${NC} ")" nombre_completo

    # Solicita la selección del rol para asignar grupos apropiados
    echo -e "\n${CYAN}Selecciona el rol del usuario:${NC}"
    echo "  1) Administrador del sistema (sudo + www-data)"
    echo "  2) Operador web (www-data)"
    echo "  3) Desarrollador (www-data)"
    echo "  4) Usuario de respaldo (sin login)"
    read -p "Rol [1-4]: " rol

    # Crea el usuario con "useradd"
    # -m: crea el directorio home automáticamente
    # -c: comentario (nombre completo para identificación)
    # -s: shell asignada al usuario
    case "$rol" in
        1)
            # Administrador: shell completa, grupos sudo y www-data
            useradd -m -c "$nombre_completo" -s /bin/bash -G sudo,www-data "$nombre_usuario"
            ;;
        2)
            # Operador web: shell completa, grupo www-data (acceso a archivos web)
            useradd -m -c "$nombre_completo" -s /bin/bash -G www-data "$nombre_usuario"
            ;;
        3)
            # Desarrollador: shell completa, grupo www-data
            useradd -m -c "$nombre_completo" -s /bin/bash -G www-data "$nombre_usuario"
            ;;
        4)
            # Usuario de respaldo: SIN shell interactiva (no puede hacer login)
            # /usr/sbin/nologin retorna un mensaje de error si alguien intenta iniciar sesión
            useradd -m -c "$nombre_completo" -s /usr/sbin/nologin "$nombre_usuario"
            ;;
        *)
            # Opción inválida
            echo -e "${RED}❌ Opción inválida. Operación cancelada.${NC}"
            return 1
            ;;
    esac

    # Verifica si "useradd" se ejecutó correctamente
    # $? contiene el código de salida del último comando ejecutado
    if [[ $? -ne 0 ]]; then
        echo -e "${RED}❌ Error al crear el usuario. Revisa los permisos.${NC}"
        return 1
    fi

    # Solicita y establece la contraseña del nuevo usuario
    # "passwd" abre un prompt seguro donde la contraseña no se muestra en pantalla
    echo -e "\n${CYAN}Establece la contraseña para '$nombre_usuario':${NC}"
    passwd "$nombre_usuario"

    # Fuerza al usuario a cambiar su contraseña en el próximo inicio de sesión
    # Esto obliga a que el usuario elija su propia contraseña segura
    # "chage -d 0" establece la fecha de expiración de contraseña en 0 (ya expirada)
    chage -d 0 "$nombre_usuario"

    # Registra la acción en el archivo de log
    log_accion "CREAR_USUARIO: $nombre_usuario (rol=$rol, nombre='$nombre_completo')"

    echo -e "\n${GREEN}✅ Usuario '$nombre_usuario' creado exitosamente.${NC}"
    echo -e "${YELLOW}   El usuario deberá cambiar su contraseña en el primer inicio de sesión.${NC}"
}

# ──────────────────────────────────────────────────────────────

# Función: eliminar_usuario
# Elimina un usuario del sistema de forma segura.
eliminar_usuario() {
    imprimir_titulo "Eliminar usuario"

    read -p "$(echo -e "${CYAN}Nombre del usuario a eliminar:${NC} ")" nombre_usuario

    # Verifica que el usuario exista antes de intentar eliminarlo
    if ! usuario_existe "$nombre_usuario"; then
        echo -e "${RED}❌ El usuario '$nombre_usuario' no existe.${NC}"
        return 1
    fi

    # Muestra información del usuario antes de eliminar (para confirmar)
    echo -e "\n${YELLOW}⚠️  Información del usuario a eliminar:${NC}"
    # "id" muestra UID, GID y grupos del usuario
    id "$nombre_usuario"

    # Solicita confirmación explícita antes de eliminar (acción irreversible)
    echo -e "${RED}ADVERTENCIA: Esta acción es irreversible.${NC}"
    read -p "$(echo -e "${YELLOW}¿Confirmas la eliminación de '$nombre_usuario'? (escribe 'CONFIRMAR'):${NC} ")" confirmacion

    # Compara la entrada con la cadena exacta "CONFIRMAR" (distingue mayúsculas)
    if [[ "$confirmacion" != "CONFIRMAR" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    # Pregunta si se desea eliminar también el directorio home del usuario
    read -p "$(echo -e "${CYAN}¿Eliminar también el directorio home? (s/n):${NC} ")" eliminar_home

    if [[ "$eliminar_home" == "s" || "$eliminar_home" == "S" ]]; then
        # "-r" elimina el directorio home y el buzón de correo
        userdel -r "$nombre_usuario"
    else
        # Sin "-r" solo elimina la cuenta, conserva el directorio home
        userdel "$nombre_usuario"
    fi

    if [[ $? -eq 0 ]]; then
        log_accion "ELIMINAR_USUARIO: $nombre_usuario (home_eliminado=$eliminar_home)"
        echo -e "${GREEN}✅ Usuario '$nombre_usuario' eliminado exitosamente.${NC}"
    else
        echo -e "${RED}❌ Error al eliminar el usuario.${NC}"
    fi
}

# ──────────────────────────────────────────────────────────────

# Función: bloquear_usuario
# Bloquea un usuario impidiendo que pueda iniciar sesión.
# El bloqueo NO elimina al usuario ni sus datos; es reversible.
bloquear_usuario() {
    imprimir_titulo "Bloquear usuario"

    read -p "$(echo -e "${CYAN}Nombre del usuario a bloquear:${NC} ")" nombre_usuario

    if ! usuario_existe "$nombre_usuario"; then
        echo -e "${RED}❌ El usuario '$nombre_usuario' no existe.${NC}"
        return 1
    fi

    # Bloqueo método 1: "usermod -L" añade un "!" al inicio del hash de contraseña
    # en /etc/shadow, lo que hace que la contraseña sea inválida
    usermod -L "$nombre_usuario"

    # Bloqueo método 2: establece la fecha de expiración de la cuenta en el pasado (día 1)
    # Esto previene el acceso incluso con claves SSH
    # "chage -E 1" establece expiración el 2 de enero de 1970 (época UNIX)
    chage -E 1 "$nombre_usuario"

    if [[ $? -eq 0 ]]; then
        log_accion "BLOQUEAR_USUARIO: $nombre_usuario"
        echo -e "${GREEN}✅ Usuario '$nombre_usuario' bloqueado correctamente.${NC}"
        echo -e "${YELLOW}   El usuario no podrá iniciar sesión hasta ser desbloqueado.${NC}"
    else
        echo -e "${RED}❌ Error al bloquear el usuario.${NC}"
    fi
}

# ──────────────────────────────────────────────────────────────

# Función: desbloquear_usuario
# Desbloquea un usuario previamente bloqueado, restaurando su acceso.
desbloquear_usuario() {
    imprimir_titulo "Desbloquear usuario"

    read -p "$(echo -e "${CYAN}Nombre del usuario a desbloquear:${NC} ")" nombre_usuario

    if ! usuario_existe "$nombre_usuario"; then
        echo -e "${RED}❌ El usuario '$nombre_usuario' no existe.${NC}"
        return 1
    fi

    # Desbloqueo método 1: "usermod -U" elimina el "!" del hash de contraseña
    usermod -U "$nombre_usuario"

    # Desbloqueo método 2: elimina la fecha de expiración de la cuenta
    # "-E -1" establece que la cuenta nunca expira
    chage -E -1 "$nombre_usuario"

    # Fuerza al usuario a cambiar su contraseña al volver a iniciar sesión
    # Esto asegura que se establezca una contraseña nueva y segura
    chage -d 0 "$nombre_usuario"

    if [[ $? -eq 0 ]]; then
        log_accion "DESBLOQUEAR_USUARIO: $nombre_usuario"
        echo -e "${GREEN}✅ Usuario '$nombre_usuario' desbloqueado correctamente.${NC}"
        echo -e "${YELLOW}   Deberá cambiar su contraseña en el próximo inicio de sesión.${NC}"
    else
        echo -e "${RED}❌ Error al desbloquear el usuario.${NC}"
    fi
}

# ──────────────────────────────────────────────────────────────

# Función: listar_usuarios
# Lista todos los usuarios del sistema con información relevante.
listar_usuarios() {
    imprimir_titulo "Listado de usuarios del sistema"

    echo -e "${CYAN}USUARIOS LOCALES (UID >= 1000 o del sistema):${NC}\n"

    # Formato de encabezado de tabla
    printf "%-20s %-6s %-20s %-20s %-15s\n" "USUARIO" "UID" "GRUPO PRINCIPAL" "SHELL" "ESTADO"
    printf "%-20s %-6s %-20s %-20s %-15s\n" "-------" "---" "---------------" "-----" "------"

    # Lee el archivo /etc/passwd que contiene todos los usuarios del sistema
    # Formato de /etc/passwd: usuario:contraseña:UID:GID:GECOS:home:shell
    # "IFS=:" establece ":" como separador de campos para el bucle while read
    while IFS=: read -r usuario password uid gid gecos home shell; do

        # Solo muestra usuarios con UID >= 1000 (usuarios humanos en Ubuntu)
        # o los usuarios especiales de Tornalyx (UID 1001-1004)
        # Los usuarios del sistema tienen UID < 1000
        if [[ $uid -ge 1000 ]]; then

            # Obtiene el nombre del grupo principal a partir del GID
            # "getent group $gid" busca el grupo en la base de datos del sistema
            # "cut -d: -f1" extrae solo el nombre del grupo (primer campo)
            grupo=$(getent group "$gid" | cut -d: -f1)

            # Verifica el estado del usuario consultando /etc/shadow
            # grep busca la línea del usuario; "cut -d: -f2" extrae el hash de contraseña
            hash_pass=$(getent shadow "$usuario" 2>/dev/null | cut -d: -f2)

            # Si el hash empieza con "!" el usuario está bloqueado
            if [[ "$hash_pass" == \!* ]]; then
                estado="${RED}BLOQUEADO${NC}"
            elif [[ "$shell" == "/usr/sbin/nologin" || "$shell" == "/bin/false" ]]; then
                estado="${YELLOW}SIN LOGIN${NC}"
            else
                estado="${GREEN}ACTIVO${NC}"
            fi

            # printf con -e para interpretar los códigos de color
            printf "%-20s %-6s %-20s %-20s " "$usuario" "$uid" "${grupo:-N/A}" "$shell"
            echo -e "$estado"
        fi

    done < /etc/passwd  # Redirige /etc/passwd como entrada del bucle while

    echo ""
    echo -e "${CYAN}RESUMEN:${NC}"
    # Cuenta usuarios con UID >= 1000 en /etc/passwd
    total=$(awk -F: '$3 >= 1000 {count++} END {print count}' /etc/passwd)
    echo -e "  Total de usuarios: ${GREEN}$total${NC}"

    log_accion "LISTAR_USUARIOS"
}

# ──────────────────────────────────────────────────────────────
# SECCIÓN 4: MENÚ PRINCIPAL
# ──────────────────────────────────────────────────────────────

# Función: mostrar_menu
# Muestra el menú interactivo principal y procesa la opción seleccionada.
mostrar_menu() {
    while true; do
        # "clear" limpia la pantalla del terminal para una mejor presentación
        clear

        echo -e "${BLUE}"
        echo "  ╔══════════════════════════════════════════╗"
        echo "  ║   🏆 TORNALYX – Gestión de Usuarios     ║"
        echo "  ║   Sistema de Gestión Deportiva Modular  ║"
        echo "  ╚══════════════════════════════════════════╝"
        echo -e "${NC}"
        echo -e "  ${CYAN}1.${NC} Crear usuario"
        echo -e "  ${CYAN}2.${NC} Eliminar usuario"
        echo -e "  ${CYAN}3.${NC} Bloquear usuario"
        echo -e "  ${CYAN}4.${NC} Desbloquear usuario"
        echo -e "  ${CYAN}5.${NC} Listar todos los usuarios"
        echo -e "  ${RED}0.${NC} Salir"
        echo ""

        # Solicita al operador que elija una opción del menú
        read -p "$(echo -e "${YELLOW}Selecciona una opción [0-5]:${NC} ")" opcion

        # Estructura case/esac: evalúa la opción seleccionada
        # Cada patrón termina con ";;" que actúa como "break"
        case "$opcion" in
            1) crear_usuario ;;       # Llamada a la función crear_usuario
            2) eliminar_usuario ;;    # Llamada a la función eliminar_usuario
            3) bloquear_usuario ;;    # Llamada a la función bloquear_usuario
            4) desbloquear_usuario ;; # Llamada a la función desbloquear_usuario
            5) listar_usuarios ;;     # Llamada a la función listar_usuarios
            0)
                echo -e "\n${GREEN}✅ Saliendo de la gestión de usuarios. ¡Hasta luego!${NC}\n"
                log_accion "FIN_SESION: script cerrado por el operador"
                exit 0  # Termina el script con código de éxito (0)
                ;;
            *)
                # Cualquier otra entrada no reconocida
                echo -e "${RED}❌ Opción inválida. Por favor selecciona entre 0 y 5.${NC}"
                ;;
        esac

        # Pausa para que el operador pueda leer el resultado antes de continuar
        echo ""
        read -p "$(echo -e "${YELLOW}Presiona ENTER para continuar...${NC}")"
    done
}

# ──────────────────────────────────────────────────────────────
# SECCIÓN 5: PUNTO DE ENTRADA DEL SCRIPT
# ──────────────────────────────────────────────────────────────

# Verifica privilegios de root antes de ejecutar cualquier función
# Si no hay privilegios, el script termina aquí con un mensaje de error
verificar_root

# Crea el directorio de logs si no existe
# "-p" crea también los directorios padres si es necesario (no da error si ya existe)
mkdir -p "$(dirname "$LOG_FILE")"

# Registra el inicio de la sesión de gestión de usuarios
log_accion "INICIO_SESION: script iniciado"

# Inicia el bucle del menú principal
# El script permanece en este bucle hasta que el usuario elige la opción 0 (Salir)
mostrar_menu
