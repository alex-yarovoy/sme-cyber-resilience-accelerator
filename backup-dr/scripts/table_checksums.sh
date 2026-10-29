#!/usr/bin/env bash
set -euo pipefail

# Print one line per table in the public schema of a Postgres database that
# runs in a Docker container:
#   <table> <row_count> <md5_of_rows_sorted_by_text>
# Used by dr_drill.sh (restored side) and tests/acceptance.sh (source side),
# so both sides are measured with exactly the same query.

CONTAINER=${1:-}
DB=${2:-}
DB_USER=${3:-postgres}

if [ -z "$CONTAINER" ] || [ -z "$DB" ]; then
  echo "Usage: $0 <container> <database> [user]" >&2
  exit 1
fi

run_psql() {
  docker exec -i "$CONTAINER" psql -X -A -t -q -v ON_ERROR_STOP=1 -U "$DB_USER" -d "$DB" "$@"
}

TABLES=$(run_psql -c "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename;")

for TABLE in $TABLES; do
  run_psql -c "SELECT '${TABLE}' || ' ' || count(*) || ' ' || md5(coalesce(string_agg(t::text, E'\\n' ORDER BY t::text), '')) FROM public.\"${TABLE}\" AS t;"
done
