#!/bin/bash
# ============================================================
# TORNALYX – SGDM
# Script de gestión de permisos de archivos y directorios
# Tarea 6 – Sistemas Operativos | Primera Entrega
#
# Menú:
#   1) Ver permisos de un archivo o directorio
#   2) Cambiar permisos (chmod)
#   3) Cambiar propietario y grupo (chown)
#   4) Aplicar los permisos recomendados a la aplicación web
#   5) Auditar permisos peligrosos (777 y SUID)
#
# Uso: sudo bash gestion_permisos.sh
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

LOG_FILE="/var/log/tornalyx/gestion_permisos.log"

# Directorio donde queda publicada la aplicación en el servidor.
DIR_APP="/var/www/tornalyx"

# Usuario y grupo con los que corre Apache en Debian y Ubuntu.
USUARIO_WEB="www-data"
GRUPO_WEB="www-data"

# ──────────────────────────────────────────────────────────────
# FUNCIONES AUXILIARES
# ──────────────────────────────────────────────────────────────

log_accion() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$(whoami)] $1" >> "$LOG_FILE" 2>/dev/null
}

imprimir_titulo() {
    echo -e "\n${BLUE}══════════════════════════════════════════${NC}"
    echo -e "${BLUE}  TORNALYX – $1${NC}"
    echo -e "${BLUE}══════════════════════════════════════════${NC}\n"
}

verificar_root() {
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}Error: este script debe ejecutarse con sudo o como root.${NC}"
        echo -e "${YELLOW}   Uso: sudo bash gestion_permisos.sh${NC}"
        exit 1
    fi
}

# Comprueba que una ruta exista antes de operar sobre ella.
# $1 - Ruta a verificar
validar_ruta() {
    local ruta="$1"

    if [[ -z "$ruta" ]]; then
        echo -e "${RED}Tenés que indicar una ruta.${NC}"
        return 1
    fi

    # -e es verdadero si la ruta existe, sea archivo, directorio o enlace.
    if [[ ! -e "$ruta" ]]; then
        echo -e "${RED}La ruta '$ruta' no existe.${NC}"
        return 1
    fi

    return 0
}

# Valida que el modo de permisos esté en notación octal de 3 o 4 dígitos,
# con cada dígito entre 0 y 7 (por ejemplo 640, 755 o 2750).
# $1 - Modo a validar
validar_modo_octal() {
    local modo="$1"

    if [[ ! "$modo" =~ ^[0-7]{3,4}$ ]]; then
        echo -e "${RED}Modo inválido: '$modo'${NC}"
        echo -e "${YELLOW}   Usá notación octal, por ejemplo 640, 755 o 2750.${NC}"
        return 1
    fi

    return 0
}

# Traduce un dígito octal a su lectura en texto, para que el operador
# entienda qué está por aplicar antes de confirmarlo.
# $1 - Un dígito del 0 al 7
explicar_digito() {
    case "$1" in
        0) echo "sin permisos" ;;
        1) echo "ejecución" ;;
        2) echo "escritura" ;;
        3) echo "escritura y ejecución" ;;
        4) echo "lectura" ;;
        5) echo "lectura y ejecución" ;;
        6) echo "lectura y escritura" ;;
        7) echo "lectura, escritura y ejecución" ;;
        *) echo "desconocido" ;;
    esac
}

# ──────────────────────────────────────────────────────────────
# OPCIONES DEL MENÚ
# ──────────────────────────────────────────────────────────────

# Opción 1 · Muestra permisos, dueño y grupo de una ruta.
ver_permisos() {
    imprimir_titulo "Ver permisos"

    read -p "$(echo -e "${CYAN}Ruta del archivo o directorio:${NC} ")" ruta
    validar_ruta "$ruta" || return 1

    # stat muestra los metadatos del archivo con el formato que le pidamos:
    #   %A  permisos en formato simbólico (-rw-r--r--)
    #   %a  permisos en octal (644)
    #   %U  nombre del dueño        %G  nombre del grupo
    #   %s  tamaño en bytes         %y  fecha de última modificación
    echo -e "${CYAN}Ruta:${NC}        $ruta"
    echo -e "${CYAN}Permisos:${NC}    $(stat -c '%A  (%a)' "$ruta")"
    echo -e "${CYAN}Propietario:${NC} $(stat -c '%U' "$ruta")"
    echo -e "${CYAN}Grupo:${NC}       $(stat -c '%G' "$ruta")"
    echo -e "${CYAN}Tamaño:${NC}      $(stat -c '%s' "$ruta") bytes"
    echo -e "${CYAN}Modificado:${NC}  $(stat -c '%y' "$ruta")"

    # Si es un directorio, muestra también lo que contiene.
    if [[ -d "$ruta" ]]; then
        echo -e "\n${CYAN}Contenido del directorio:${NC}"
        # -l formato largo, -a incluye ocultos, -h tamaños legibles
        ls -lah "$ruta"
    fi

    log_accion "VER_PERMISOS: $ruta"
}

# Opción 2 · Cambia los permisos de una ruta con chmod.
cambiar_permisos() {
    imprimir_titulo "Cambiar permisos (chmod)"

    read -p "$(echo -e "${CYAN}Ruta:${NC} ")" ruta
    validar_ruta "$ruta" || return 1

    echo -e "\n${CYAN}Permisos actuales:${NC} $(stat -c '%A (%a)' "$ruta")"

    echo -e "\n${YELLOW}Recordatorio de la notación octal:${NC}"
    echo "  Los tres dígitos son, en orden: dueño, grupo y otros."
    echo "  4 = leer   2 = escribir   1 = ejecutar   (se suman entre sí)"
    echo "  Ejemplos:  640 = rw- r-- ---     755 = rwx r-x r-x"
    echo ""

    read -p "$(echo -e "${CYAN}Nuevo modo en octal:${NC} ")" modo
    validar_modo_octal "$modo" || return 1

    # Toma los últimos tres dígitos para explicarlos: si el operador escribió
    # cuatro, el primero corresponde a los bits especiales (SUID/SGID/sticky).
    local basicos="${modo: -3}"
    echo -e "\n${YELLOW}Vas a aplicar:${NC}"
    echo "  Dueño:  $(explicar_digito "${basicos:0:1}")"
    echo "  Grupo:  $(explicar_digito "${basicos:1:1}")"
    echo "  Otros:  $(explicar_digito "${basicos:2:1}")"

    local recursivo=""
    if [[ -d "$ruta" ]]; then
        read -p "$(echo -e "${CYAN}¿Aplicar a todo el contenido del directorio? (s/n):${NC} ")" resp
        # -R aplica el cambio a los subdirectorios y archivos de adentro.
        [[ "$resp" == "s" || "$resp" == "S" ]] && recursivo="-R"
    fi

    read -p "$(echo -e "${YELLOW}¿Confirmás el cambio? (s/n):${NC} ")" confirmacion
    if [[ "$confirmacion" != "s" && "$confirmacion" != "S" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    if ! chmod $recursivo "$modo" "$ruta"; then
        echo -e "${RED}No se pudieron cambiar los permisos.${NC}"
        return 1
    fi

    log_accion "CHMOD: $ruta -> $modo ${recursivo:+recursivo}"
    echo -e "\n${GREEN}Permisos actualizados: $(stat -c '%A (%a)' "$ruta")${NC}"
}

# Opción 3 · Cambia el propietario y el grupo de una ruta con chown.
cambiar_propietario() {
    imprimir_titulo "Cambiar propietario (chown)"

    read -p "$(echo -e "${CYAN}Ruta:${NC} ")" ruta
    validar_ruta "$ruta" || return 1

    echo -e "\n${CYAN}Dueño actual:${NC} $(stat -c '%U:%G' "$ruta")"

    read -p "$(echo -e "${CYAN}Nuevo propietario:${NC} ")" nuevo_usuario

    # Sin este control, chown fallaría a mitad de un cambio recursivo.
    if ! getent passwd "$nuevo_usuario" &>/dev/null; then
        echo -e "${RED}El usuario '$nuevo_usuario' no existe en el sistema.${NC}"
        return 1
    fi

    read -p "$(echo -e "${CYAN}Nuevo grupo (ENTER para dejar el actual):${NC} ")" nuevo_grupo

    local destino="$nuevo_usuario"
    if [[ -n "$nuevo_grupo" ]]; then
        if ! getent group "$nuevo_grupo" &>/dev/null; then
            echo -e "${RED}El grupo '$nuevo_grupo' no existe en el sistema.${NC}"
            return 1
        fi
        # chown acepta el formato usuario:grupo para cambiar ambos de una vez.
        destino="${nuevo_usuario}:${nuevo_grupo}"
    fi

    local recursivo=""
    if [[ -d "$ruta" ]]; then
        read -p "$(echo -e "${CYAN}¿Aplicar a todo el contenido? (s/n):${NC} ")" resp
        [[ "$resp" == "s" || "$resp" == "S" ]] && recursivo="-R"
    fi

    if ! chown $recursivo "$destino" "$ruta"; then
        echo -e "${RED}No se pudo cambiar el propietario.${NC}"
        return 1
    fi

    log_accion "CHOWN: $ruta -> $destino ${recursivo:+recursivo}"
    echo -e "\n${GREEN}Propietario actualizado: $(stat -c '%U:%G' "$ruta")${NC}"
}

# Opción 4 · Deja la aplicación web con los permisos que corresponden.
aplicar_permisos_app() {
    imprimir_titulo "Permisos recomendados para la aplicación"

    if [[ ! -d "$DIR_APP" ]]; then
        echo -e "${RED}No encontré el directorio de la aplicación: $DIR_APP${NC}"
        echo -e "${YELLOW}   Editá la variable DIR_APP al principio del script.${NC}"
        return 1
    fi

    echo -e "${CYAN}Se va a aplicar sobre:${NC} $DIR_APP\n"
    echo "  Propietario: root, grupo $GRUPO_WEB"
    echo "  Directorios: 750  (el dueño entra y escribe; Apache solo entra y lee)"
    echo "  Archivos:    640  (el dueño lee y escribe; Apache solo lee)"
    echo "  Escritura para Apache solo en el directorio de subidas."
    echo ""
    echo -e "${YELLOW}El criterio es que Apache no pueda modificar el código que ejecuta:${NC}"
    echo -e "${YELLOW}si alguien encontrara una falla en la web, no podría reescribir${NC}"
    echo -e "${YELLOW}los archivos PHP del servidor.${NC}"
    echo ""

    read -p "$(echo -e "${YELLOW}¿Confirmás? (s/n):${NC} ")" confirmacion
    if [[ "$confirmacion" != "s" && "$confirmacion" != "S" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    # El código pertenece a root; Apache accede a través del grupo.
    chown -R "root:${GRUPO_WEB}" "$DIR_APP"

    # find recorre la ruta y ejecuta chmod sobre cada coincidencia:
    #   -type d  solo directorios     -type f  solo archivos
    #   {} es la ruta encontrada y + agrupa varias por llamada (más rápido)
    find "$DIR_APP" -type d -exec chmod 750 {} +
    find "$DIR_APP" -type f -exec chmod 640 {} +

    # Los directorios donde la aplicación guarda archivos sí necesitan que
    # Apache pueda escribir, así que se les da el permiso por separado.
    local dir_subidas="${DIR_APP}/public/uploads"
    if [[ -d "$dir_subidas" ]]; then
        chown -R "${USUARIO_WEB}:${GRUPO_WEB}" "$dir_subidas"
        chmod -R 770 "$dir_subidas"
        echo -e "${GREEN}Permisos de escritura habilitados en $dir_subidas${NC}"
    fi

    # El archivo .env guarda la contraseña de la base de datos: solo lo lee
    # su dueño y el grupo de Apache, nadie más.
    local env="${DIR_APP}/.env"
    if [[ -f "$env" ]]; then
        chmod 640 "$env"
        echo -e "${GREEN}Archivo .env restringido a 640.${NC}"
    fi

    log_accion "PERMISOS_APP: aplicados sobre $DIR_APP"
    echo -e "\n${GREEN}Permisos aplicados correctamente.${NC}"
}

# Opción 5 · Busca permisos que suelen ser un problema de seguridad.
auditar_permisos() {
    imprimir_titulo "Auditoría de permisos"

    read -p "$(echo -e "${CYAN}Directorio a revisar [$DIR_APP]:${NC} ")" ruta
    # Si el operador aprieta ENTER, se usa el valor por defecto entre corchetes.
    ruta="${ruta:-$DIR_APP}"

    validar_ruta "$ruta" || return 1

    echo -e "\n${CYAN}1. Archivos con permisos 777 (cualquiera puede escribirlos)${NC}"
    # -perm 0777 busca la coincidencia exacta con ese modo.
    local abiertos
    abiertos=$(find "$ruta" -type f -perm 0777 2>/dev/null)
    if [[ -z "$abiertos" ]]; then
        echo -e "  ${GREEN}Ninguno.${NC}"
    else
        echo -e "${RED}$abiertos${NC}"
        echo -e "  ${YELLOW}Conviene bajarlos a 640 con la opción 2.${NC}"
    fi

    echo -e "\n${CYAN}2. Archivos con SUID o SGID${NC}"
    echo -e "  ${YELLOW}Estos archivos se ejecutan con los permisos de su dueño,${NC}"
    echo -e "  ${YELLOW}no con los de quien los ejecuta.${NC}"
    # -4000 es el bit SUID y -2000 el SGID; el guion busca "que tenga ese bit".
    local especiales
    especiales=$(find "$ruta" -type f \( -perm -4000 -o -perm -2000 \) 2>/dev/null)
    if [[ -z "$especiales" ]]; then
        echo -e "  ${GREEN}Ninguno.${NC}"
    else
        echo -e "${YELLOW}$especiales${NC}"
    fi

    echo -e "\n${CYAN}3. Archivos sin dueño${NC}"
    echo -e "  ${YELLOW}Suelen quedar cuando se borra un usuario sin limpiar sus archivos.${NC}"
    local huerfanos
    huerfanos=$(find "$ruta" \( -nouser -o -nogroup \) 2>/dev/null)
    if [[ -z "$huerfanos" ]]; then
        echo -e "  ${GREEN}Ninguno.${NC}"
    else
        echo -e "${YELLOW}$huerfanos${NC}"
    fi

    log_accion "AUDITAR_PERMISOS: $ruta"
}

# ──────────────────────────────────────────────────────────────
# MENÚ PRINCIPAL
# ──────────────────────────────────────────────────────────────

mostrar_menu() {
    while true; do
        clear

        echo -e "${BLUE}"
        echo "  ╔══════════════════════════════════════════╗"
        echo "  ║   TORNALYX – Gestión de permisos         ║"
        echo "  ╚══════════════════════════════════════════╝"
        echo -e "${NC}"
        echo -e "  ${CYAN}1.${NC} Ver permisos de un archivo o directorio"
        echo -e "  ${CYAN}2.${NC} Cambiar permisos (chmod)"
        echo -e "  ${CYAN}3.${NC} Cambiar propietario y grupo (chown)"
        echo -e "  ${CYAN}4.${NC} Aplicar permisos recomendados a la aplicación"
        echo -e "  ${CYAN}5.${NC} Auditar permisos peligrosos"
        echo -e "  ${RED}0.${NC} Salir"
        echo ""

        read -p "$(echo -e "${YELLOW}Seleccioná una opción [0-5]:${NC} ")" opcion

        case "$opcion" in
            1) ver_permisos ;;
            2) cambiar_permisos ;;
            3) cambiar_propietario ;;
            4) aplicar_permisos_app ;;
            5) auditar_permisos ;;
            0)
                echo -e "\n${GREEN}Saliendo de la gestión de permisos.${NC}\n"
                log_accion "FIN_SESION"
                exit 0
                ;;
            *)
                echo -e "${RED}Opción inválida: elegí un número entre 0 y 5.${NC}"
                ;;
        esac

        echo ""
        read -p "$(echo -e "${YELLOW}Presioná ENTER para continuar...${NC}")"
    done
}

verificar_root
mkdir -p "$(dirname "$LOG_FILE")"
log_accion "INICIO_SESION"
mostrar_menu
