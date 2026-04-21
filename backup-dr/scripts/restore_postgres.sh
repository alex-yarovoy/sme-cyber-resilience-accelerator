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
psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;'
gunzip -c "$FILE" | psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME"
echo "Restored from $FILE"


