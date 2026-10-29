#!/usr/bin/env bash
set -euo pipefail

# Restore a .sql.gz Postgres backup into an ephemeral postgres:14 container,
# measure the restore, verify the restored tables and write a text + JSON
# report under reports/. Exit code: 0 = PASS, 1 = FAIL.
#
# Optional environment:
#   DR_EXPECTED_MANIFEST     file with "<table> <rows> <md5>" lines (see table_checksums.sh);
#                            every listed table must match after restore
#   DR_EXPECTED_SHA256       expected sha256 of the backup file (defaults to the
#                            <backup>.sha256 sidecar when present)
#   DR_BACKUP_CREATED_EPOCH  backup creation time (epoch seconds); defaults to file mtime
#   DR_RESTORE_STRICT=1      stop the restore on the first SQL error (psql ON_ERROR_STOP)
#   DR_SOURCE_DESC           free-text description of where the backup came from
#   DR_REPORT_SUFFIX         appended to the report file name
#   DR_CONTAINER             container name (default dr-postgres)
#   CLAMAV_SCAN=1            run clamscan on the backup artifact when available

# Inputs
BACKUP_FILE=${1:-}
if [ -z "$BACKUP_FILE" ]; then
  echo "Usage: $0 <backup_file.sql.gz>" >&2; exit 1
fi
if [ ! -f "$BACKUP_FILE" ]; then
  echo "Backup file not found: $BACKUP_FILE" >&2; exit 1
fi

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
CONTAINER=${DR_CONTAINER:-dr-postgres}
EXPECTED_MANIFEST=${DR_EXPECTED_MANIFEST:-}

now_ms() {
  if command -v python3 >/dev/null 2>&1; then
    python3 -c 'import time; print(int(time.time() * 1000))'
  else
    echo "$(($(date +%s) * 1000))"
  fi
}

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

ms_utc() {
  printf '%s.%03dZ' "$(epoch_utc "$(($1 / 1000))" | sed 's/Z$//')" $(($1 % 1000))
}

ms_to_seconds() {
  printf '%d.%03d' $(($1 / 1000)) $(($1 % 1000))
}

sha256_of() {
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$1" | awk '{print $1}'
  else
    shasum -a 256 "$1" | awk '{print $1}'
  fi
}

file_mtime() {
  stat -c %Y "$1" 2>/dev/null || stat -f %m "$1"
}

json_str() {
  local s=${1//\\/\\\\}
  s=${s//\"/\\\"}
  printf '"%s"' "$s"
}

json_int() {
  case "$1" in
    ''|*[!0-9]*) printf 'null' ;;
    *) printf '%s' "$1" ;;
  esac
}

STATUS=PASS
FAILURES=()

START_MS=$(now_ms)
echo "[DR] Starting drill with $BACKUP_FILE"

# Backup integrity
BACKUP_SHA256=$(sha256_of "$BACKUP_FILE")
EXPECTED_SHA256=${DR_EXPECTED_SHA256:-}
if [ -z "$EXPECTED_SHA256" ] && [ -f "$BACKUP_FILE.sha256" ]; then
  EXPECTED_SHA256=$(awk '{print $1}' "$BACKUP_FILE.sha256")
fi
if [ -z "$EXPECTED_SHA256" ]; then
  SHA256_CHECK=NOT_PROVIDED
elif [ "$EXPECTED_SHA256" = "$BACKUP_SHA256" ]; then
  SHA256_CHECK=MATCH
else
  SHA256_CHECK=MISMATCH
  STATUS=FAIL
  FAILURES+=("backup sha256 mismatch")
fi

BACKUP_CREATED_EPOCH=${DR_BACKUP_CREATED_EPOCH:-$(file_mtime "$BACKUP_FILE")}

# Provision ephemeral postgres via docker
docker run --name "$CONTAINER" -e POSTGRES_PASSWORD=pass -e POSTGRES_DB=app -d postgres:14 >/dev/null
trap 'docker rm -f "$CONTAINER" >/dev/null' EXIT

# Wait for postgres. TCP check: the image's init-time server listens on the
# unix socket only, so this returns once the final server is up.
READY=0
for _ in $(seq 1 60); do
  if docker exec "$CONTAINER" pg_isready -h 127.0.0.1 -U postgres >/dev/null 2>&1; then
    READY=1; break
  fi
  sleep 1
done
if [ "$READY" != "1" ]; then
  echo "[DR] Postgres container did not become ready" >&2
  exit 1
fi

# Restore
PSQL_RESTORE_OPTS=(-X -q -U postgres -d app)
if [ "${DR_RESTORE_STRICT:-0}" = "1" ]; then
  PSQL_RESTORE_OPTS+=(-v ON_ERROR_STOP=1)
fi
RESTORE_LOG=$(mktemp)
RESTORE_START_MS=$(now_ms)
RESTORE_EXIT=0
gunzip -c "$BACKUP_FILE" | docker exec -i "$CONTAINER" psql "${PSQL_RESTORE_OPTS[@]}" >/dev/null 2>"$RESTORE_LOG" || RESTORE_EXIT=$?
RESTORE_END_MS=$(now_ms)
RESTORE_ERRORS=$(grep -c 'ERROR' "$RESTORE_LOG" || true)
if [ "$RESTORE_EXIT" != "0" ]; then
  STATUS=FAIL
  FAILURES+=("restore exited with code $RESTORE_EXIT")
  sed 's/^/[DR] restore: /' "$RESTORE_LOG" >&2
elif [ "$RESTORE_ERRORS" != "0" ]; then
  echo "[DR] restore reported $RESTORE_ERRORS SQL error line(s) (set DR_RESTORE_STRICT=1 to stop on the first one)" >&2
fi
rm -f "$RESTORE_LOG"

RESTORE_ELAPSED_MS=$((RESTORE_END_MS - RESTORE_START_MS))
BACKUP_AGE_S=$((RESTORE_START_MS / 1000 - BACKUP_CREATED_EPOCH))

# Sanity tests
if ! docker exec "$CONTAINER" psql -X -U postgres -d app -c 'SELECT 1;' >/dev/null; then
  STATUS=FAIL
  FAILURES+=("sanity query failed")
fi

# Table verification
RESTORED_MANIFEST=$(mktemp)
"$SCRIPT_DIR/table_checksums.sh" "$CONTAINER" app postgres > "$RESTORED_MANIFEST" || {
  STATUS=FAIL
  FAILURES+=("could not compute restored table checksums")
}

TABLE_LINES=()
TABLE_JSON=()
if [ -n "$EXPECTED_MANIFEST" ]; then
  VERIFICATION=MANIFEST
  while read -r TABLE EXP_ROWS EXP_MD5; do
    [ -z "$TABLE" ] && continue
    GOT_ROWS=$(awk -v t="$TABLE" '$1 == t {print $2}' "$RESTORED_MANIFEST")
    GOT_MD5=$(awk -v t="$TABLE" '$1 == t {print $3}' "$RESTORED_MANIFEST")
    if [ "$GOT_ROWS" = "$EXP_ROWS" ] && [ "$GOT_MD5" = "$EXP_MD5" ]; then
      RESULT=PASS
    else
      RESULT=FAIL
      STATUS=FAIL
      FAILURES+=("table $TABLE does not match")
    fi
    TABLE_LINES+=("$(printf '  %-20s rows=%s/%s md5=%s/%s %s' "$TABLE" "${GOT_ROWS:-missing}" "$EXP_ROWS" "${GOT_MD5:-missing}" "$EXP_MD5" "$RESULT")")
    TABLE_JSON+=("{\"table\":$(json_str "$TABLE"),\"rows_restored\":$(json_int "$GOT_ROWS"),\"rows_expected\":$(json_int "$EXP_ROWS"),\"md5_restored\":$(json_str "${GOT_MD5:-missing}"),\"md5_expected\":$(json_str "$EXP_MD5"),\"result\":\"$RESULT\"}")
  done < "$EXPECTED_MANIFEST"
  if [ "${#TABLE_LINES[@]}" = "0" ]; then
    STATUS=FAIL
    FAILURES+=("expected manifest is empty")
  fi
else
  VERIFICATION=NOT_RUN
  while read -r TABLE GOT_ROWS GOT_MD5; do
    [ -z "$TABLE" ] && continue
    TABLE_LINES+=("$(printf '  %-20s rows=%s md5=%s (no expected manifest)' "$TABLE" "$GOT_ROWS" "$GOT_MD5")")
    TABLE_JSON+=("{\"table\":$(json_str "$TABLE"),\"rows_restored\":$(json_int "$GOT_ROWS"),\"md5_restored\":$(json_str "$GOT_MD5"),\"result\":\"NOT_VERIFIED\"}")
  done < "$RESTORED_MANIFEST"
fi
rm -f "$RESTORED_MANIFEST"

if [ "${CLAMAV_SCAN:-0}" = "1" ] && command -v clamscan >/dev/null 2>&1; then
  echo "[DR] clamscan on backup artifact (host tool; limited vs full DB scan)"
  clamscan --no-summary "$BACKUP_FILE"
fi

END_MS=$(now_ms)
DURATION_MS=$((END_MS - START_MS))
echo "[DR] Drill completed in $(ms_to_seconds "$DURATION_MS")s: $STATUS"

# Produce report
mkdir -p reports
REPORT_BASE="reports/drill_$(date -u +%Y%m%dT%H%M%SZ)${DR_REPORT_SUFFIX:-}"
REPORT="$REPORT_BASE.txt"
REPORT_JSON="$REPORT_BASE.json"
{
  echo "DR Drill Report"
  echo "Backup:                          $BACKUP_FILE"
  echo "Backup SHA256:                   $BACKUP_SHA256"
  echo "Backup SHA256 expected:          ${EXPECTED_SHA256:-none} ($SHA256_CHECK)"
  echo "Backup created (UTC):            $(epoch_utc "$BACKUP_CREATED_EPOCH")"
  [ -n "${DR_SOURCE_DESC:-}" ] && echo "Source:                          $DR_SOURCE_DESC"
  echo "Drill start (UTC):               $(ms_utc "$START_MS")"
  echo "Restore start (UTC):             $(ms_utc "$RESTORE_START_MS")"
  echo "Restore end (UTC):               $(ms_utc "$RESTORE_END_MS")"
  echo "Restore elapsed (s, RTO obs.):   $(ms_to_seconds "$RESTORE_ELAPSED_MS")"
  echo "Backup age at restore (s, RPO):  $BACKUP_AGE_S"
  echo "Drill end (UTC):                 $(ms_utc "$END_MS")"
  echo "Duration(s):                     $(ms_to_seconds "$DURATION_MS")"
  echo "Restore exit code:               $RESTORE_EXIT (SQL error lines: $RESTORE_ERRORS)"
  echo "Verification:                    $VERIFICATION"
  echo "Tables (restored/expected):"
  for LINE in "${TABLE_LINES[@]+"${TABLE_LINES[@]}"}"; do echo "$LINE"; done
  for F in "${FAILURES[@]+"${FAILURES[@]}"}"; do echo "Failure: $F"; done
  echo "Status: $STATUS"
} > "$REPORT"

FAILURES_JSON=()
for F in "${FAILURES[@]+"${FAILURES[@]}"}"; do FAILURES_JSON+=("$(json_str "$F")"); done
{
  printf '{\n'
  printf '  "report": "dr_drill",\n'
  printf '  "backup_file": %s,\n' "$(json_str "$BACKUP_FILE")"
  printf '  "backup_sha256": "%s",\n' "$BACKUP_SHA256"
  printf '  "backup_sha256_expected": %s,\n' "$(json_str "$EXPECTED_SHA256")"
  printf '  "backup_sha256_check": "%s",\n' "$SHA256_CHECK"
  printf '  "backup_created_utc": "%s",\n' "$(epoch_utc "$BACKUP_CREATED_EPOCH")"
  printf '  "source": %s,\n' "$(json_str "${DR_SOURCE_DESC:-}")"
  printf '  "drill_start_utc": "%s",\n' "$(ms_utc "$START_MS")"
  printf '  "restore_start_utc": "%s",\n' "$(ms_utc "$RESTORE_START_MS")"
  printf '  "restore_end_utc": "%s",\n' "$(ms_utc "$RESTORE_END_MS")"
  printf '  "restore_elapsed_seconds": %s,\n' "$(ms_to_seconds "$RESTORE_ELAPSED_MS")"
  printf '  "backup_age_at_restore_seconds": %s,\n' "$BACKUP_AGE_S"
  printf '  "drill_end_utc": "%s",\n' "$(ms_utc "$END_MS")"
  printf '  "drill_duration_seconds": %s,\n' "$(ms_to_seconds "$DURATION_MS")"
  printf '  "restore_exit_code": %s,\n' "$RESTORE_EXIT"
  printf '  "restore_sql_error_lines": %s,\n' "$RESTORE_ERRORS"
  printf '  "verification": "%s",\n' "$VERIFICATION"
  printf '  "tables": [%s],\n' "$(IFS=,; echo "${TABLE_JSON[*]+"${TABLE_JSON[*]}"}")"
  printf '  "failures": [%s],\n' "$(IFS=,; echo "${FAILURES_JSON[*]+"${FAILURES_JSON[*]}"}")"
  printf '  "status": "%s"\n' "$STATUS"
  printf '}\n'
} > "$REPORT_JSON"

echo "Report: $REPORT"
echo "Report (JSON): $REPORT_JSON"

[ "$STATUS" = "PASS" ]
