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

if [ "${CLAMAV_SCAN:-0}" = "1" ] && command -v clamscan >/dev/null 2>&1; then
  echo "[DR] clamscan on backup artifact (host tool; limited vs full DB scan)"
  clamscan --no-summary "$BACKUP_FILE"
fi

END=$(date +%s)
DURATION=$((END-START))
echo "[DR] Drill completed in ${DURATION}s"

epoch_utc() {
  export EPOCH_TS=$1
  if command -v python3 >/dev/null 2>&1; then
    python3 -c "import os; from datetime import datetime, timezone; print(datetime.fromtimestamp(int(os.environ['EPOCH_TS']), tz=timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'))"
  elif date -u -r "$1" +'%Y-%m-%dT%H:%M:%SZ' >/dev/null 2>&1; then
    date -u -r "$1" +'%Y-%m-%dT%H:%M:%SZ'
  else
    date -u -d "@$1" +'%Y-%m-%dT%H:%M:%SZ'
  fi
}

# Produce simple report
mkdir -p reports
REPORT="reports/drill_$(date +%Y%m%d%H%M%S).txt"
{
  echo "DR Drill Report"
  echo "Backup: $BACKUP_FILE"
  echo "Start:  $(epoch_utc "$START")"
  echo "End:    $(epoch_utc "$END")"
  echo "Duration(s): $DURATION"
  echo "Status: SUCCESS"
} > "$REPORT"
echo "Report: $REPORT"


