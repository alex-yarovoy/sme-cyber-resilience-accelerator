#!/usr/bin/env bash
set -euo pipefail

IN_FILE=${1:-}
OUT_FILE=${2:-}
PASS=${BACKUP_ENC_PASS:-}

if [ -z "$IN_FILE" ] || [ -z "$OUT_FILE" ] || [ -z "$PASS" ]; then
  echo "Usage: BACKUP_ENC_PASS=... $0 <input.enc> <output>" >&2; exit 1
fi

openssl enc -d -aes-256-cbc -pbkdf2 -in "$IN_FILE" -out "$OUT_FILE" -k "$PASS"
echo "Decrypted -> $OUT_FILE"


