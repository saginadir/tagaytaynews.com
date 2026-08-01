#!/usr/bin/env bash
# Usage:
#   ./devops/logs.sh [-f] [nginx|app|queue|scheduler|all]
#   -f  follow (tail -f) — default is print last 200 lines and exit
set -euo pipefail

SERVER=ssh.tagaytaynews.com # via Cloudflare Tunnel; direct IP 87.99.130.147 also fine on normal networks
SSH_KEY=devops/hetzner_server
SSH="ssh -i $SSH_KEY -o StrictHostKeyChecking=accept-new"
APP_DIR=/var/www/tagaytaynews

FOLLOW=false
if [[ "${1:-}" == "-f" ]]; then
    FOLLOW=true
    shift
fi

TARGET=${1:-all}

tail_flags() { $FOLLOW && echo "-n 200 -f" || echo "-n 200"; }
journal_flags() { $FOLLOW && echo "-n 200 --follow -q" || echo "-n 200 --no-pager -q"; }

case "$TARGET" in
    nginx)
        $SSH "www-laravel@$SERVER" "sudo tail -f /var/log/nginx/error.log"
        ;;
    app)
        $SSH "www-laravel@$SERVER" "tail $(tail_flags) $APP_DIR/storage/logs/laravel.log"
        ;;
    queue)
        $SSH "www-laravel@$SERVER" "journalctl -u 'tagaytaynews-queue@*' $(journal_flags)"
        ;;
    scheduler)
        $SSH "www-laravel@$SERVER" "journalctl -u tagaytaynews-scheduler $(journal_flags)"
        ;;
    all|*)
        $SSH "www-laravel@$SERVER" "tail $(tail_flags) /var/log/nginx/error.log $APP_DIR/storage/logs/laravel.log"
        ;;
esac
