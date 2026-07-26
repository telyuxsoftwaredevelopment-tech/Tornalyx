#!/bin/bash
# ============================================================
# TORNALYX – SGDM
# Script de configuración de IP estática (NetworkManager)
# Tarea 6 – Sistemas Operativos | Primera Entrega
#
# Un servidor necesita una dirección fija: si el router le fuera cambiando
# la IP por DHCP, la aplicación dejaría de responder en la dirección que
# tienen configurada los clientes y el registro DNS quedaría apuntando a
# ninguna parte.
#
# Menú:
#   1) Ver la configuración de red actual
#   2) Configurar una IP estática
#   3) Volver a DHCP
#   4) Restaurar el último respaldo
#
# Pensado para AlmaLinux 8, que administra la red con NetworkManager y la
# herramienta nmcli. En Debian y Ubuntu el equivalente sería netplan.
# Uso: sudo bash configurar_ip_estatica.sh
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

LOG_FILE="/var/log/tornalyx/configurar_red.log"

# NetworkManager guarda cada conexión en un archivo de este directorio.
DIR_CONEXIONES="/etc/sysconfig/network-scripts"

# Directorio donde se guardan las copias antes de cada cambio.
DIR_RESPALDOS="/var/backups/tornalyx/red"

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
        echo -e "${YELLOW}   Uso: sudo bash configurar_ip_estatica.sh${NC}"
        exit 1
    fi
}

verificar_nmcli() {
    if ! command -v nmcli &>/dev/null; then
        echo -e "${RED}Este script necesita NetworkManager, que no está instalado.${NC}"
        echo -e "${YELLOW}   Instalalo con: dnf install NetworkManager${NC}"
        exit 1
    fi

    # NetworkManager tiene que estar corriendo para que nmcli haga algo.
    if ! systemctl is-active --quiet NetworkManager; then
        echo -e "${RED}El servicio NetworkManager no está activo.${NC}"
        echo -e "${YELLOW}   Arrancalo con: systemctl start NetworkManager${NC}"
        exit 1
    fi
}

# Valida una dirección IPv4: cuatro números de 0 a 255 separados por puntos.
# $1 - Dirección a validar
validar_ipv4() {
    local ip="$1"

    # Primero comprueba la forma general: cuatro grupos de 1 a 3 dígitos.
    if [[ ! "$ip" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        return 1
    fi

    # La expresión regular no alcanza para descartar valores como 999.
    # IFS=. parte la dirección y read la guarda en cuatro variables.
    local o1 o2 o3 o4
    IFS=. read -r o1 o2 o3 o4 <<< "$ip"

    local octeto
    for octeto in "$o1" "$o2" "$o3" "$o4"; do
        if [[ "$octeto" -gt 255 ]]; then
            return 1
        fi
    done

    return 0
}

# Valida el prefijo de red en notación CIDR (el número después de la barra).
# $1 - Prefijo, por ejemplo 24
validar_prefijo() {
    local prefijo="$1"

    if [[ ! "$prefijo" =~ ^[0-9]{1,2}$ ]]; then
        return 1
    fi

    # /0 tomaría toda internet y /32 dejaría una red de una sola dirección:
    # ninguno de los dos sirve para la placa de un servidor.
    if [[ "$prefijo" -lt 1 || "$prefijo" -gt 32 ]]; then
        return 1
    fi

    return 0
}

# Devuelve en CONEXION_ELEGIDA el nombre de la conexión que seleccione el
# operador, y en DISPOSITIVO_ELEGIDO la placa a la que corresponde.
elegir_conexion() {
    CONEXION_ELEGIDA=""
    DISPOSITIVO_ELEGIDO=""

    # -t saca la salida separada por ":" (fácil de recorrer) y -f elige las
    # columnas. Se filtran las conexiones de tipo ethernet y wifi: las
    # interfaces virtuales de Docker o del loopback no se tocan.
    local lineas
    mapfile -t lineas < <(nmcli -t -f NAME,TYPE,DEVICE connection show \
                          | grep -E ':(802-3-ethernet|802-11-wireless|ethernet|wifi):')

    if [[ ${#lineas[@]} -eq 0 ]]; then
        echo -e "${RED}No encontré conexiones de red administradas por NetworkManager.${NC}"
        return 1
    fi

    echo -e "${CYAN}Conexiones disponibles:${NC}"

    local i
    for i in "${!lineas[@]}"; do
        local nombre dispositivo
        nombre=$(echo "${lineas[$i]}" | cut -d: -f1)
        dispositivo=$(echo "${lineas[$i]}" | cut -d: -f3)

        # La dirección actual ayuda a reconocer cuál es la conexión en uso.
        local ip_actual
        ip_actual=$(nmcli -g IP4.ADDRESS connection show "$nombre" 2>/dev/null | head -1)

        printf "  %d) %-22s placa: %-10s %s\n" \
               "$((i + 1))" "$nombre" "${dispositivo:-sin placa}" "${ip_actual:-sin dirección}"
    done
    echo ""

    read -p "$(echo -e "${CYAN}Número de conexión:${NC} ")" numero

    if [[ ! "$numero" =~ ^[0-9]+$ ]] || [[ "$numero" -lt 1 || "$numero" -gt ${#lineas[@]} ]]; then
        echo -e "${RED}Selección inválida.${NC}"
        return 1
    fi

    CONEXION_ELEGIDA=$(echo "${lineas[$((numero - 1))]}" | cut -d: -f1)
    DISPOSITIVO_ELEGIDO=$(echo "${lineas[$((numero - 1))]}" | cut -d: -f3)
    return 0
}

# Guarda una copia de los archivos de conexión antes de modificarlos.
respaldar_configuracion() {
    mkdir -p "$DIR_RESPALDOS"

    local marca
    marca=$(date '+%Y%m%d-%H%M%S')

    local respaldo="${DIR_RESPALDOS}/network-scripts-${marca}.tar.gz"

    if [[ ! -d "$DIR_CONEXIONES" ]]; then
        echo -e "${YELLOW}No existe $DIR_CONEXIONES; no hay nada que respaldar.${NC}"
        return 1
    fi

    # Se guarda el directorio completo porque una conexión puede depender de
    # archivos vecinos (rutas estáticas, reglas por interfaz).
    if tar -czf "$respaldo" -C /etc/sysconfig network-scripts 2>/dev/null; then
        echo -e "${GREEN}Respaldo guardado en:${NC} $respaldo"
        log_accion "RESPALDO: $respaldo"
        return 0
    fi

    echo -e "${YELLOW}No se pudo crear el respaldo.${NC}"
    return 1
}

# ──────────────────────────────────────────────────────────────
# OPCIONES DEL MENÚ
# ──────────────────────────────────────────────────────────────

# Opción 1 · Muestra cómo está configurada la red en este momento.
ver_configuracion() {
    imprimir_titulo "Configuración de red actual"

    echo -e "${CYAN}Estado de los dispositivos:${NC}"
    nmcli device status

    echo -e "\n${CYAN}Direcciones por placa:${NC}"
    # -4 limita la salida a IPv4 y -br la muestra en formato breve.
    ip -4 -br addr show

    echo -e "\n${CYAN}Puerta de enlace (gateway):${NC}"
    # La ruta "default" es por donde sale el tráfico hacia afuera de la red.
    ip route show default || echo "  Sin puerta de enlace configurada."

    echo -e "\n${CYAN}Servidores DNS:${NC}"
    grep -i '^nameserver' /etc/resolv.conf 2>/dev/null || echo "  No pude leerlos."

    echo -e "\n${CYAN}Detalle de las conexiones:${NC}"
    nmcli -f NAME,TYPE,DEVICE,STATE connection show

    log_accion "VER_CONFIGURACION"
}

# Opción 2 · Pide los datos y deja la conexión con dirección fija.
configurar_estatica() {
    imprimir_titulo "Configurar IP estática"

    elegir_conexion || return 1
    local conexion="$CONEXION_ELEGIDA"

    echo ""
    read -p "$(echo -e "${CYAN}Dirección IP (ej. 192.168.1.50):${NC} ")" ip
    if ! validar_ipv4 "$ip"; then
        echo -e "${RED}Dirección IP inválida: '$ip'${NC}"
        return 1
    fi

    read -p "$(echo -e "${CYAN}Prefijo de red [24]:${NC} ")" prefijo
    prefijo="${prefijo:-24}"
    if ! validar_prefijo "$prefijo"; then
        echo -e "${RED}Prefijo inválido: '$prefijo' (tiene que estar entre 1 y 32).${NC}"
        return 1
    fi

    read -p "$(echo -e "${CYAN}Puerta de enlace (ej. 192.168.1.1):${NC} ")" gateway
    if ! validar_ipv4 "$gateway"; then
        echo -e "${RED}Puerta de enlace inválida: '$gateway'${NC}"
        return 1
    fi

    read -p "$(echo -e "${CYAN}DNS primario [8.8.8.8]:${NC} ")" dns1
    dns1="${dns1:-8.8.8.8}"
    if ! validar_ipv4 "$dns1"; then
        echo -e "${RED}DNS inválido: '$dns1'${NC}"
        return 1
    fi

    read -p "$(echo -e "${CYAN}DNS secundario [1.1.1.1]:${NC} ")" dns2
    dns2="${dns2:-1.1.1.1}"
    if ! validar_ipv4 "$dns2"; then
        echo -e "${RED}DNS inválido: '$dns2'${NC}"
        return 1
    fi

    echo -e "\n${YELLOW}Resumen de lo que se va a aplicar:${NC}"
    echo "  Conexión:         $conexion"
    echo "  Placa:            ${DISPOSITIVO_ELEGIDO:-sin asignar}"
    echo "  Dirección:        ${ip}/${prefijo}"
    echo "  Puerta de enlace: $gateway"
    echo "  DNS:              $dns1, $dns2"
    echo ""
    echo -e "${RED}Si estás conectado por SSH y la dirección nueva es distinta${NC}"
    echo -e "${RED}de la actual, vas a perder la sesión al aplicar el cambio.${NC}"
    echo ""

    read -p "$(echo -e "${YELLOW}¿Confirmás? (escribí CONFIRMAR):${NC} ")" confirmacion
    if [[ "$confirmacion" != "CONFIRMAR" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    respaldar_configuracion

    # nmcli modifica la conexión guardada. El método "manual" desactiva DHCP.
    # ipv4.addresses acepta la dirección en notación CIDR y ipv4.dns una
    # lista separada por espacios.
    echo -e "\n${CYAN}Aplicando la configuración...${NC}"

    if ! nmcli connection modify "$conexion" \
            ipv4.method manual \
            ipv4.addresses "${ip}/${prefijo}" \
            ipv4.gateway "$gateway" \
            ipv4.dns "${dns1} ${dns2}"; then
        echo -e "${RED}No se pudo modificar la conexión.${NC}"
        log_accion "ERROR_IP_ESTATICA: $conexion"
        return 1
    fi

    # Los cambios recién toman efecto cuando la conexión se reactiva.
    echo -e "${CYAN}Reactivando la conexión...${NC}"
    if ! nmcli connection up "$conexion"; then
        echo -e "${RED}La conexión no levantó con la configuración nueva.${NC}"
        echo -e "${YELLOW}   Usá la opción 4 para restaurar el respaldo.${NC}"
        log_accion "ERROR_ACTIVAR: $conexion"
        return 1
    fi

    log_accion "IP_ESTATICA: $conexion -> ${ip}/${prefijo} gw=$gateway dns=$dns1,$dns2"
    echo -e "\n${GREEN}Configuración aplicada.${NC}"
    echo -e "${CYAN}Dirección actual:${NC}"
    ip -4 -br addr show "${DISPOSITIVO_ELEGIDO:-lo}" 2>/dev/null || ip -4 -br addr show
}

# Opción 3 · Devuelve la conexión a DHCP.
volver_a_dhcp() {
    imprimir_titulo "Volver a DHCP"

    elegir_conexion || return 1
    local conexion="$CONEXION_ELEGIDA"

    echo -e "\n${YELLOW}La conexión '$conexion' va a pedirle la dirección al router.${NC}"
    echo -e "${RED}La dirección puede cambiar, así que no es lo apropiado para${NC}"
    echo -e "${RED}un servidor en producción.${NC}\n"

    read -p "$(echo -e "${YELLOW}¿Confirmás? (s/n):${NC} ")" confirmacion
    if [[ "$confirmacion" != "s" && "$confirmacion" != "S" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    respaldar_configuracion

    # Al volver a "auto" hay que limpiar los valores fijos: si quedaran
    # cargados, NetworkManager los sumaría a los que entregue el DHCP.
    if ! nmcli connection modify "$conexion" \
            ipv4.method auto \
            ipv4.addresses "" \
            ipv4.gateway "" \
            ipv4.dns ""; then
        echo -e "${RED}No se pudo modificar la conexión.${NC}"
        return 1
    fi

    if ! nmcli connection up "$conexion"; then
        echo -e "${RED}La conexión no levantó.${NC}"
        return 1
    fi

    log_accion "DHCP: $conexion"
    echo -e "\n${GREEN}La conexión '$conexion' quedó en DHCP.${NC}"
    ip -4 -br addr show "${DISPOSITIVO_ELEGIDO:-lo}" 2>/dev/null || ip -4 -br addr show
}

# Opción 4 · Restaura una copia guardada antes de un cambio.
restaurar_respaldo() {
    imprimir_titulo "Restaurar respaldo"

    if [[ ! -d "$DIR_RESPALDOS" ]]; then
        echo -e "${YELLOW}Todavía no hay respaldos guardados.${NC}"
        return 1
    fi

    # Los nombres llevan la fecha, así que el orden alfabético inverso deja
    # los más recientes arriba.
    local respaldos
    mapfile -t respaldos < <(ls -1 "$DIR_RESPALDOS"/network-scripts-*.tar.gz 2>/dev/null | sort -r)

    if [[ ${#respaldos[@]} -eq 0 ]]; then
        echo -e "${YELLOW}Todavía no hay respaldos guardados.${NC}"
        return 1
    fi

    echo -e "${CYAN}Respaldos disponibles (del más reciente al más viejo):${NC}"

    local i
    for i in "${!respaldos[@]}"; do
        printf "  %d) %s\n" "$((i + 1))" "$(basename "${respaldos[$i]}")"
    done
    echo ""

    read -p "$(echo -e "${CYAN}Número de respaldo:${NC} ")" numero

    if [[ ! "$numero" =~ ^[0-9]+$ ]] || [[ "$numero" -lt 1 || "$numero" -gt ${#respaldos[@]} ]]; then
        echo -e "${RED}Selección inválida.${NC}"
        return 1
    fi

    local elegido="${respaldos[$((numero - 1))]}"

    echo -e "\n${YELLOW}Se va a reemplazar la configuración actual por:${NC}"
    echo "  $(basename "$elegido")"
    echo ""

    read -p "$(echo -e "${YELLOW}¿Confirmás? (escribí CONFIRMAR):${NC} ")" confirmacion
    if [[ "$confirmacion" != "CONFIRMAR" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    # -C /etc/sysconfig extrae el contenido dentro de ese directorio,
    # reponiendo network-scripts tal como estaba cuando se hizo la copia.
    if ! tar -xzf "$elegido" -C /etc/sysconfig; then
        echo -e "${RED}No se pudo extraer el respaldo.${NC}"
        return 1
    fi

    # NetworkManager mantiene las conexiones en memoria: sin recargar,
    # seguiría trabajando con las de antes de restaurar.
    echo -e "${CYAN}Recargando las conexiones...${NC}"
    nmcli connection reload

    log_accion "RESTAURAR_RESPALDO: $elegido"
    echo -e "\n${GREEN}Configuración restaurada.${NC}"
    echo -e "${YELLOW}   Reactivá la conexión con la opción 2 o 3, o reiniciá el servidor.${NC}"
}

# ──────────────────────────────────────────────────────────────
# MENÚ PRINCIPAL
# ──────────────────────────────────────────────────────────────

mostrar_menu() {
    while true; do
        clear

        echo -e "${BLUE}"
        echo "  ╔══════════════════════════════════════════╗"
        echo "  ║   TORNALYX – Configuración de red        ║"
        echo "  ╚══════════════════════════════════════════╝"
        echo -e "${NC}"
        echo -e "  ${CYAN}1.${NC} Ver la configuración de red actual"
        echo -e "  ${CYAN}2.${NC} Configurar una IP estática"
        echo -e "  ${CYAN}3.${NC} Volver a DHCP"
        echo -e "  ${CYAN}4.${NC} Restaurar el último respaldo"
        echo -e "  ${RED}0.${NC} Salir"
        echo ""

        read -p "$(echo -e "${YELLOW}Seleccioná una opción [0-4]:${NC} ")" opcion

        case "$opcion" in
            1) ver_configuracion ;;
            2) configurar_estatica ;;
            3) volver_a_dhcp ;;
            4) restaurar_respaldo ;;
            0)
                echo -e "\n${GREEN}Saliendo de la configuración de red.${NC}\n"
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

verificar_root
verificar_nmcli
mkdir -p "$(dirname "$LOG_FILE")"
log_accion "INICIO_SESION"
mostrar_menu
