#!/usr/bin/env bash
#
# gestion_usuarios.sh — Script de gestión de usuarios del sistema operativo
# para el servidor de Tornalyx (SGDM).
#
# Implementa los 4 usuarios definidos en:
#   docs/primera-entrega/TAREA6-SISTEMAS-OPERATIVOS/analisis-usuarios-sistema.md
#
#   admin_tornalyx    (UID 1001) — administrador del sistema (sudo completo)
#   operador_web      (UID 1002) — operador de la aplicación web
#   dev_tornalyx      (UID 1003) — desarrollo (solo servidor de staging)
#   backup_tornalyx   (UID 1004) — cuenta de servicio para backups (nologin)
#
# Requisito: TAREA6 - Administración de Sistemas Operativos, primera entrega
# ("Script de gestión de usuarios del sistema implementado en el servidor").
#
# Uso:
#   sudo ./gestion_usuarios.sh crear              # crea/actualiza los 4 usuarios
#   sudo ./gestion_usuarios.sh crear admin_tornalyx   # crea/actualiza solo uno
#   sudo ./gestion_usuarios.sh listar             # tabla de estado de los 4 usuarios
#   sudo ./gestion_usuarios.sh estado <usuario>   # detalle de un usuario
#   sudo ./gestion_usuarios.sh eliminar <usuario> # borra usuario + home (con confirmación)
#   sudo ./gestion_usuarios.sh -h                 # ayuda
#
# El script es idempotente: correrlo varias veces no duplica usuarios ni
# permisos, solo los deja en el estado declarado abajo.

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
SUDOERS_SRC_DIR="$SCRIPT_DIR/sudoers.d"
CREDENTIALS_SRC_DIR="$SCRIPT_DIR/credentials.d"
LOG_FILE="/var/log/tornalyx/gestion_usuarios.log"

# ── Definición declarativa de usuarios ──────────────────────────────────
# Formato: nombre|uid|grupo_primario|grupos_secundarios|shell|home|con_sudoers|dias_expiracion_password
USUARIOS=(
  "admin_tornalyx|1001|admin_tornalyx|sudo,www-data|/bin/bash|/home/admin_tornalyx|no|90"
  "operador_web|1002|www-data|-|/bin/bash|/home/operador_web|si|180"
  "dev_tornalyx|1003|dev_tornalyx|www-data|/bin/bash|/home/dev_tornalyx|si|180"
  "backup_tornalyx|1004|backup_tornalyx|-|/usr/sbin/nologin|/var/backups/tornalyx|si|-"
)

# ── Utilidades ───────────────────────────────────────────────────────────
log() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
  echo "$msg"
  if [[ -d "$(dirname -- "$LOG_FILE")" ]] || mkdir -p "$(dirname -- "$LOG_FILE")" 2>/dev/null; then
    echo "$msg" >>"$LOG_FILE" 2>/dev/null || true
  fi
}

fallar() {
  echo "ERROR: $*" >&2
  exit 1
}

requiere_root() {
  [[ "$(id -u)" -eq 0 ]] || fallar "este script debe ejecutarse como root (sudo)."
}

buscar_definicion() {
  local nombre="$1"
  local def
  for def in "${USUARIOS[@]}"; do
    if [[ "${def%%|*}" == "$nombre" ]]; then
      echo "$def"
      return 0
    fi
  done
  return 1
}

nombres_usuarios() {
  local def
  for def in "${USUARIOS[@]}"; do
    echo "${def%%|*}"
  done
}

# ── Creación / actualización de un usuario ──────────────────────────────
crear_usuario() {
  local def="$1"
  IFS='|' read -r nombre uid grupo_primario grupos_sec shell home con_sudoers dias_exp <<<"$def"

  log "Procesando usuario '$nombre'..."

  # Grupo primario (mismo nombre que el usuario, salvo cuando es www-data,
  # que ya existe por defecto en Debian/Ubuntu con Apache instalado).
  if [[ "$grupo_primario" != "www-data" ]] && ! getent group "$grupo_primario" >/dev/null; then
    groupadd "$grupo_primario"
    log "  Grupo '$grupo_primario' creado."
  fi

  # Grupos secundarios declarados (deben existir previamente, p. ej. 'sudo').
  if [[ "$grupos_sec" != "-" ]]; then
    IFS=',' read -ra secundarios <<<"$grupos_sec"
    for g in "${secundarios[@]}"; do
      getent group "$g" >/dev/null || fallar "el grupo secundario '$g' no existe en el sistema."
    done
  fi

  if id "$nombre" &>/dev/null; then
    log "  El usuario '$nombre' ya existe: actualizando shell/grupos/home."
    usermod -s "$shell" -d "$home" -g "$grupo_primario" "$nombre"
    if [[ "$grupos_sec" != "-" ]]; then
      usermod -G "$grupos_sec" "$nombre"
    fi
  else
    local extra_args=(-m -u "$uid" -g "$grupo_primario" -s "$shell" -d "$home" -c "Tornalyx: $nombre")
    if [[ "$grupos_sec" != "-" ]]; then
      extra_args+=(-G "$grupos_sec")
    fi
    useradd "${extra_args[@]}" "$nombre"
    log "  Usuario '$nombre' creado (UID $uid)."
  fi

  configurar_home "$nombre" "$home"
  instalar_credenciales "$nombre" "$home"
  configurar_password "$nombre" "$shell" "$dias_exp"

  if [[ "$con_sudoers" == "si" ]]; then
    instalar_sudoers "$nombre"
  fi

  log "  '$nombre' listo."
}

# ── Permisos del directorio home ────────────────────────────────────────
configurar_home() {
  local nombre="$1" home="$2"
  mkdir -p "$home"
  chown "$nombre":"$(id -gn "$nombre")" "$home"
  if [[ "$nombre" == "backup_tornalyx" ]]; then
    chmod 700 "$home"
  else
    chmod 750 "$home"
    # Directorio .ssh listo para authorized_keys (login solo por clave pública).
    install -d -m 700 -o "$nombre" -g "$(id -gn "$nombre")" "$home/.ssh"
    [[ -f "$home/.ssh/authorized_keys" ]] || install -m 600 -o "$nombre" -g "$(id -gn "$nombre")" /dev/null "$home/.ssh/authorized_keys"
  fi
}

# ── Política de contraseñas y expiración ────────────────────────────────
# Cuenta de servicio (nologin, sin login interactivo): sin contraseña,
# cuenta bloqueada para autenticación por password.
configurar_password() {
  local nombre="$1" shell="$2" dias_exp="$3"

  if [[ "$shell" == "/usr/sbin/nologin" || "$shell" == "/bin/false" ]]; then
    passwd -l "$nombre" >/dev/null
    log "  Cuenta de servicio: password bloqueado (sin login interactivo)."
    return
  fi

  # Si el usuario no tiene contraseña asignada todavía, se deja bloqueado y
  # se le pide al administrador que la establezca a mano (nunca hardcodeada
  # en el script) y que el usuario la cambie en el primer ingreso.
  if passwd -S "$nombre" 2>/dev/null | awk '{print $2}' | grep -qE '^(NP|L)$'; then
    log "  '$nombre' sin contraseña asignada todavía."
    log "  -> Ejecutar manualmente: sudo passwd $nombre"
  fi

  chage -d 0 "$nombre"          # obliga a cambiar la contraseña en el próximo login
  [[ "$dias_exp" != "-" ]] && chage -M "$dias_exp" "$nombre"
  log "  Expiración de contraseña configurada (máx. $dias_exp días)."
}

# ── Instalación de plantillas de credenciales de MySQL ──────────────────
# Solo backup_tornalyx (cuenta de servicio, necesita el password guardado
# para que cron pueda correr respaldo.sh sin interacción) y dev_tornalyx
# (comodidad de ~/.my.cnf) tienen plantilla en credentials.d/. Nunca
# sobrescribe un archivo que ya exista, para no pisar un password real ya
# configurado por el admin.
instalar_credenciales() {
  local nombre="$1" home="$2"
  local src="$CREDENTIALS_SRC_DIR/$nombre"
  [[ -f "$src" ]] || return 0

  case "$nombre" in
    backup_tornalyx)
      local dest="/etc/tornalyx/backup.env"
      install -d -m 700 -o backup_tornalyx -g backup_tornalyx /etc/tornalyx
      if [[ -f "$dest" ]]; then
        log "  $dest ya existe, no se sobrescribe."
      else
        install -m 600 -o backup_tornalyx -g backup_tornalyx "$src" "$dest"
        log "  Plantilla de credenciales instalada en $dest (editar DB_BACKUP_PASS)."
      fi
      ;;
    dev_tornalyx)
      local dest="$home/.my.cnf"
      if [[ -f "$dest" ]]; then
        log "  $dest ya existe, no se sobrescribe."
      else
        install -m 600 -o "$nombre" -g "$(id -gn "$nombre")" "$src" "$dest"
        log "  Plantilla de credenciales instalada en $dest (editar password)."
      fi
      ;;
    *)
      log "  Sin manejo de credenciales para '$nombre', se omite."
      ;;
  esac
}

# ── Instalación de reglas sudoers específicas ───────────────────────────
instalar_sudoers() {
  local nombre="$1"
  local src="$SUDOERS_SRC_DIR/$nombre"
  local dest="/etc/sudoers.d/$nombre"

  [[ -f "$src" ]] || { log "  Sin plantilla sudoers para '$nombre', se omite."; return; }

  visudo -cf "$src" >/dev/null || fallar "la plantilla sudoers de '$nombre' tiene sintaxis inválida."

  install -m 440 -o root -g root "$src" "$dest"
  log "  Reglas sudoers instaladas en $dest."
}

# ── Comandos del script ──────────────────────────────────────────────────
cmd_crear() {
  requiere_root
  local objetivo="${1:-}"
  if [[ -n "$objetivo" ]]; then
    local def
    def="$(buscar_definicion "$objetivo")" || fallar "usuario desconocido: '$objetivo'. Usá 'listar' para ver los válidos."
    crear_usuario "$def"
  else
    local def
    for def in "${USUARIOS[@]}"; do
      crear_usuario "$def"
    done
  fi
}

cmd_listar() {
  printf '%-18s %-6s %-12s %-25s %-9s\n' "USUARIO" "UID" "SHELL" "GRUPOS" "EXISTE"
  local def nombre uid grupo_primario grupos_sec shell home con_sudoers dias_exp grupos existe
  for def in "${USUARIOS[@]}"; do
    IFS='|' read -r nombre uid grupo_primario grupos_sec shell home con_sudoers dias_exp <<<"$def"
    if id "$nombre" &>/dev/null; then
      existe="si"
      shell="$(getent passwd "$nombre" | cut -d: -f7)"
      grupos="$(id -Gn "$nombre" | tr ' ' ',')"
    else
      existe="NO"
      grupos="-"
    fi
    printf '%-18s %-6s %-12s %-25s %-9s\n' "$nombre" "$uid" "$shell" "$grupos" "$existe"
  done
}

cmd_estado() {
  local nombre="${1:-}"
  [[ -n "$nombre" ]] || fallar "uso: $0 estado <usuario>"
  buscar_definicion "$nombre" >/dev/null || fallar "usuario desconocido: '$nombre'."
  id "$nombre" &>/dev/null || fallar "'$nombre' no existe en el sistema todavía."

  echo "== $nombre =="
  id "$nombre"
  echo "Home:  $(getent passwd "$nombre" | cut -d: -f6)"
  echo "Shell: $(getent passwd "$nombre" | cut -d: -f7)"
  passwd -S "$nombre" 2>/dev/null || true
  chage -l "$nombre" 2>/dev/null || true
  [[ -f "/etc/sudoers.d/$nombre" ]] && echo "Sudoers: /etc/sudoers.d/$nombre" || echo "Sudoers: (ninguno)"
}

cmd_eliminar() {
  requiere_root
  local nombre="${1:-}"
  [[ -n "$nombre" ]] || fallar "uso: $0 eliminar <usuario>"
  buscar_definicion "$nombre" >/dev/null || fallar "usuario desconocido: '$nombre'."
  id "$nombre" &>/dev/null || { log "'$nombre' no existe, nada que hacer."; return; }

  read -r -p "¿Confirmás eliminar '$nombre' y su directorio home? [escribir 'si']: " confirmacion
  [[ "$confirmacion" == "si" ]] || { log "Cancelado."; return; }

  userdel -r "$nombre" 2>/dev/null || userdel "$nombre"
  rm -f "/etc/sudoers.d/$nombre"
  [[ "$nombre" == "backup_tornalyx" ]] && rm -f /etc/tornalyx/backup.env
  log "Usuario '$nombre' eliminado."
}

mostrar_ayuda() {
  cat <<EOF
Uso: $0 <comando> [usuario]

Comandos:
  crear [usuario]     Crea o actualiza uno de los usuarios definidos, o todos
                       si no se especifica ninguno. Instala sudoers.d/ y
                       credentials.d/ (plantillas) para el usuario que
                       corresponda; nunca pisa un password real existente.
  listar               Muestra el estado (UID, shell, grupos) de los 4 usuarios.
  estado <usuario>     Detalle completo de un usuario (grupos, expiración, sudoers).
  eliminar <usuario>   Borra el usuario y su home (pide confirmación).
  -h, --help           Muestra esta ayuda.

Usuarios gestionados: $(nombres_usuarios | paste -sd ' ' -)

Requiere ejecutarse con privilegios de root (sudo) para crear/eliminar.
EOF
}

main() {
  local comando="${1:-}"
  case "$comando" in
    crear) shift; cmd_crear "${1:-}" ;;
    listar) cmd_listar ;;
    estado) shift; cmd_estado "${1:-}" ;;
    eliminar) shift; cmd_eliminar "${1:-}" ;;
    -h|--help|help|"") mostrar_ayuda ;;
    *) fallar "comando desconocido: '$comando'. Usá '$0 --help'." ;;
  esac
}

main "$@"
