#!/bin/bash

# Crea los usuarios y grupos del sistema definidos en el estudio de roles.
# A diferencia de usuarios.sh, que da de alta una cuenta cualquiera que se
# le indique, este script deja el servidor con exactamente las cuentas que
# el proyecto necesita, con sus UID, grupos y shell.
#
# Se puede volver a ejecutar sin problema: lo que ya existe se informa y
# se deja como está.

# Definición de las cuentas según el análisis de usuarios del sistema.
# Formato: usuario|UID|grupo principal|grupos secundarios|shell|home|descripción
USUARIOS=(
    "admin_tornalyx|1001|admin_tornalyx|wheel,apache|/bin/bash|/home/admin_tornalyx|Administrador del servidor"
    "operador_web|1002|apache||/bin/bash|/home/operador_web|Operador de la aplicacion web"
    "dev_tornalyx|1003|dev_tornalyx|apache|/bin/bash|/home/dev_tornalyx|Desarrollo y pruebas"
    "backup_tornalyx|1004|backup_tornalyx||/sbin/nologin|/var/backups/tornalyx|Cuenta de servicio para respaldos"
)

# Grupos del sistema que las cuentas necesitan y que no crea este script:
# wheel viene con AlmaLinux y apache lo crea el paquete httpd.
GRUPOS_REQUERIDOS=(wheel apache)

verificar_root() {
    if [ "$EUID" -ne 0 ]; then
        echo "Este script debe ejecutarse con sudo o como root."
        exit 1
    fi
}

# Comprueba que existan los grupos que no creamos nosotros. Si falta apache
# es porque todavía no se instaló httpd, y conviene avisarlo antes de
# empezar en lugar de que useradd falle a mitad de camino.
verificar_grupos_requeridos() {
    faltan=""
    for grupo in "${GRUPOS_REQUERIDOS[@]}"; do
        if ! getent group "$grupo" &>/dev/null; then
            faltan="$faltan $grupo"
        fi
    done

    if [ -n "$faltan" ]; then
        echo "Faltan grupos del sistema:$faltan"
        echo "El grupo apache lo crea el paquete httpd: dnf install httpd"
        echo ""
        read -p "¿Continuar de todos modos? (s/n): " respuesta
        if [ "$respuesta" != "s" ]; then
            echo "Operación cancelada."
            exit 1
        fi
    fi
}

# Crea el grupo principal propio de una cuenta, usando el mismo número que
# el UID para que el usuario y su grupo queden emparejados.
crear_grupo_principal() {
    grupo=$1
    gid=$2

    if getent group "$grupo" &>/dev/null; then
        echo "    El grupo $grupo ya existe."
        return 0
    fi

    if groupadd -g "$gid" "$grupo"; then
        echo "    Grupo $grupo creado (GID $gid)."
    else
        echo "    Error al crear el grupo $grupo."
        return 1
    fi
}

crear_usuarios() {
    echo ""
    echo "===== CREACIÓN DE ROLES Y USUARIOS ====="

    for definicion in "${USUARIOS[@]}"; do
        # IFS='|' hace que read parta la línea por las barras verticales.
        IFS='|' read -r usuario uid grupo_pri grupos_sec shell home descripcion <<< "$definicion"

        echo ""
        echo " $usuario ($descripcion)"

        if id "$usuario" &>/dev/null; then
            echo "    El usuario ya existe. Grupos actuales: $(id -nG "$usuario")"
            continue
        fi

        # Si el grupo principal lleva el nombre de la cuenta, lo creamos;
        # si es un grupo del sistema como apache, ya tiene que existir.
        if [ "$grupo_pri" = "$usuario" ]; then
            crear_grupo_principal "$grupo_pri" "$uid" || continue
        elif ! getent group "$grupo_pri" &>/dev/null; then
            echo "    No existe el grupo principal $grupo_pri. Se omite esta cuenta."
            continue
        fi

        # -u fija el UID, -g el grupo principal, -s la shell, -d el home y
        # -m lo crea. -c guarda la descripción en el campo de comentario.
        if [ -n "$grupos_sec" ]; then
            useradd -u "$uid" -g "$grupo_pri" -G "$grupos_sec" \
                -s "$shell" -d "$home" -m -c "$descripcion" "$usuario"
        else
            useradd -u "$uid" -g "$grupo_pri" \
                -s "$shell" -d "$home" -m -c "$descripcion" "$usuario"
        fi

        if [ $? -ne 0 ]; then
            echo "    Error al crear el usuario."
            continue
        fi

        echo "    Usuario creado (UID $uid, shell $shell)."

        # Las cuentas de servicio no llevan contraseña: quedan bloqueadas
        # para que nadie pueda iniciar sesión con ellas.
        if [ "$shell" = "/sbin/nologin" ]; then
            passwd -l "$usuario" > /dev/null
            echo "    Cuenta de servicio: contraseña bloqueada."
        else
            # Se bloquea hasta que el administrador le asigne una desde el
            # menú, así no queda ninguna cuenta con acceso sin clave.
            passwd -l "$usuario" > /dev/null
            echo "    Pendiente: asignar contraseña con la opción 2 del menú."
        fi
    done
}

asignar_contrasenas() {
    echo ""
    echo "===== ASIGNACIÓN DE CONTRASEÑAS ====="

    for definicion in "${USUARIOS[@]}"; do
        IFS='|' read -r usuario uid grupo_pri grupos_sec shell home descripcion <<< "$definicion"

        # Las cuentas sin shell interactiva no necesitan contraseña.
        if [ "$shell" = "/sbin/nologin" ]; then
            continue
        fi

        if ! id "$usuario" &>/dev/null; then
            echo " $usuario todavía no existe; creá los usuarios primero."
            continue
        fi

        echo ""
        read -p " ¿Asignar contraseña a $usuario? (s/n): " respuesta
        if [ "$respuesta" = "s" ]; then
            passwd "$usuario"
            # chage -d 0 vence la contraseña de inmediato, así el usuario
            # tiene que elegir la suya en el primer inicio de sesión.
            chage -d 0 "$usuario"
            echo "    Deberá cambiarla al iniciar sesión."
        fi
    done
}

verificar_creacion() {
    echo ""
    echo "===== VERIFICACIÓN ====="
    printf " %-18s %-7s %-28s %s\n" "USUARIO" "UID" "GRUPOS" "SHELL"
    printf " %-18s %-7s %-28s %s\n" "-------" "---" "------" "-----"

    for definicion in "${USUARIOS[@]}"; do
        IFS='|' read -r usuario uid grupo_pri grupos_sec shell home descripcion <<< "$definicion"

        if id "$usuario" &>/dev/null; then
            # El sexto campo de /etc/passwd no lo usamos acá: preferimos
            # leer los datos reales del sistema y no los de la definición.
            uid_real=$(id -u "$usuario")
            shell_real=$(getent passwd "$usuario" | cut -d: -f7)
            printf " %-18s %-7s %-28s %s\n" \
                "$usuario" "$uid_real" "$(id -nG "$usuario" | tr ' ' ',')" "$shell_real"
        else
            printf " %-18s %-7s %-28s %s\n" "$usuario" "-" "NO EXISTE" "-"
        fi
    done
}

eliminar_usuarios() {
    echo ""
    echo "===== ELIMINAR LOS USUARIOS DEL PROYECTO ====="
    echo "Se van a eliminar las cuentas creadas por este script."
    echo "ADVERTENCIA: esta acción es irreversible."
    echo ""
    read -p "Escribí CONFIRMAR para continuar: " confirmacion

    if [ "$confirmacion" != "CONFIRMAR" ]; then
        echo "Operación cancelada."
        return
    fi

    read -p "¿Eliminar también los directorios home? (s/n): " borrar_home

    for definicion in "${USUARIOS[@]}"; do
        IFS='|' read -r usuario uid grupo_pri grupos_sec shell home descripcion <<< "$definicion"

        if ! id "$usuario" &>/dev/null; then
            echo " $usuario no existe."
            continue
        fi

        if [ "$borrar_home" = "s" ]; then
            userdel -r "$usuario" 2>/dev/null
        else
            userdel "$usuario" 2>/dev/null
        fi
        echo " $usuario eliminado."

        # El grupo propio queda huérfano al borrar la cuenta, así que se
        # limpia también. Los grupos del sistema (wheel, apache) no se tocan.
        if [ "$grupo_pri" = "$usuario" ] && getent group "$grupo_pri" &>/dev/null; then
            groupdel "$grupo_pri" 2>/dev/null && echo " Grupo $grupo_pri eliminado."
        fi
    done
}

menu_roles() {
    while true; do
        echo ""
        echo "===== ROLES Y USUARIOS DEL PROYECTO ====="
        echo "1. Crear todos los usuarios y grupos"
        echo "2. Asignar contraseñas"
        echo "3. Verificar las cuentas creadas"
        echo "4. Eliminar los usuarios del proyecto"
        echo "5. Volver"
        read -p "Seleccione una opción: " opcion
        case $opcion in
            1)
                crear_usuarios
                verificar_creacion
                ;;
            2)
                asignar_contrasenas
                ;;
            3)
                verificar_creacion
                ;;
            4)
                eliminar_usuarios
                ;;
            5)
                break
                ;;
            *)
                echo "Opción inválida."
                ;;
        esac
    done
}

verificar_root
verificar_grupos_requeridos
menu_roles
