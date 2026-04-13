#!/usr/bin/env bash
set -euo pipefail

# Inputs
BACKUP_FILE=${1:-}
if [ -z "$BACKUP_FILE" ]; then
  echo "Usage: $0 <backup_file.sql.gz>" >&2; exit 1
fi

START=$(date +%s)
echo "[DR] Starting drill with $BACKUP_FILE"

# Provision ephemeral postgres via docker
docker run --name dr-postgres -e POSTGRES_PASSWORD=pass -e POSTGRES_DB=app -p 5544:5432 -d postgres:14 >/dev/null
trap 'docker rm -f dr-postgres >/dev/null' EXIT

export DB_HOST=127.0.0.1 DB_USER=postgres DB_NAME=app

# Wait for postgres
until docker exec dr-postgres pg_isready -U postgres >/dev/null 2>&1; do sleep 1; done

# Restore
gunzip -c "$BACKUP_FILE" | docker exec -i dr-postgres psql -U postgres -d app >/dev/null

# Sanity tests
docker exec dr-postgres psql -U postgres -d app -c 'SELECT 1;' >/dev/null

END=$(date +%s)
DURATION=$((END-START))
echo "[DR] Drill completed in ${DURATION}s"

# Produce simple report
mkdir -p reports
REPORT="reports/drill_$(date +%Y%m%d%H%M%S).txt"
{
  echo "DR Drill Report"
  echo "Backup: $BACKUP_FILE"
  echo "Start:  $(date -d @$START)"
  echo "End:    $(date -d @$END)"
  echo "Duration(s): $DURATION"
  echo "Status: SUCCESS"
} > "$REPORT"
echo "Report: $REPORT"


