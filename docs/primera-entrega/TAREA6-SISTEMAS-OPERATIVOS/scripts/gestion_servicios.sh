#!/bin/bash
# ============================================================
# TORNALYX – SGDM
# Script de gestión de servicios del servidor (systemd)
# Tarea 6 – Sistemas Operativos | Primera Entrega
#
# Menú:
#   1) Ver el estado de todos los servicios
#   2) Iniciar un servicio
#   3) Detener un servicio
#   4) Reiniciar un servicio
#   5) Habilitar o deshabilitar el arranque automático
#   6) Ver los últimos registros de un servicio
#
# Uso: sudo bash gestion_servicios.sh
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

LOG_FILE="/var/log/tornalyx/gestion_servicios.log"

# Lista blanca de servicios que este script puede tocar. Trabajar sobre una
# lista cerrada evita que un error de tipeo detenga algo que no corresponde,
# como el propio servicio de SSH y quedarse sin acceso al servidor.
#
# Los nombres son los de AlmaLinux y el resto de la familia RHEL, que no
# coinciden con los de Debian: acá el servidor web es httpd (no apache2),
# el demonio de SSH es sshd (no ssh), el de tareas programadas es crond
# (no cron) y el firewall es firewalld (no ufw).
SERVICIOS=(httpd mysqld sshd crond firewalld fail2ban)

# Descripción de cada servicio, para que el menú se entienda sin buscar afuera.
descripcion_servicio() {
    case "$1" in
        httpd)     echo "Servidor web que publica la aplicación" ;;
        mysqld)    echo "Base de datos del sistema" ;;
        sshd)      echo "Acceso remoto al servidor" ;;
        crond)     echo "Tareas programadas (respaldos, limpieza)" ;;
        firewalld) echo "Firewall del servidor" ;;
        fail2ban)  echo "Bloqueo de intentos de acceso repetidos" ;;
        *)         echo "-" ;;
    esac
}

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
        echo -e "${YELLOW}   Uso: sudo bash gestion_servicios.sh${NC}"
        exit 1
    fi
}

# Comprueba que systemd esté disponible: sin él, systemctl no sirve.
verificar_systemd() {
    if ! command -v systemctl &>/dev/null; then
        echo -e "${RED}Este script necesita systemd (systemctl no está disponible).${NC}"
        exit 1
    fi
}

# Muestra la lista numerada de servicios y devuelve el que eligió el operador
# en la variable global SERVICIO_ELEGIDO.
elegir_servicio() {
    SERVICIO_ELEGIDO=""

    echo -e "${CYAN}Servicios disponibles:${NC}"

    # ${#SERVICIOS[@]} es la cantidad de elementos del arreglo.
    local i
    for i in "${!SERVICIOS[@]}"; do
        # Los arreglos empiezan en 0, por eso se muestra i+1.
        printf "  %d) %-10s %s\n" "$((i + 1))" "${SERVICIOS[$i]}" "$(descripcion_servicio "${SERVICIOS[$i]}")"
    done
    echo ""

    read -p "$(echo -e "${CYAN}Número de servicio:${NC} ")" numero

    # Rechaza cualquier cosa que no sea un número entero.
    if [[ ! "$numero" =~ ^[0-9]+$ ]]; then
        echo -e "${RED}Tenés que ingresar un número.${NC}"
        return 1
    fi

    if [[ "$numero" -lt 1 || "$numero" -gt ${#SERVICIOS[@]} ]]; then
        echo -e "${RED}Número fuera de rango.${NC}"
        return 1
    fi

    SERVICIO_ELEGIDO="${SERVICIOS[$((numero - 1))]}"

    # Un servicio de la lista puede no estar instalado todavía en el servidor.
    # list-unit-files busca la definición del servicio en el sistema.
    if ! systemctl list-unit-files | grep -q "^${SERVICIO_ELEGIDO}\.service"; then
        echo -e "${YELLOW}Aviso: '$SERVICIO_ELEGIDO' no parece estar instalado.${NC}"
        read -p "$(echo -e "${YELLOW}¿Continuar igual? (s/n):${NC} ")" resp
        if [[ "$resp" != "s" && "$resp" != "S" ]]; then
            return 1
        fi
    fi

    return 0
}

# Imprime el estado de un servicio con color según corresponda.
# $1 - Nombre del servicio
mostrar_estado_corto() {
    local servicio="$1"

    # is-active devuelve active, inactive o failed.
    # || true evita que el script corte cuando el servicio no está corriendo,
    # porque en ese caso systemctl termina con código distinto de cero.
    local activo
    activo=$(systemctl is-active "$servicio" 2>/dev/null || true)

    # is-enabled indica si arranca solo al encender el servidor.
    local habilitado
    habilitado=$(systemctl is-enabled "$servicio" 2>/dev/null || true)

    [[ -z "$activo" ]] && activo="no-instalado"
    [[ -z "$habilitado" ]] && habilitado="-"

    local color="$RED"
    [[ "$activo" == "active" ]] && color="$GREEN"
    [[ "$activo" == "no-instalado" ]] && color="$YELLOW"

    printf "  %-10s ${color}%-14s${NC} %-12s %s\n" \
           "$servicio" "$activo" "$habilitado" "$(descripcion_servicio "$servicio")"
}

# ──────────────────────────────────────────────────────────────
# OPCIONES DEL MENÚ
# ──────────────────────────────────────────────────────────────

# Opción 1 · Tabla con el estado de todos los servicios de la lista.
ver_estado_general() {
    imprimir_titulo "Estado de los servicios"

    printf "  %-10s %-14s %-12s %s\n" "SERVICIO" "ESTADO" "AL ARRANQUE" "DESCRIPCIÓN"
    printf "  %-10s %-14s %-12s %s\n" "--------" "------" "-----------" "-----------"

    local servicio
    for servicio in "${SERVICIOS[@]}"; do
        mostrar_estado_corto "$servicio"
    done

    echo ""
    echo -e "  ${CYAN}active${NC} = corriendo   ${CYAN}inactive${NC} = detenido   ${CYAN}failed${NC} = falló al arrancar"
    echo -e "  ${CYAN}enabled${NC} = arranca solo al encender el servidor"

    log_accion "VER_ESTADO_GENERAL"
}

# Opciones 2, 3 y 4 · Ejecutan la acción de systemctl sobre el servicio elegido.
# $1 - start, stop o restart
operar_servicio() {
    local accion="$1"

    case "$accion" in
        start)   imprimir_titulo "Iniciar servicio" ;;
        stop)    imprimir_titulo "Detener servicio" ;;
        restart) imprimir_titulo "Reiniciar servicio" ;;
    esac

    elegir_servicio || return 1
    local servicio="$SERVICIO_ELEGIDO"

    echo -e "\n${CYAN}Estado actual:${NC} $(systemctl is-active "$servicio" 2>/dev/null || echo desconocido)"

    # Detener sshd deja al administrador sin forma de volver a entrar si está
    # trabajando de forma remota, así que se avisa antes.
    if [[ "$accion" == "stop" && "$servicio" == "sshd" ]]; then
        echo -e "\n${RED}ATENCIÓN: si estás conectado por SSH, detener este servicio${NC}"
        echo -e "${RED}te va a dejar afuera del servidor.${NC}"
    fi

    read -p "$(echo -e "${YELLOW}¿Confirmás la acción '$accion' sobre '$servicio'? (s/n):${NC} ")" confirmacion
    if [[ "$confirmacion" != "s" && "$confirmacion" != "S" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    if ! systemctl "$accion" "$servicio"; then
        echo -e "${RED}El comando falló. Mirá el detalle con la opción 6.${NC}"
        log_accion "ERROR_${accion^^}: $servicio"
        return 1
    fi

    log_accion "${accion^^}: $servicio"

    # systemctl devuelve el control antes de que el servicio termine de
    # levantar, por eso se espera un instante antes de confirmar el estado.
    sleep 1

    local estado_final
    estado_final=$(systemctl is-active "$servicio" 2>/dev/null || true)

    if [[ "$accion" == "stop" ]]; then
        echo -e "\n${GREEN}Servicio '$servicio' detenido (estado: ${estado_final:-inactive}).${NC}"
    elif [[ "$estado_final" == "active" ]]; then
        echo -e "\n${GREEN}Servicio '$servicio' corriendo correctamente.${NC}"
    else
        echo -e "\n${RED}'$servicio' no quedó activo (estado: ${estado_final:-desconocido}).${NC}"
        echo -e "${YELLOW}   Revisá los registros con la opción 6 del menú.${NC}"
    fi
}

# Opción 5 · Decide si el servicio arranca solo cuando se enciende el servidor.
configurar_arranque() {
    imprimir_titulo "Arranque automático"

    elegir_servicio || return 1
    local servicio="$SERVICIO_ELEGIDO"

    local estado_actual
    estado_actual=$(systemctl is-enabled "$servicio" 2>/dev/null || echo "desconocido")
    echo -e "\n${CYAN}Arranque automático actual:${NC} $estado_actual"

    echo ""
    echo "  1) Habilitar (que arranque solo al encender el servidor)"
    echo "  2) Deshabilitar (que haya que iniciarlo a mano)"
    echo ""
    read -p "$(echo -e "${CYAN}Opción [1-2]:${NC} ")" opcion

    local accion
    case "$opcion" in
        1) accion="enable" ;;
        2) accion="disable" ;;
        *)
            echo -e "${RED}Opción inválida.${NC}"
            return 1
            ;;
    esac

    if [[ "$accion" == "disable" && "$servicio" == "sshd" ]]; then
        echo -e "\n${RED}ATENCIÓN: sin SSH al arranque, tras un reinicio no vas a poder${NC}"
        echo -e "${RED}entrar de forma remota al servidor.${NC}"
        read -p "$(echo -e "${YELLOW}¿Seguro? (escribí CONFIRMAR):${NC} ")" seguro
        if [[ "$seguro" != "CONFIRMAR" ]]; then
            echo -e "${YELLOW}Operación cancelada.${NC}"
            return 0
        fi
    fi

    if ! systemctl "$accion" "$servicio"; then
        echo -e "${RED}No se pudo cambiar el arranque automático.${NC}"
        return 1
    fi

    log_accion "${accion^^}_ARRANQUE: $servicio"
    echo -e "\n${GREEN}Listo: '$servicio' ahora está $(systemctl is-enabled "$servicio" 2>/dev/null).${NC}"
}

# Opción 6 · Muestra los últimos registros del servicio.
ver_registros() {
    imprimir_titulo "Registros de un servicio"

    elegir_servicio || return 1
    local servicio="$SERVICIO_ELEGIDO"

    read -p "$(echo -e "${CYAN}¿Cuántas líneas querés ver? [50]:${NC} ")" lineas
    lineas="${lineas:-50}"

    if [[ ! "$lineas" =~ ^[0-9]+$ ]]; then
        echo -e "${RED}La cantidad de líneas tiene que ser un número.${NC}"
        return 1
    fi

    echo ""
    # journalctl lee el registro centralizado de systemd:
    #   -u  filtra por unidad (el servicio)
    #   -n  cantidad de líneas
    #   --no-pager  imprime directo, sin abrir el paginador
    if ! journalctl -u "$servicio" -n "$lineas" --no-pager 2>/dev/null; then
        echo -e "${RED}No se pudieron leer los registros de '$servicio'.${NC}"
        return 1
    fi

    log_accion "VER_REGISTROS: $servicio ($lineas lineas)"
}

# ──────────────────────────────────────────────────────────────
# MENÚ PRINCIPAL
# ──────────────────────────────────────────────────────────────

mostrar_menu() {
    while true; do
        clear

        echo -e "${BLUE}"
        echo "  ╔══════════════════════════════════════════╗"
        echo "  ║   TORNALYX – Gestión de servicios        ║"
        echo "  ╚══════════════════════════════════════════╝"
        echo -e "${NC}"
        echo -e "  ${CYAN}1.${NC} Ver el estado de todos los servicios"
        echo -e "  ${CYAN}2.${NC} Iniciar un servicio"
        echo -e "  ${CYAN}3.${NC} Detener un servicio"
        echo -e "  ${CYAN}4.${NC} Reiniciar un servicio"
        echo -e "  ${CYAN}5.${NC} Configurar el arranque automático"
        echo -e "  ${CYAN}6.${NC} Ver los registros de un servicio"
        echo -e "  ${RED}0.${NC} Salir"
        echo ""

        read -p "$(echo -e "${YELLOW}Seleccioná una opción [0-6]:${NC} ")" opcion

        case "$opcion" in
            1) ver_estado_general ;;
            2) operar_servicio "start" ;;
            3) operar_servicio "stop" ;;
            4) operar_servicio "restart" ;;
            5) configurar_arranque ;;
            6) ver_registros ;;
            0)
                echo -e "\n${GREEN}Saliendo de la gestión de servicios.${NC}\n"
                log_accion "FIN_SESION"
                exit 0
                ;;
            *)
                echo -e "${RED}Opción inválida: elegí un número entre 0 y 6.${NC}"
                ;;
        esac

        echo ""
        read -p "$(echo -e "${YELLOW}Presioná ENTER para continuar...${NC}")"
    done
}

verificar_root
verificar_systemd
mkdir -p "$(dirname "$LOG_FILE")"
log_accion "INICIO_SESION"
mostrar_menu
