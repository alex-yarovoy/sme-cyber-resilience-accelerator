#!/usr/bin/env bash
# Acceptance check: the sme-redact ingest pipeline masks sensitive values before indexing.
# Loads configs/ingest-pipeline.json into Elasticsearch, runs every case in
# tests/redaction-cases.json through the _simulate API, and fails on any mismatch.
#
# Usage: ES_URL=http://localhost:9200 tests/redaction_check.sh   (from logging-alerts/)
set -euo pipefail

ES_URL=${ES_URL:-http://localhost:9200}
PIPELINE_ID=${PIPELINE_ID:-sme-redact}
HERE=$(cd "$(dirname "$0")" && pwd)
PIPELINE_FILE="$HERE/../configs/ingest-pipeline.json"
CASES_FILE="$HERE/redaction-cases.json"

command -v jq >/dev/null 2>&1 || { echo "jq is required" >&2; exit 2; }

for _ in $(seq 1 60); do
  curl -fsS "$ES_URL/_cluster/health" >/dev/null 2>&1 && break
  sleep 2
done
curl -fsS "$ES_URL/_cluster/health" >/dev/null || { echo "Elasticsearch not reachable at $ES_URL" >&2; exit 2; }

curl -fsS -X PUT "$ES_URL/_ingest/pipeline/$PIPELINE_ID" \
  -H 'Content-Type: application/json' --data-binary "@$PIPELINE_FILE" >/dev/null

docs=$(jq '{docs: [.[] | {_source: {message: .message}}]}' "$CASES_FILE")
result=$(curl -fsS -X POST "$ES_URL/_ingest/pipeline/$PIPELINE_ID/_simulate" \
  -H 'Content-Type: application/json' -d "$docs")

total=$(jq 'length' "$CASES_FILE")
failed=0
for i in $(seq 0 $((total - 1))); do
  name=$(jq -r ".[$i].name" "$CASES_FILE")
  expected=$(jq -r ".[$i].expected" "$CASES_FILE")
  actual=$(jq -r ".docs[$i].doc._source.message // .docs[$i].error.reason" <<<"$result")
  if [ "$actual" = "$expected" ]; then
    echo "PASS  $name"
  else
    echo "FAIL  $name"
    echo "      expected: $expected"
    echo "      actual:   $actual"
    failed=$((failed + 1))
  fi
done

echo "Redaction check: $((total - failed))/$total cases passed"
[ "$failed" -eq 0 ]
