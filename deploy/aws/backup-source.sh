#!/bin/bash
# ============================================================
# PEGASUS ERP — Weekly Source Code Backup to S3
# Crontab: 0 4 * * 0 /var/www/pegasus_erp/deploy/aws/backup-source.sh
# ============================================================
set -euo pipefail

APP_DIR=/var/www/pegasus_erp
source "$APP_DIR/.env"

TS=$(date +%Y%m%d_%H%M%S)
ARCHIVE="/tmp/pegasus_source_${TS}.tar.gz"

echo "[$(date)] Archiving source code..."
tar czf "$ARCHIVE" \
    --exclude="$APP_DIR/public/uploads/business_cards" \
    --exclude="$APP_DIR/uploads/cost_imports" \
    --exclude="$APP_DIR/backups" \
    --exclude="$APP_DIR/logs" \
    --exclude="$APP_DIR/.git" \
    --exclude="$APP_DIR/.env" \
    -C / "${APP_DIR#/}"

SIZE=$(du -h "$ARCHIVE" | cut -f1)
echo "[$(date)] Archive: $ARCHIVE ($SIZE)"

aws s3 cp "$ARCHIVE" "s3://${S3_BACKUP_BUCKET}/source/pegasus_source_${TS}.tar.gz" \
    --region "${AWS_REGION:-ap-southeast-1}" \
    --sse AES256

rm -f "$ARCHIVE"
echo "[$(date)] Source backup complete."
