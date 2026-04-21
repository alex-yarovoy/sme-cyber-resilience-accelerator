#!/usr/bin/env bash
set -euo pipefail

if [ -z "${DB_HOST:-}" ] || [ -z "${DB_USER:-}" ]; then
  echo "Set DB_HOST and DB_USER (and optionally DB_NAME) before running $0" >&2
  exit 1
fi

TS=$(date +%Y%m%d%H%M%S)
DB_NAME=${DB_NAME:-app}
OUT=backups/${DB_NAME}_${TS}.sql.gz
mkdir -p backups
pg_dump -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" | gzip > "$OUT"
sha256sum "$OUT" > "$OUT.sha256"
echo "Created backup $OUT"


