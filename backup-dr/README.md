# Backup + DR drill kit

Reference **shell automation** for **PostgreSQL** logical backups, **client-side encryption** of dump files, optional **off-site copy** via a hook you provide, and a **disaster-recovery drill** that restores into an ephemeral container and writes a short report.

## What is implemented in this repo (Phase 0)

| Piece | Purpose |
|--------|---------|
| [`scripts/backup_postgres.sh`](scripts/backup_postgres.sh) | `pg_dump` → gzip under `backups/`, plus `sha256sum` sidecar |
| [`scripts/encrypt_backup.sh`](scripts/encrypt_backup.sh) / [`decrypt_backup.sh`](scripts/decrypt_backup.sh) | OpenSSL AES-256-CBC with PBKDF2 (`BACKUP_ENC_PASS`) |
| [`scripts/restore_postgres.sh`](scripts/restore_postgres.sh) | Destructive restore into an existing Postgres (`DROP SCHEMA public CASCADE`) — **lab only** |
| [`scripts/dr_drill.sh`](scripts/dr_drill.sh) | End-to-end drill: temporary `postgres:14` container → restore from `.sql.gz` → sanity SQL → `reports/` text report |
| [`tests/dr_sanity.sh`](tests/dr_sanity.sh) | Minimal connectivity check when `DB_*` env vars are set |

## What is not in this repo yet (see [ROADMAP.md](../ROADMAP.md))

- **MySQL/MariaDB** dumps and restores (same pattern; scripts not shipped here).
- **File-system / volume-level** backups (only database dumps today).
- **S3 Object Lock (WORM)** and **KMS** integration — document as MSP/cloud wiring; no Terraform modules in-tree until roadmap ships them.
- **ClamAV scanning** of restored data — optional: if `clamscan` is on `PATH` and you set `CLAMAV_SCAN=1`, `dr_drill.sh` runs it after restore (best-effort; skips if binary missing).

## RTO / RPO

The kit uses **placeholders you set** with your stakeholders. Example targets often used with SMEs (not enforced by scripts):

- **RTO:** 4 h  
- **RPO:** 1 h  

Adjust in your runbooks and monitoring; scripts only help you **measure drill duration** in the generated report.

## Quick start (lab)

Prerequisites: `docker`, `pg_dump` / `psql` (host tools) for backup/restore scripts against a real DB; **only Docker** needed for `dr_drill.sh`.

### 1. Backup from a running Postgres

```bash
export DB_HOST=127.0.0.1 DB_USER=postgres DB_NAME=app
./scripts/backup_postgres.sh
```

### 2. Encrypt artifact

```bash
export BACKUP_ENC_PASS='use-a-long-random-secret'
./scripts/encrypt_backup.sh backups/app_TIMESTAMP.sql.gz backups/app_TIMESTAMP.sql.gz.enc
```

### 3. Off-site copy (optional)

Implement your own `rclone`, `aws s3 cp`, or MSP script; this repo does not ship cloud credentials or buckets.

### 4. DR drill (uses Docker only)

```bash
./scripts/decrypt_backup.sh backups/app_TIMESTAMP.sql.gz.enc backups/app_TIMESTAMP.sql.gz
./scripts/dr_drill.sh backups/app_TIMESTAMP.sql.gz
# Optional malware scan after restore (host must have clamscan):
CLAMAV_SCAN=1 ./scripts/dr_drill.sh backups/app_TIMESTAMP.sql.gz
```

Reports appear under `reports/`.

## Principles

- **3-2-1** — You keep multiple copies and at least one off-site; scripts help automate **1→2** (encrypt, copy) once you wire storage.
- **Tested recovery** — A backup without a restored row count (or drill) is incomplete; `dr_drill.sh` is the minimal automated check.
