#!/usr/bin/env bash
# Local → server deploy script. Run from project root on every deploy.
set -euo pipefail

SERVER=87.99.130.147
SSH_KEY=devops/hetzner_server
APP_DIR=/var/www/template
SSH="ssh -i $SSH_KEY -o StrictHostKeyChecking=accept-new"
QUEUE_WORKERS=2 # to update this, also update systemd service count in devops/provision.sh - and also disable manually extra workers when reducing count

echo "==> [1/4] Running unit tests..."
php artisan test --testsuite=Unit

echo "==> [2/4] Building frontend assets..."
npm run build

echo "==> [3/4] Syncing code to server..."
rsync -avz --delete \
    --exclude='.env*' \
    --exclude='public/hot' \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    --exclude='database/database.*' \
    --exclude='storage/' \
    --exclude='public/storage' \
    --exclude='bootstrap/cache/' \
    --exclude='.git/' \
    --exclude='devops/' \
    -e "$SSH" \
    ./ "www-laravel@$SERVER:$APP_DIR/"

echo "==> [4/4] Running post-deploy commands on server..."
$SSH "www-laravel@$SERVER" "cd $APP_DIR && \
    chmod 775 database && \
    chmod 664 database/database.sqlite 2>/dev/null || true && \
    composer install --no-dev --optimize-autoloader --no-interaction && \
    php artisan storage:link --force && \
    php artisan optimize && \
    php artisan migrate --force && \
    touch storage/logs/laravel.log && chmod 664 storage/logs/laravel.log && \
    php artisan queue:restart && \
    sudo systemctl restart php8.5-fpm"

for i in $(seq 1 $QUEUE_WORKERS); do
    $SSH "www-laravel@$SERVER" "sudo systemctl restart template-queue@$i 2>/dev/null || true"
done

$SSH "www-laravel@$SERVER" "sudo systemctl start template-scheduler.timer 2>/dev/null || true"

echo ""
echo "==> Deploy complete! https://your-domain.com"
