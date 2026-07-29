#!/usr/bin/env bash
# One-time server provision script — run as root via:
#   ssh -i devops/hetzner_server root@YOUR_SERVER_IP 'bash -s' < devops/provision.sh
# Safe to re-run: idempotent guards on cert, user, dirs.
set -euo pipefail

DOMAIN="your-domain.com"
APP_USER="www-laravel"
APP_DIR="/var/www/template"
CERTBOT_EMAIL="your-email@example.com"

echo "==> [1/12] Adding ondrej/php PPA and installing packages..."
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y \
    nginx \
    php8.5-fpm php8.5-cli php8.5-sqlite3 php8.5-mbstring \
    php8.5-xml php8.5-curl php8.5-zip \
    unzip certbot

echo "==> [2/12] Installing Composer globally..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> [3/12] Creating app user: $APP_USER..."
id "$APP_USER" &>/dev/null || useradd -r -m -s /bin/bash "$APP_USER"
usermod -s /bin/bash "$APP_USER"
usermod -aG systemd-journal "$APP_USER"

echo "==> [3b/12] Setting up SSH access for $APP_USER..."
mkdir -p /home/www-laravel/.ssh
chmod 700 /home/www-laravel/.ssh
# Inject deploy key (idempotent: grep prevents duplicates)
DEPLOY_PUBKEY="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIKJZa0gpUBEa97T/Wdhpcor5REkluGZN1ILCWHMQ8RZ/ saginadir@Sagis-MacBook-Pro.local"
grep -qxF "$DEPLOY_PUBKEY" /home/www-laravel/.ssh/authorized_keys 2>/dev/null \
    || echo "$DEPLOY_PUBKEY" >> /home/www-laravel/.ssh/authorized_keys
chmod 600 /home/www-laravel/.ssh/authorized_keys
chown -R www-laravel:www-laravel /home/www-laravel/.ssh

echo "==> [4/12] Creating app directory: $APP_DIR..."
mkdir -p "$APP_DIR"
chown "$APP_USER":www-data "$APP_DIR"
chmod 750 "$APP_DIR"

echo "==> [5/12] Creating storage and bootstrap/cache directories..."
mkdir -p "$APP_DIR/storage/app/public"
mkdir -p "$APP_DIR/storage/framework/cache/data"
mkdir -p "$APP_DIR/storage/framework/sessions"
mkdir -p "$APP_DIR/storage/framework/views"
mkdir -p "$APP_DIR/storage/logs"
mkdir -p "$APP_DIR/bootstrap/cache"
chown -R "$APP_USER":www-data "$APP_DIR/storage" "$APP_DIR/bootstrap"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "==> [6/12] Creating empty SQLite database..."
mkdir -p "$APP_DIR/database"
touch "$APP_DIR/database/database.sqlite"
chown "$APP_USER":www-data "$APP_DIR/database" "$APP_DIR/database/database.sqlite"
chmod 775 "$APP_DIR/database"
chmod 664 "$APP_DIR/database/database.sqlite"

echo "==> [7/12] Obtaining SSL certificate (skipped if already exists)..."
if [ ! -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    systemctl stop nginx 2>/dev/null || true
    certbot certonly --standalone \
        -d "$DOMAIN" -d "www.$DOMAIN" \
        --non-interactive --agree-tos \
        -m "$CERTBOT_EMAIL"
else
    echo "    Certificate already exists, skipping."
fi

echo "==> [8/12] Installing Nginx config..."
cat > /etc/nginx/sites-available/template <<'NGINXCONF'
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://your-domain.com$request_uri;
}

server {
    listen 443 ssl;
    server_name www.your-domain.com;
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    return 301 https://your-domain.com$request_uri;
}

server {
    listen 443 ssl;
    server_name your-domain.com;
    root /var/www/template/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    charset utf-8;

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
}
NGINXCONF

ln -sf /etc/nginx/sites-available/template /etc/nginx/sites-enabled/template
rm -f /etc/nginx/sites-enabled/default
nginx -t

echo "==> [9/12] Starting and enabling Nginx..."
systemctl restart nginx
systemctl enable nginx

echo "==> [10/12] Configuring PHP-FPM pool..."
PHP_POOL="/etc/php/8.5/fpm/pool.d/www.conf"
sed -i 's/^user = .*/user = www-laravel/' "$PHP_POOL"
sed -i 's/^group = .*/group = www-data/' "$PHP_POOL"
sed -i 's/^pm = .*/pm = dynamic/' "$PHP_POOL"
sed -i 's/^pm.max_children = .*/pm.max_children = 20/' "$PHP_POOL"
sed -i 's/^pm.start_servers = .*/pm.start_servers = 5/' "$PHP_POOL"
sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 3/' "$PHP_POOL"
sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 10/' "$PHP_POOL"
sed -i 's|^listen = .*|listen = /run/php/php8.5-fpm.sock|' "$PHP_POOL"
systemctl restart php8.5-fpm
systemctl enable php8.5-fpm

echo "==> [11/12] Installing and enabling systemd units..."
cat > /etc/systemd/system/template-queue@.service <<'EOF'
[Unit]
Description=Queue Worker %i — Template
After=network.target

[Service]
User=www-laravel
Group=www-data
WorkingDirectory=/var/www/template
ExecStart=/usr/bin/php artisan queue:work \
    --sleep=3 --tries=1 --timeout=600 --max-time=3600
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/systemd/system/template-scheduler.service <<'EOF'
[Unit]
Description=Scheduler — Template

[Service]
Type=oneshot
User=www-laravel
Group=www-data
WorkingDirectory=/var/www/template
ExecStart=/usr/bin/php artisan schedule:run
StandardOutput=journal
StandardError=journal
EOF

cat > /etc/systemd/system/template-scheduler.timer <<'EOF'
[Unit]
Description=Run Template Scheduler every minute

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
AccuracySec=1s

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
QUEUE_WORKERS=2
for i in $(seq 1 $QUEUE_WORKERS); do systemctl enable --now "template-queue@$i"; done
systemctl enable --now template-scheduler.timer

echo "==> [12/12] Adding sudoers entry for $APP_USER..."
cat > /etc/sudoers.d/www-laravel <<'EOF'
www-laravel ALL=(ALL) NOPASSWD: /bin/systemctl restart php8.5-fpm, /bin/systemctl restart template-queue@*, /bin/systemctl start template-scheduler.timer, /bin/systemctl restart template-scheduler.timer
EOF
chmod 440 /etc/sudoers.d/www-laravel
visudo -cf /etc/sudoers.d/www-laravel

echo ""
echo "==> Provision complete!"
echo ""
echo "Next steps:"
echo "  1. Point DNS: A record $DOMAIN -> $(curl -s ifconfig.me)"
echo "  2. Create $APP_DIR/.env from .env.production.example"
echo "  3. Run: ./devops/deploy.sh (from local machine)"
echo "  4. On server: cd $APP_DIR && php artisan key:generate"
