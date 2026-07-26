#!/bin/bash
# ============================================================
# TORNALYX – SGDM
# Script de configuración de IP estática (netplan)
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
# Probado sobre Ubuntu Server 22.04 / 24.04 (netplan).
# Uso: sudo bash configurar_ip_estatica.sh
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

LOG_FILE="/var/log/tornalyx/configurar_red.log"

# Archivo de netplan que administra este script. Se usa un nombre propio
# para no pisar el que trae el sistema; el número alto del prefijo hace que
# se aplique después de los demás.
ARCHIVO_NETPLAN="/etc/netplan/99-tornalyx.yaml"

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

verificar_netplan() {
    if ! command -v netplan &>/dev/null; then
        echo -e "${RED}Este script necesita netplan, que no está instalado.${NC}"
        echo -e "${YELLOW}   En sistemas con ifupdown la configuración va en${NC}"
        echo -e "${YELLOW}   /etc/network/interfaces en lugar de /etc/netplan.${NC}"
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

# Devuelve en INTERFAZ_ELEGIDA la placa de red que seleccione el operador.
elegir_interfaz() {
    INTERFAZ_ELEGIDA=""

    # ip -o link muestra una placa por línea. Se descarta "lo", que es la
    # interfaz de loopback interna y no se configura.
    local interfaces
    mapfile -t interfaces < <(ip -o link show | awk -F': ' '{print $2}' | grep -v '^lo$')

    if [[ ${#interfaces[@]} -eq 0 ]]; then
        echo -e "${RED}No encontré ninguna placa de red.${NC}"
        return 1
    fi

    echo -e "${CYAN}Placas de red disponibles:${NC}"

    local i
    for i in "${!interfaces[@]}"; do
        local nombre="${interfaces[$i]}"
        # La IP actual sirve para reconocer cuál es la placa en uso.
        local ip_actual
        ip_actual=$(ip -4 -o addr show "$nombre" 2>/dev/null | awk '{print $4}' | head -1)
        printf "  %d) %-12s %s\n" "$((i + 1))" "$nombre" "${ip_actual:-sin dirección}"
    done
    echo ""

    read -p "$(echo -e "${CYAN}Número de placa:${NC} ")" numero

    if [[ ! "$numero" =~ ^[0-9]+$ ]] || [[ "$numero" -lt 1 || "$numero" -gt ${#interfaces[@]} ]]; then
        echo -e "${RED}Selección inválida.${NC}"
        return 1
    fi

    INTERFAZ_ELEGIDA="${interfaces[$((numero - 1))]}"
    return 0
}

# Guarda una copia de la configuración de red antes de modificarla.
respaldar_configuracion() {
    mkdir -p "$DIR_RESPALDOS"

    local marca
    marca=$(date '+%Y%m%d-%H%M%S')

    local respaldo="${DIR_RESPALDOS}/netplan-${marca}.tar.gz"

    # Se guarda todo /etc/netplan porque puede haber más de un archivo y el
    # orden entre ellos afecta el resultado final.
    if tar -czf "$respaldo" -C /etc netplan 2>/dev/null; then
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

    echo -e "${CYAN}Direcciones por placa:${NC}"
    # -4 limita la salida a IPv4 y -br la muestra en formato breve.
    ip -4 -br addr show

    echo -e "\n${CYAN}Puerta de enlace (gateway):${NC}"
    # La ruta "default" es por donde sale el tráfico hacia afuera de la red.
    ip route show default || echo "  Sin puerta de enlace configurada."

    echo -e "\n${CYAN}Servidores DNS:${NC}"
    if command -v resolvectl &>/dev/null; then
        resolvectl status 2>/dev/null | grep -i 'DNS Servers' | head -5
    else
        grep -i '^nameserver' /etc/resolv.conf 2>/dev/null || echo "  No pude leerlos."
    fi

    echo -e "\n${CYAN}Archivos de netplan:${NC}"
    ls -1 /etc/netplan/*.yaml 2>/dev/null || echo "  Ninguno."

    if [[ -f "$ARCHIVO_NETPLAN" ]]; then
        echo -e "\n${CYAN}Configuración escrita por este script:${NC}"
        cat "$ARCHIVO_NETPLAN"
    fi

    log_accion "VER_CONFIGURACION"
}

# Opción 2 · Pide los datos y deja la placa con dirección fija.
configurar_estatica() {
    imprimir_titulo "Configurar IP estática"

    elegir_interfaz || return 1
    local interfaz="$INTERFAZ_ELEGIDA"

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
    echo "  Placa:            $interfaz"
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

    # cat con "<< EOF" escribe todo el bloque hasta la marca EOF.
    # El formato es YAML: la indentación con espacios es obligatoria y no
    # se pueden usar tabulaciones.
    cat > "$ARCHIVO_NETPLAN" << EOF
# Configuración de red generada por configurar_ip_estatica.sh
# Tornalyx (SGDM) · $(date '+%Y-%m-%d %H:%M:%S')
network:
  version: 2
  renderer: networkd
  ethernets:
    ${interfaz}:
      dhcp4: false
      addresses:
        - ${ip}/${prefijo}
      routes:
        - to: default
          via: ${gateway}
      nameservers:
        addresses: [${dns1}, ${dns2}]
EOF

    # netplan exige que solo root pueda leer el archivo; si queda con
    # permisos más abiertos, avisa en cada ejecución.
    chmod 600 "$ARCHIVO_NETPLAN"

    echo -e "\n${CYAN}Archivo escrito:${NC} $ARCHIVO_NETPLAN"
    echo -e "${CYAN}Verificando la sintaxis...${NC}"

    # generate traduce el YAML a la configuración interna sin activarla:
    # sirve para detectar errores de escritura antes de aplicar el cambio.
    if ! netplan generate 2>&1; then
        echo -e "${RED}El archivo tiene errores. No se aplicó nada.${NC}"
        echo -e "${YELLOW}   Usá la opción 4 para restaurar el respaldo.${NC}"
        log_accion "ERROR_SINTAXIS: $ARCHIVO_NETPLAN"
        return 1
    fi

    echo -e "${GREEN}Sintaxis correcta.${NC}\n"
    echo -e "${CYAN}Aplicando con 'netplan try'...${NC}"
    echo -e "${YELLOW}Si la conexión se corta, el sistema vuelve solo a la${NC}"
    echo -e "${YELLOW}configuración anterior a los 120 segundos.${NC}\n"

    # try aplica la configuración y espera confirmación por teclado. Si el
    # operador pierde el acceso y no confirma, revierte solo: es la forma
    # segura de cambiar la red de un servidor remoto.
    if netplan try --timeout 120; then
        log_accion "IP_ESTATICA: $interfaz -> ${ip}/${prefijo} gw=$gateway dns=$dns1,$dns2"
        echo -e "\n${GREEN}Configuración aplicada.${NC}"
        echo -e "${CYAN}Dirección actual:${NC} $(ip -4 -br addr show "$interfaz")"
    else
        echo -e "\n${YELLOW}La configuración no se confirmó y el sistema volvió atrás.${NC}"
        log_accion "IP_ESTATICA_REVERTIDA: $interfaz"
    fi
}

# Opción 3 · Devuelve la placa a DHCP.
volver_a_dhcp() {
    imprimir_titulo "Volver a DHCP"

    elegir_interfaz || return 1
    local interfaz="$INTERFAZ_ELEGIDA"

    echo -e "\n${YELLOW}La placa '$interfaz' va a pedirle la dirección al router.${NC}"
    echo -e "${RED}La dirección puede cambiar, así que no es lo apropiado para${NC}"
    echo -e "${RED}un servidor en producción.${NC}\n"

    read -p "$(echo -e "${YELLOW}¿Confirmás? (s/n):${NC} ")" confirmacion
    if [[ "$confirmacion" != "s" && "$confirmacion" != "S" ]]; then
        echo -e "${YELLOW}Operación cancelada.${NC}"
        return 0
    fi

    respaldar_configuracion

    cat > "$ARCHIVO_NETPLAN" << EOF
# Configuración de red generada por configurar_ip_estatica.sh
# Tornalyx (SGDM) · $(date '+%Y-%m-%d %H:%M:%S')
network:
  version: 2
  renderer: networkd
  ethernets:
    ${interfaz}:
      dhcp4: true
EOF

    chmod 600 "$ARCHIVO_NETPLAN"

    if ! netplan generate 2>&1; then
        echo -e "${RED}El archivo tiene errores. No se aplicó nada.${NC}"
        return 1
    fi

    if netplan try --timeout 120; then
        log_accion "DHCP: $interfaz"
        echo -e "\n${GREEN}La placa '$interfaz' quedó en DHCP.${NC}"
        echo -e "${CYAN}Dirección actual:${NC} $(ip -4 -br addr show "$interfaz")"
    else
        echo -e "\n${YELLOW}La configuración no se confirmó y el sistema volvió atrás.${NC}"
    fi
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
    mapfile -t respaldos < <(ls -1 "$DIR_RESPALDOS"/netplan-*.tar.gz 2>/dev/null | sort -r)

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

    # -C /etc extrae el contenido dentro de /etc, reponiendo /etc/netplan
    # tal como estaba cuando se hizo la copia.
    if ! tar -xzf "$elegido" -C /etc; then
        echo -e "${RED}No se pudo extraer el respaldo.${NC}"
        return 1
    fi

    if ! netplan generate 2>&1; then
        echo -e "${RED}El respaldo restaurado tiene errores de sintaxis.${NC}"
        return 1
    fi

    if netplan try --timeout 120; then
        log_accion "RESTAURAR_RESPALDO: $elegido"
        echo -e "\n${GREEN}Configuración restaurada.${NC}"
    else
        echo -e "\n${YELLOW}La configuración no se confirmó y el sistema volvió atrás.${NC}"
    fi
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
verificar_netplan
mkdir -p "$(dirname "$LOG_FILE")"
log_accion "INICIO_SESION"
mostrar_menu
