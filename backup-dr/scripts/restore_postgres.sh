#!/usr/bin/env bash
set -euo pipefail

FILE=$1
DB_NAME=${DB_NAME:-app}
psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;'
gunzip -c "$FILE" | psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME"
echo "Restored from $FILE"


