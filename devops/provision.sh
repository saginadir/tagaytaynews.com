#!/usr/bin/env bash
# Provisions tagaytaynews.com as an ADDITIONAL site on the shared Hetzner box
# (87.99.130.147). The box already serves several sites (gorencoart, ivertubani,
# treatingasd, vorkl, vulnsurge, zukeep) with the same layout: per-site app dir,
# nginx vhost, letsencrypt cert, <site>-queue@ / <site>-scheduler units.
# This script adds tagaytaynews following that convention — it must NOT touch
# the other sites, and it never stops nginx (zero downtime for the other sites).
#
# Run as root (either address works; tunnel works on SSH-filtered networks):
#   ssh -i devops/hetzner_server root@87.99.130.147 'bash -s' < devops/provision.sh
#   ssh root@ssh.tagaytaynews.com 'bash -s' < devops/provision.sh
# Idempotent guards on cert, dirs, units — safe to re-run.
set -euo pipefail

DOMAIN="tagaytaynews.com"
APP_USER="www-laravel"
APP_DIR="/var/www/tagaytaynews"
CERTBOT_EMAIL="saginadir@gmail.com"
QUEUE_WORKERS=2 # keep in sync with devops/deploy.sh

echo "==> [1/8] Sanity-checking base server..."
id "$APP_USER" &>/dev/null || { echo "ERROR: user $APP_USER missing — base box not provisioned"; exit 1; }
systemctl list-unit-files php8.5-fpm.service &>/dev/null || { echo "ERROR: php8.5-fpm missing"; exit 1; }
command -v certbot >/dev/null || { echo "ERROR: certbot missing"; exit 1; }
command -v nginx >/dev/null || { echo "ERROR: nginx missing"; exit 1; }

echo "==> [2/8] Creating app directory: $APP_DIR..."
mkdir -p "$APP_DIR/public"
chown -R "$APP_USER":www-data "$APP_DIR"
chmod 750 "$APP_DIR"

echo "==> [3/8] Creating storage and bootstrap/cache directories..."
mkdir -p "$APP_DIR/storage/app/public"
mkdir -p "$APP_DIR/storage/framework/cache/data"
mkdir -p "$APP_DIR/storage/framework/sessions"
mkdir -p "$APP_DIR/storage/framework/views"
mkdir -p "$APP_DIR/storage/logs"
mkdir -p "$APP_DIR/bootstrap/cache"
chown -R "$APP_USER":www-data "$APP_DIR/storage" "$APP_DIR/bootstrap"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "==> [4/8] Creating empty SQLite database..."
mkdir -p "$APP_DIR/database"
touch "$APP_DIR/database/database.sqlite"
chown "$APP_USER":www-data "$APP_DIR/database" "$APP_DIR/database/database.sqlite"
chmod 775 "$APP_DIR/database"
chmod 664 "$APP_DIR/database/database.sqlite"

echo "==> [5/8] Obtaining SSL certificate via webroot (skipped if already exists)..."
if [ ! -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    # Temporary HTTP-only vhost so certbot HTTP-01 works through the running
    # nginx — no nginx stop, other sites unaffected.
    cat > /etc/nginx/sites-available/tagaytaynews <<'NGINXCONF'
server {
    listen 80;
    server_name tagaytaynews.com www.tagaytaynews.com;
    root /var/www/tagaytaynews/public;
    location /.well-known/acme-challenge/ { try_files $uri =404; }
    location / { return 404; }
}
NGINXCONF
    ln -sf /etc/nginx/sites-available/tagaytaynews /etc/nginx/sites-enabled/tagaytaynews
    nginx -t && systemctl reload nginx
    certbot certonly --webroot -w "$APP_DIR/public" \
        -d "$DOMAIN" -d "www.$DOMAIN" \
        --non-interactive --agree-tos \
        -m "$CERTBOT_EMAIL"
else
    echo "    Certificate already exists, skipping."
fi

echo "==> [6/8] Installing full Nginx vhost..."
cat > /etc/nginx/sites-available/tagaytaynews <<'NGINXCONF'
server {
    listen 80;
    server_name tagaytaynews.com www.tagaytaynews.com;
    return 301 https://tagaytaynews.com$request_uri;
}

server {
    listen 443 ssl;
    server_name www.tagaytaynews.com;
    ssl_certificate /etc/letsencrypt/live/tagaytaynews.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tagaytaynews.com/privkey.pem;
    return 301 https://tagaytaynews.com$request_uri;
}

server {
    listen 443 ssl;
    server_name tagaytaynews.com;
    root /var/www/tagaytaynews/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/tagaytaynews.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tagaytaynews.com/privkey.pem;

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

ln -sf /etc/nginx/sites-available/tagaytaynews /etc/nginx/sites-enabled/tagaytaynews
nginx -t
systemctl reload nginx

echo "==> [7/8] Installing and enabling systemd units..."
cat > /etc/systemd/system/tagaytaynews-queue@.service <<EOF
[Unit]
Description=Queue Worker %i — Tagaytay News
After=network.target

[Service]
User=$APP_USER
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan queue:work \
    --sleep=3 --tries=1 --timeout=600 --max-time=3600
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/systemd/system/tagaytaynews-scheduler.service <<EOF
[Unit]
Description=Scheduler — Tagaytay News

[Service]
Type=oneshot
User=$APP_USER
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan schedule:run
StandardOutput=journal
StandardError=journal
EOF

cat > /etc/systemd/system/tagaytaynews-scheduler.timer <<'EOF'
[Unit]
Description=Run Tagaytay News Scheduler every minute

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
AccuracySec=1s

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
for i in $(seq 1 $QUEUE_WORKERS); do systemctl enable --now "tagaytaynews-queue@$i"; done
systemctl enable --now tagaytaynews-scheduler.timer

echo "==> [8/8] Adding sudoers entries for $APP_USER..."
cat > /etc/sudoers.d/www-laravel-tagaytaynews <<EOF
$APP_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart php8.5-fpm, /bin/systemctl restart tagaytaynews-queue@*, /bin/systemctl start tagaytaynews-scheduler.timer, /bin/systemctl restart tagaytaynews-scheduler.timer
EOF
chmod 440 /etc/sudoers.d/www-laravel-tagaytaynews
visudo -cf /etc/sudoers.d/www-laravel-tagaytaynews

echo ""
echo "==> Provision complete!"
echo ""
echo "Next steps:"
echo "  1. Create $APP_DIR/.env (deploy.sh does NOT sync .env files)"
echo "  2. Run: ./devops/deploy.sh (from local machine)"
