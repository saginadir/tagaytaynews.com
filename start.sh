#!/bin/bash
# CVE Intelligence Platform - Development Server
# Usage: ./start.sh

set -e
cd "$(dirname "$0")"

echo "Starting CVE Intelligence Platform..."
echo ""

# Run migrations
php artisan migrate --force

# Start all services
trap 'kill 0' EXIT

php artisan serve --port=8000 &
echo "  Web server:   http://localhost:8000"

npm run dev &
echo "  Vite dev:     running (HMR)"

php artisan queue:work --sleep=3 --tries=3 --timeout=360 &
echo "  Queue worker: running"

php artisan schedule:work &
echo "  Scheduler:    running"

echo ""
echo "Press Ctrl+C to stop all services."
wait
