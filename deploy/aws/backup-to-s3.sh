#!/bin/bash
# ============================================================
# PEGASUS ERP — Daily DB Backup to S3
# Place this in /var/www/pegasus_erp/deploy/aws/ on EC2.
# Add to crontab: 0 3 * * * /var/www/pegasus_erp/deploy/aws/backup-to-s3.sh
# Requires: awscli + IAM role with s3:PutObject on $S3_BACKUP_BUCKET
# ============================================================
set -euo pipefail

APP_DIR=/var/www/pegasus_erp
source "$APP_DIR/.env"

TS=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$APP_DIR/backups"
DUMP_FILE="$BACKUP_DIR/pegasus_${TS}.dump"
S3_PATH="s3://${S3_BACKUP_BUCKET}/daily/pegasus_${TS}.dump"

mkdir -p "$BACKUP_DIR"

echo "[$(date)] Starting DB backup..."
PGPASSWORD="$DB_PASS" pg_dump \
    -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USER" \
    -d "$DB_NAME" -Fc -v -f "$DUMP_FILE" 2>&1 | tail -3

SIZE=$(du -h "$DUMP_FILE" | cut -f1)
echo "[$(date)] Dump created: $DUMP_FILE ($SIZE)"

# Upload to S3 with server-side encryption
aws s3 cp "$DUMP_FILE" "$S3_PATH" \
    --region "${AWS_REGION:-ap-southeast-1}" \
    --sse AES256 \
    --storage-class STANDARD_IA

echo "[$(date)] Uploaded to $S3_PATH"

# Local retention: keep last 7 days
find "$BACKUP_DIR" -name 'pegasus_*.dump' -mtime +7 -delete

# S3 lifecycle policy should handle long-term retention (configure separately)
# Optional: delete S3 daily backups older than 90 days here if no lifecycle policy
# aws s3 ls "s3://${S3_BACKUP_BUCKET}/daily/" | ...

echo "[$(date)] Backup complete."
