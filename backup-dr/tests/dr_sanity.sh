#!/usr/bin/env bash
set -euo pipefail

# Basic DR sanity check example
psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c 'SELECT 1;' >/dev/null
echo "DB connection OK"


