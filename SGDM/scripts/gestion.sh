#!/bin/bash

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

while true; do
    echo ""
    echo "===================================="
    echo "   SISTEMA DE GESTIÓN DEL SERVIDOR"
    echo "              TORNALYX"
    echo "===================================="
    echo "1. Gestión de usuarios"
    echo "2. Gestión de grupos"
    echo "3. Asignación de grupos"
    echo "4. Consultar usuarios y grupos"
    echo "5. Crear los roles y usuarios del proyecto"
    echo "6. Gestión de servicios"
    echo "7. Gestión de permisos"
    echo "8. Salir"
    read -p "Seleccione una opción: " opcion
    case $opcion in
        1)
            bash "$DIR/usuarios.sh"
            ;;
        2)
            bash "$DIR/grupos.sh"
            ;;
        3)
            bash "$DIR/registrar_grupo.sh"
            ;;
        4)
            bash "$DIR/listar.sh"
            ;;
        5)
            bash "$DIR/crear_roles.sh"
            ;;
        6)
            bash "$DIR/servicios.sh"
            ;;
        7)
            bash "$DIR/permisos.sh"
            ;;
        8)
            echo "Saliendo del sistema..."
            exit 0
            ;;
        *)
            echo "Opción inválida."
            ;;
    esac
done
