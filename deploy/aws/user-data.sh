#!/bin/bash
# ============================================================
# PEGASUS ERP — EC2 Ubuntu 22.04 Initial Setup
# Paste this entire file into EC2 "User data" at launch time.
# Runs as root on first boot.
# ============================================================
set -euxo pipefail

APP_DIR=/var/www/pegasus_erp
LOG_FILE=/var/log/pegasus-cloud-init.log
exec > >(tee -a "$LOG_FILE") 2>&1

echo "==> [$(date)] Starting PEGASUS ERP setup"

# 1) System update
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get upgrade -y

# 2) Base packages
apt-get install -y \
    nginx \
    php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-gd \
    php8.2-xml php8.2-curl php8.2-zip php8.2-intl \
    postgresql-client-15 \
    unzip git curl jq \
    certbot python3-certbot-nginx \
    awscli \
    ufw fail2ban

# 3) Firewall
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# 4) Timezone
timedatectl set-timezone Asia/Bangkok

# 5) App directories
mkdir -p "$APP_DIR"
mkdir -p "$APP_DIR/public/uploads/business_cards"
mkdir -p "$APP_DIR/uploads/cost_imports"
mkdir -p "$APP_DIR/backups"
mkdir -p "$APP_DIR/logs"
chown -R www-data:www-data "$APP_DIR"
chmod -R 775 "$APP_DIR/public/uploads" "$APP_DIR/uploads" "$APP_DIR/backups"

# 6) PHP configuration
cat > /etc/php/8.2/fpm/conf.d/99-pegasus.ini <<'EOF'
memory_limit = 256M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 120
date.timezone = Asia/Bangkok
session.gc_maxlifetime = 7200
session.cookie_httponly = 1
session.cookie_samesite = Lax
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
EOF

touch /var/log/php_errors.log
chown www-data:www-data /var/log/php_errors.log

# 7) Nginx site config (HTTP only; certbot will add HTTPS)
cat > /etc/nginx/sites-available/pegasus <<'NGX'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;

    root /var/www/pegasus_erp/public;
    index index.php index.html;

    client_max_body_size 32M;

    # Hide sensitive files
    location ~ /\.(env|git|ht) { deny all; return 404; }
    location ~* \.(log|sql|dump|bak|sh)$ { deny all; return 404; }
    location ^~ /backups/ { deny all; return 404; }

    # Static assets cache
    location ~* \.(?:jpg|jpeg|png|webp|gif|ico|css|js|svg|woff2?)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public, max-age=604800";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }
}
NGX

# Disable default site, enable pegasus
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/pegasus /etc/nginx/sites-enabled/pegasus

# 8) Placeholder index until real code is deployed
cat > "$APP_DIR/public/index.php" <<'EOF'
<?php
header('Content-Type: text/plain');
echo "PEGASUS ERP — Cloud-init complete. Waiting for app deployment.\n";
echo "Server: " . php_uname('n') . "\n";
echo "PHP:    " . PHP_VERSION . "\n";
echo "Time:   " . date('Y-m-d H:i:s T') . "\n";
EOF

# 9) Restart services
nginx -t && systemctl restart nginx
systemctl restart php8.2-fpm
systemctl enable nginx php8.2-fpm

# 10) Fail2ban basic config
systemctl enable fail2ban
systemctl start fail2ban

# 11) Setup log rotation
cat > /etc/logrotate.d/pegasus <<'EOF'
/var/log/php_errors.log
/var/log/pegasus-cloud-init.log
/var/log/pegasus_backup.log
{
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
EOF

echo "==> [$(date)] PEGASUS ERP setup complete"
echo "==> Next steps:"
echo "   1. Upload source code to $APP_DIR"
echo "   2. Create $APP_DIR/.env with RDS credentials"
echo "   3. Restore DB from S3 backup"
echo "   4. Run certbot --nginx -d your-domain.com"
