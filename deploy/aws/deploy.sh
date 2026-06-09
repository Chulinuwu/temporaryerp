#!/bin/bash
# ============================================================
# PEGASUS ERP — Deploy from local → EC2
# Run on LOCAL PC (Git Bash / WSL). Requires ssh key.
# Usage: ./deploy.sh <ec2-user>@<elastic-ip>
# Example: ./deploy.sh ubuntu@13.250.xxx.xxx
# ============================================================
set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <user>@<host>"
    echo "Example: $0 ubuntu@13.250.100.200"
    exit 1
fi

TARGET="$1"
KEY="${SSH_KEY:-$HOME/.ssh/pegasus-key.pem}"
SRC_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
REMOTE_DIR="/var/www/pegasus_erp"

echo "==> Source:  $SRC_DIR"
echo "==> Target:  $TARGET:$REMOTE_DIR"
echo "==> SSH key: $KEY"

# Pre-checks
[[ -f "$KEY" ]] || { echo "SSH key not found at $KEY"; exit 1; }
command -v rsync >/dev/null || { echo "rsync required"; exit 1; }

# Sync files (exclude dev artifacts)
rsync -avz --delete \
    --exclude '.git' \
    --exclude '.claude' \
    --exclude '.vscode' \
    --exclude 'backups' \
    --exclude 'logs' \
    --exclude 'node_modules' \
    --exclude '*.log' \
    --exclude '*.tmp' \
    --exclude '.env' \
    --exclude 'public/uploads/business_cards/*' \
    --exclude 'uploads/cost_imports/*' \
    --exclude 'QT data' \
    -e "ssh -i $KEY -o StrictHostKeyChecking=accept-new" \
    "$SRC_DIR/" "$TARGET:$REMOTE_DIR/"

# Fix ownership & permissions on remote
ssh -i "$KEY" "$TARGET" "sudo chown -R www-data:www-data $REMOTE_DIR && \
    sudo chmod -R 775 $REMOTE_DIR/public/uploads $REMOTE_DIR/uploads $REMOTE_DIR/backups || true && \
    sudo chmod 600 $REMOTE_DIR/.env 2>/dev/null || true && \
    sudo systemctl reload php8.2-fpm nginx"

echo "==> Deploy complete."
echo "==> Access: https://$(echo $TARGET | cut -d@ -f2)/"
