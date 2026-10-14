#!/usr/bin/env bash
# Aplica las migraciones pendientes a una instalación existente. Es seguro ejecutarlo varias veces.
#   001_multiuser.sql          -> usuarios y datos por usuario
#   002_user_language.sql      -> idioma por usuario
#   003_operation_type.sql     -> compras y ventas
#   004_scheduled_purchases.sql -> tareas programadas de compra (DCA)
set -euo pipefail
cd "$(dirname "$0")/.."

sql() { docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root "$MYSQL_DATABASE"'; }
col_exists() {  # tabla columna -> imprime 1 si existe, 0 si no
  docker compose exec -T db sh -c "MYSQL_PWD=\"\$MYSQL_ROOT_PASSWORD\" mysql -u root -N -e \"SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='$1' AND column_name='$2'\" \"\$MYSQL_DATABASE\"" | tr -d '\r'
}
table_exists() {  # tabla -> imprime 1 si existe, 0 si no
  docker compose exec -T db sh -c "MYSQL_PWD=\"\$MYSQL_ROOT_PASSWORD\" mysql -u root -N -e \"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='$1'\" \"\$MYSQL_DATABASE\"" | tr -d '\r'
}

pending=()
if [ "$(col_exists investments user_id)" = "0" ]; then pending+=(001_multiuser); fi
if [ "$(col_exists users language)" = "0" ] || [ "${pending[*]:-}" = "001_multiuser" ]; then pending+=(002_user_language); fi
if [ "$(col_exists investments operation_type)" = "0" ]; then pending+=(003_operation_type); fi
if [ "$(table_exists scheduled_purchases)" = "0" ]; then pending+=(004_scheduled_purchases); fi

if [ ${#pending[@]} -eq 0 ]; then
  echo "La base de datos ya está al día. Nada que hacer."
  exit 0
fi

echo "Migraciones pendientes: ${pending[*]}"
echo "Creando backup previo..."
scripts/backup.sh
for m in "${pending[@]}"; do
  echo "Aplicando $m..."
  sql < "database/migrations/$m.sql"
done
echo "Migración completada."
echo "(Si venías de la versión sin usuarios, el primer usuario que se registre heredará los datos existentes.)"
