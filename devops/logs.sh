#!/usr/bin/env bash
# Usage:
#   ./devops/logs.sh [-f] [nginx|app|queue|scheduler|all]
#   -f  follow (tail -f) — default is print last 200 lines and exit
set -euo pipefail

SERVER=87.99.130.147
SSH_KEY=devops/hetzner_server
SSH="ssh -i $SSH_KEY -o StrictHostKeyChecking=accept-new"

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
        $SSH "www-laravel@$SERVER" "tail $(tail_flags) /var/www/template/storage/logs/laravel.log"
        ;;
    queue)
        $SSH "www-laravel@$SERVER" "journalctl -u 'laravel-queue@*' $(journal_flags)"
        ;;
    scheduler)
        $SSH "www-laravel@$SERVER" "journalctl -u laravel-scheduler $(journal_flags)"
        ;;
    all|*)
        $SSH "www-laravel@$SERVER" "tail $(tail_flags) /var/log/nginx/error.log /var/www/template/storage/logs/laravel.log"
        ;;
esac
