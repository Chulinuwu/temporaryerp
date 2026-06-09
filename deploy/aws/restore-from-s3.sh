#!/bin/bash
# ============================================================
# PEGASUS ERP — Restore DB from S3 backup (disaster recovery)
# Usage: ./restore-from-s3.sh s3://pegasus-backups/daily/pegasus_YYYYMMDD_HHMMSS.dump
# ============================================================
set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <s3-path-to-dump>"
    echo "Example: $0 s3://pegasus-backups/daily/pegasus_20260417_030000.dump"
    exit 1
fi

S3_PATH="$1"
APP_DIR=/var/www/pegasus_erp
source "$APP_DIR/.env"

TMP_FILE="/tmp/restore_$(date +%s).dump"

echo "==> Downloading from $S3_PATH"
aws s3 cp "$S3_PATH" "$TMP_FILE" --region "${AWS_REGION:-ap-southeast-1}"

echo "==> File size: $(du -h "$TMP_FILE" | cut -f1)"
echo "==> WARNING: This will OVERWRITE data in $DB_NAME on $DB_HOST"
read -p "==> Continue? (yes/no): " CONFIRM
[[ "$CONFIRM" == "yes" ]] || { echo "Aborted."; rm -f "$TMP_FILE"; exit 0; }

echo "==> Restoring..."
PGPASSWORD="$DB_PASS" pg_restore \
    -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USER" \
    -d "$DB_NAME" \
    -c --if-exists \
    -v "$TMP_FILE" 2>&1 | tail -20

rm -f "$TMP_FILE"
echo "==> Restore complete."
