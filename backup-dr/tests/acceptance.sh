#!/usr/bin/env bash
set -euo pipefail

# Backup and recovery acceptance check (Docker, openssl, gzip, python3 or date).
#
#   1. start a source postgres:14 container and load tests/fixtures/seed.sql
#   2. record per-table row counts and content checksums of the source
#   3. back it up with scripts/backup_postgres.sh
#   4. encrypt with scripts/encrypt_backup.sh, decrypt with scripts/decrypt_backup.sh
#   5. restore and verify with scripts/dr_drill.sh against the source manifest
#   6. negative control: restore a copy of the dump with one row removed and
#      require dr_drill.sh to report FAIL (skip with ACCEPTANCE_SKIP_NEGATIVE=1)
#
# Reports land in backup-dr/reports/. Exit code 0 only when every step passes.
# BACKUP_ENC_PASS is used when set; otherwise a random one-off passphrase is generated.

KIT_DIR=$(cd "$(dirname "$0")/.." && pwd)
cd "$KIT_DIR"

RUN_ID="$(date -u +%Y%m%dT%H%M%SZ)-$$"
SRC_CONTAINER="dr-acceptance-src-$$"
DRILL_CONTAINER="dr-acceptance-restore-$$"
SRC_DB=dr_acceptance
WORK_DIR=$(mktemp -d)
SHIM_DIR="$WORK_DIR/bin"

cleanup() {
  docker rm -f "$SRC_CONTAINER" >/dev/null 2>&1 || true
  rm -rf "$WORK_DIR"
}
trap cleanup EXIT

log() { echo "[acceptance] $*"; }

if [ -z "${BACKUP_ENC_PASS:-}" ]; then
  BACKUP_ENC_PASS=$(openssl rand -hex 32)
fi
export BACKUP_ENC_PASS

# 1. Source database with deterministic fixture
log "starting source postgres ($SRC_CONTAINER)"
docker run --name "$SRC_CONTAINER" -e POSTGRES_PASSWORD=pass -e POSTGRES_DB="$SRC_DB" -d postgres:14 >/dev/null
READY=0
for _ in $(seq 1 60); do
  if docker exec "$SRC_CONTAINER" pg_isready -h 127.0.0.1 -U postgres >/dev/null 2>&1; then
    READY=1; break
  fi
  sleep 1
done
[ "$READY" = "1" ] || { echo "source postgres did not become ready" >&2; exit 1; }

log "loading tests/fixtures/seed.sql"
docker exec -i "$SRC_CONTAINER" psql -X -q -v ON_ERROR_STOP=1 -U postgres -d "$SRC_DB" < tests/fixtures/seed.sql

# 2. Source manifest
MANIFEST="$WORK_DIR/source_manifest.txt"
scripts/table_checksums.sh "$SRC_CONTAINER" "$SRC_DB" postgres > "$MANIFEST"
log "source manifest:"
sed 's/^/  /' "$MANIFEST"

# 3. Backup with the kit script. pg_dump runs inside the source container so the
#    client version always matches the server, locally and in CI.
mkdir -p "$SHIM_DIR"
cat > "$SHIM_DIR/pg_dump" <<SHIM
#!/usr/bin/env bash
exec docker exec -i "$SRC_CONTAINER" pg_dump "\$@"
SHIM
chmod +x "$SHIM_DIR/pg_dump"

BACKUP_OUT=$(PATH="$SHIM_DIR:$PATH" DB_HOST=127.0.0.1 DB_USER=postgres DB_NAME="$SRC_DB" scripts/backup_postgres.sh)
echo "$BACKUP_OUT"
BACKUP_FILE=$(echo "$BACKUP_OUT" | sed -n 's/^Created backup //p')
[ -f "$BACKUP_FILE" ] || { echo "backup file not created" >&2; exit 1; }
BACKUP_SHA256=$(awk '{print $1}' "$BACKUP_FILE.sha256")

# 4. Encrypt / decrypt round trip
ENC_FILE="$BACKUP_FILE.enc"
scripts/encrypt_backup.sh "$BACKUP_FILE" "$ENC_FILE"
if gunzip -t < "$ENC_FILE" >/dev/null 2>&1; then
  echo "encrypted artifact is still a readable gzip stream" >&2; exit 1
fi
mkdir -p backups/decrypted
RESTORE_FILE="backups/decrypted/$(basename "$BACKUP_FILE")"
scripts/decrypt_backup.sh "$ENC_FILE" "$RESTORE_FILE"
# Keep the original creation time so the drill reports backup age correctly.
touch -r "$BACKUP_FILE" "$RESTORE_FILE"

# 5. Restore + verify
log "running dr_drill.sh on decrypted backup"
DRILL_EXIT=0
DR_CONTAINER="$DRILL_CONTAINER" \
DR_EXPECTED_MANIFEST="$MANIFEST" \
DR_EXPECTED_SHA256="$BACKUP_SHA256" \
DR_RESTORE_STRICT=1 \
DR_SOURCE_DESC="tests/fixtures/seed.sql -> backup_postgres.sh -> encrypt_backup.sh -> decrypt_backup.sh (run $RUN_ID)" \
  scripts/dr_drill.sh "$RESTORE_FILE" || DRILL_EXIT=$?

if [ "$DRILL_EXIT" != "0" ]; then
  log "FAIL: drill did not pass (exit $DRILL_EXIT)"
  exit 1
fi

# 6. Negative control: the check must detect a missing row
if [ "${ACCEPTANCE_SKIP_NEGATIVE:-0}" != "1" ]; then
  TAMPERED="$WORK_DIR/tampered_$(basename "$BACKUP_FILE")"
  gunzip -c "$RESTORE_FILE" | awk '
    drop == 1 { drop = 0; next }
    /^COPY public\.orders / { drop = 1 }
    { print }
  ' | gzip > "$TAMPERED"
  log "negative control: restoring dump with one orders row removed (must FAIL)"
  NEG_EXIT=0
  DR_CONTAINER="$DRILL_CONTAINER" \
  DR_EXPECTED_MANIFEST="$MANIFEST" \
  DR_RESTORE_STRICT=1 \
  DR_REPORT_SUFFIX="_negative-control" \
  DR_SOURCE_DESC="negative control: decrypted backup with first public.orders data row removed (run $RUN_ID)" \
    scripts/dr_drill.sh "$TAMPERED" || NEG_EXIT=$?
  if [ "$NEG_EXIT" = "0" ]; then
    log "FAIL: negative control passed, verification did not detect the removed row"
    exit 1
  fi
  log "negative control detected the tampered restore (exit $NEG_EXIT)"
fi

log "PASS"
