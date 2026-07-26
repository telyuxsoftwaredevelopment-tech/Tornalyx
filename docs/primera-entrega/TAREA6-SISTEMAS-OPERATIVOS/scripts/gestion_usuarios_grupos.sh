#!/bin/bash
# ============================================================
# TORNALYX – SGDM
# Script de gestión de usuarios y grupos del sistema operativo
# Tarea 6 – Sistemas Operativos | Primera Entrega
#
# Menú de gestión con las cuatro operaciones básicas:
#   1) Crear usuario
#   2) Crear grupo
#   3) Asignar un usuario a un grupo
#   4) Listar usuarios y grupos
#
# Uso: sudo bash gestion_usuarios_grupos.sh
# ============================================================

# ──────────────────────────────────────────────────────────────
# SECCIÓN 1: CONSTANTES
# ──────────────────────────────────────────────────────────────

# Códigos ANSI de color para la salida en terminal
RED='\033[0;31m'      # Rojo: errores
GREEN='\033[0;32m'    # Verde: operaciones exitosas
YELLOW='\033[1;33m'   # Amarillo: advertencias
BLUE='\033[0;34m'     # Azul: títulos
CYAN='\033[0;36m'     # Cian: datos que se piden al operador
NC='\033[0m'          # NC = No Color: vuelve al color por defecto

# Archivo donde queda registrada cada acción, con fecha y responsable
LOG_FILE="/var/log/tornalyx/gestion_usuarios_grupos.log"

# UID/GID a partir del cual AlmaLinux numera las cuentas y los grupos
# creados por personas. Por debajo de 1000 están los del propio sistema.
UID_MINIMO=1000

# ──────────────────────────────────────────────────────────────
# SECCIÓN 2: FUNCIONES AUXILIARES
# ──────────────────────────────────────────────────────────────

# Registra una acción en el archivo de log.
# $1 - Mensaje a registrar
log_accion() {
    # date arma la marca de tiempo y whoami identifica a quien ejecutó el script.
    # >> agrega al final del archivo en lugar de sobrescribirlo.
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$(whoami)] $1" >> "$LOG_FILE" 2>/dev/null
}

# Imprime el encabezado de una sección del menú.
# $1 - Texto del título
imprimir_titulo() {
    echo -e "\n${BLUE}══════════════════════════════════════════${NC}"
    echo -e "${BLUE}  TORNALYX – $1${NC}"
    echo -e "${BLUE}══════════════════════════════════════════${NC}\n"
}

# Corta la ejecución si el script no corre con privilegios de superusuario.
# useradd, groupadd y usermod modifican /etc/passwd y /etc/group, así que
# sin root ninguna de las operaciones del menú podría completarse.
verificar_root() {
    # EUID es el UID efectivo del proceso; el de root siempre es 0.
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}Error: este script debe ejecutarse con sudo o como root.${NC}"
        echo -e "${YELLOW}   Uso: sudo bash gestion_usuarios_grupos.sh${NC}"
        exit 1
    fi
}

# Valida un nombre de usuario o de grupo según las reglas de Linux:
# empieza con letra minúscula o guion bajo, sigue con minúsculas, números,
# guion o guion bajo, y mide entre 3 y 32 caracteres.
# $1 - Nombre a validar
# Devuelve 0 si es válido, 1 si no.
validar_nombre() {
    local nombre="$1"

    # Sin nombre no hay nada que validar: evita crear cuentas vacías si el
    # operador aprieta ENTER de más.
    if [[ -z "$nombre" ]]; then
        echo -e "${RED}El nombre no puede quedar vacío.${NC}"
        return 1
    fi

    # =~ compara contra una expresión regular:
    #   ^[a-z_]      → primer carácter: minúscula o guion bajo
    #   [a-z0-9_-]*  → resto: minúsculas, números, guion bajo o guion
    #   $            → fin de la cadena
    if [[ ! "$nombre" =~ ^[a-z_][a-z0-9_-]*$ ]]; then
        echo -e "${RED}Nombre inválido: '$nombre'${NC}"
        echo -e "${YELLOW}   Solo minúsculas, números, _ y - (debe iniciar con letra o _).${NC}"
        return 1
    fi

    # ${#nombre} devuelve la cantidad de caracteres de la variable.
    if [[ ${#nombre} -lt 3 || ${#nombre} -gt 32 ]]; then
        echo -e "${RED}El nombre debe tener entre 3 y 32 caracteres.${NC}"
        return 1
    fi

    return 0
}

# Indica si un usuario existe en el sistema.
# $1 - Nombre de usuario
usuario_existe() {
    # getent consulta la base de datos de usuarios (/etc/passwd y también
    # LDAP o NIS si el servidor los usara). Devuelve 0 si encontró la cuenta.
    getent passwd "$1" &>/dev/null
}

# Indica si un grupo existe en el sistema.
# $1 - Nombre de grupo
grupo_existe() {
    getent group "$1" &>/dev/null
}

# ──────────────────────────────────────────────────────────────
# SECCIÓN 3: OPERACIONES DEL MENÚ
# ──────────────────────────────────────────────────────────────

# Opción 1 · Crea una cuenta de usuario con su directorio home y su contraseña.
crear_usuario() {
    imprimir_titulo "Crear usuario"

    read -p "$(echo -e "${CYAN}Nombre de usuario:${NC} ")" nombre_usuario

    # || return 1 corta la función si la validación falló.
    validar_nombre "$nombre_usuario" || return 1

    if usuario_existe "$nombre_usuario"; then
        echo -e "${YELLOW}El usuario '$nombre_usuario' ya existe en el sistema.${NC}"
        return 1
    fi

    # El campo GECOS guarda el nombre real de la persona: sirve para saber
    # de quién es la cuenta cuando se audita el servidor meses después.
    read -p "$(echo -e "${CYAN}Nombre completo:${NC} ")" nombre_completo

    # Un usuario sin shell interactiva no puede iniciar sesión. Es lo correcto
    # para cuentas de servicio (respaldos, procesos automáticos).
    read -p "$(echo -e "${CYAN}¿Puede iniciar sesión? (s/n):${NC} ")" con_login

    local shell_usuario
    if [[ "$con_login" == "s" || "$con_login" == "S" ]]; then
        shell_usuario="/bin/bash"
    else
        shell_usuario="/usr/sbin/nologin"
    fi

    # useradd crea la cuenta:
    #   -m  crea el directorio home
    #   -c  guarda el nombre completo en el campo GECOS
    #   -s  define la shell
    if ! useradd -m -c "$nombre_completo" -s "$shell_usuario" "$nombre_usuario"; then
        echo -e "${RED}No se pudo crear el usuario.${NC}"
        return 1
    fi

    # Solo tiene sentido pedir contraseña si la cuenta puede iniciar sesión.
    if [[ "$shell_usuario" == "/bin/bash" ]]; then
        echo -e "\n${CYAN}Contraseña para '$nombre_usuario':${NC}"
        passwd "$nombre_usuario"

        # chage -d 0 marca la contraseña como vencida: el sistema obliga a
        # cambiarla en el primer inicio de sesión, así la que puso el
        # administrador no queda como definitiva.
        chage -d 0 "$nombre_usuario"
    fi

    log_accion "CREAR_USUARIO: $nombre_usuario (shell=$shell_usuario)"
    echo -e "\n${GREEN}Usuario '$nombre_usuario' creado correctamente.${NC}"
}

# Opción 2 · Crea un grupo del sistema.
crear_grupo() {
    imprimir_titulo "Crear grupo"

    read -p "$(echo -e "${CYAN}Nombre del grupo:${NC} ")" nombre_grupo

    validar_nombre "$nombre_grupo" || return 1

    if grupo_existe "$nombre_grupo"; then
        echo -e "${YELLOW}El grupo '$nombre_grupo' ya existe en el sistema.${NC}"
        return 1
    fi

    # groupadd da de alta el grupo en /etc/group y le asigna el primer GID
    # libre a partir de 1000.
    if ! groupadd "$nombre_grupo"; then
        echo -e "${RED}No se pudo crear el grupo.${NC}"
        return 1
    fi

    # Recupera el GID asignado para mostrarlo: el tercer campo de /etc/group.
    local gid
    gid=$(getent group "$nombre_grupo" | cut -d: -f3)

    log_accion "CREAR_GRUPO: $nombre_grupo (GID=$gid)"
    echo -e "\n${GREEN}Grupo '$nombre_grupo' creado correctamente (GID $gid).${NC}"
}

# Opción 3 · Agrega un usuario existente a un grupo existente.
asignar_usuario_a_grupo() {
    imprimir_titulo "Asignar usuario a grupo"

    read -p "$(echo -e "${CYAN}Usuario:${NC} ")" nombre_usuario

    if ! usuario_existe "$nombre_usuario"; then
        echo -e "${RED}El usuario '$nombre_usuario' no existe.${NC}"
        echo -e "${YELLOW}   Crealo primero con la opción 1 del menú.${NC}"
        return 1
    fi

    read -p "$(echo -e "${CYAN}Grupo:${NC} ")" nombre_grupo

    if ! grupo_existe "$nombre_grupo"; then
        echo -e "${RED}El grupo '$nombre_grupo' no existe.${NC}"
        echo -e "${YELLOW}   Crealo primero con la opción 2 del menú.${NC}"
        return 1
    fi

    # id -nG lista los grupos a los que ya pertenece el usuario.
    # El patrón con espacios alrededor evita falsos positivos: sin él,
    # buscar "web" también coincidiría con "web_admin".
    if [[ " $(id -nG "$nombre_usuario") " == *" $nombre_grupo "* ]]; then
        echo -e "${YELLOW}'$nombre_usuario' ya pertenece al grupo '$nombre_grupo'.${NC}"
        return 0
    fi

    # usermod -aG agrega el usuario al grupo.
    #   -G  indica los grupos secundarios
    #   -a  (append) es imprescindible: sin esta opción, -G REEMPLAZA todos
    #       los grupos del usuario y lo saca de los que ya tenía.
    if ! usermod -aG "$nombre_grupo" "$nombre_usuario"; then
        echo -e "${RED}No se pudo asignar el usuario al grupo.${NC}"
        return 1
    fi

    log_accion "ASIGNAR_GRUPO: $nombre_usuario -> $nombre_grupo"
    echo -e "\n${GREEN}'$nombre_usuario' agregado al grupo '$nombre_grupo'.${NC}"
    echo -e "${CYAN}Grupos actuales:${NC} $(id -nG "$nombre_usuario")"
    echo -e "${YELLOW}   El cambio se aplica al iniciar sesión de nuevo.${NC}"
}

# Opción 4 · Muestra dos tablas: los usuarios y los grupos del sistema.
listar_usuarios_y_grupos() {
    imprimir_titulo "Usuarios y grupos del sistema"

    echo -e "${CYAN}USUARIOS (UID >= $UID_MINIMO)${NC}\n"
    printf "%-18s %-7s %-16s %-22s %s\n" "USUARIO" "UID" "GRUPO PRINCIPAL" "SHELL" "GRUPOS SECUNDARIOS"
    printf "%-18s %-7s %-16s %-22s %s\n" "-------" "---" "---------------" "-----" "------------------"

    local total_usuarios=0

    # /etc/passwd tiene un usuario por línea, con los campos separados por ":"
    #   usuario:contraseña:UID:GID:nombre completo:home:shell
    # IFS=: hace que read parta cada línea usando ":" como separador.
    while IFS=: read -r usuario _ uid gid _ _ shell; do

        # Deja afuera las cuentas internas del sistema (UID < 1000) y a
        # "nobody", que usa el UID 65534.
        if [[ "$uid" -ge "$UID_MINIMO" && "$uid" -ne 65534 ]]; then

            # El GID del cuarto campo identifica al grupo principal;
            # getent lo traduce a nombre.
            local grupo_principal
            grupo_principal=$(getent group "$gid" | cut -d: -f1)

            # Los grupos secundarios son todos los de id -nG menos el principal.
            local secundarios
            secundarios=$(id -nG "$usuario" 2>/dev/null | tr ' ' '\n' \
                          | grep -v "^${grupo_principal}$" | paste -sd ',' -)

            printf "%-18s %-7s %-16s %-22s %s\n" \
                   "$usuario" "$uid" "${grupo_principal:-?}" "$shell" "${secundarios:--}"

            total_usuarios=$((total_usuarios + 1))
        fi
    done < /etc/passwd

    echo -e "\n${CYAN}GRUPOS (GID >= $UID_MINIMO)${NC}\n"
    printf "%-24s %-8s %s\n" "GRUPO" "GID" "MIEMBROS"
    printf "%-24s %-8s %s\n" "-----" "---" "--------"

    local total_grupos=0

    # /etc/group tiene el formato:  grupo:contraseña:GID:miembros
    while IFS=: read -r grupo _ gid miembros; do
        if [[ "$gid" -ge "$UID_MINIMO" && "$gid" -ne 65534 ]]; then
            printf "%-24s %-8s %s\n" "$grupo" "$gid" "${miembros:--}"
            total_grupos=$((total_grupos + 1))
        fi
    done < /etc/group

    echo ""
    echo -e "${CYAN}RESUMEN:${NC}"
    echo -e "  Usuarios: ${GREEN}${total_usuarios}${NC}   Grupos: ${GREEN}${total_grupos}${NC}"

    log_accion "LISTAR_USUARIOS_Y_GRUPOS"
}

# ──────────────────────────────────────────────────────────────
# SECCIÓN 4: MENÚ PRINCIPAL
# ──────────────────────────────────────────────────────────────

mostrar_menu() {
    # Repite el menú hasta que el operador elija salir.
    while true; do
        clear

        echo -e "${BLUE}"
        echo "  ╔══════════════════════════════════════════╗"
        echo "  ║   TORNALYX – Gestión de usuarios         ║"
        echo "  ║   y grupos del sistema                   ║"
        echo "  ╚══════════════════════════════════════════╝"
        echo -e "${NC}"
        echo -e "  ${CYAN}1.${NC} Crear usuario"
        echo -e "  ${CYAN}2.${NC} Crear grupo"
        echo -e "  ${CYAN}3.${NC} Asignar usuario a grupo"
        echo -e "  ${CYAN}4.${NC} Listar usuarios y grupos"
        echo -e "  ${RED}0.${NC} Salir"
        echo ""

        read -p "$(echo -e "${YELLOW}Seleccioná una opción [0-4]:${NC} ")" opcion

        # case compara la opción contra cada patrón; ";;" cierra cada rama.
        case "$opcion" in
            1) crear_usuario ;;
            2) crear_grupo ;;
            3) asignar_usuario_a_grupo ;;
            4) listar_usuarios_y_grupos ;;
            0)
                echo -e "\n${GREEN}Saliendo de la gestión de usuarios y grupos.${NC}\n"
                log_accion "FIN_SESION"
                exit 0
                ;;
            *)
                echo -e "${RED}Opción inválida: elegí un número entre 0 y 4.${NC}"
                ;;
        esac

        echo ""
        read -p "$(echo -e "${YELLOW}Presioná ENTER para continuar...${NC}")"
    done
}

# ──────────────────────────────────────────────────────────────
# SECCIÓN 5: PUNTO DE ENTRADA
# ──────────────────────────────────────────────────────────────

verificar_root

# -p crea los directorios intermedios y no falla si el directorio ya existe.
mkdir -p "$(dirname "$LOG_FILE")"

log_accion "INICIO_SESION"

mostrar_menu
