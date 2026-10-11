#!/usr/bin/env bash
# Restablece la contraseña de un usuario que la ha olvidado (requiere acceso al servidor).
# Uso: scripts/reset_password.sh nombre_de_usuario
set -euo pipefail
cd "$(dirname "$0")/.."
[ $# -eq 1 ] || { echo "Uso: $0 nombre_de_usuario" >&2; exit 1; }
read -rsp "Nueva contraseña (8-72 caracteres): " p1; echo
read -rsp "Repite la contraseña: " p2; echo
[ "$p1" = "$p2" ] || { echo "Las contraseñas no coinciden." >&2; exit 1; }
printf '%s\n' "$p1" | docker compose exec -T web php scripts/reset_password.php "$1"
