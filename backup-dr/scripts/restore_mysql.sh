#!/usr/bin/env bash
set -euo pipefail

FILE=${1:-}
if [ -z "$FILE" ]; then
  echo "Usage: $0 <backup.sql.gz>" >&2
  exit 1
fi
DB_NAME=${DB_NAME:-app}
if [ -z "${DB_HOST:-}" ] || [ -z "${DB_USER:-}" ]; then
  echo "Set DB_HOST and DB_USER in the environment." >&2
  exit 1
fi

if [ -n "${DB_PASSWORD:-}" ]; then
  export MYSQL_PWD="$DB_PASSWORD"
fi

mysql -h "$DB_HOST" -u "$DB_USER" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\`;"
gunzip -c "$FILE" | mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME"
echo "Restored from $FILE"
