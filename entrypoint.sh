#!/bin/sh
set -e

# Run migrations & cache stuff
php artisan migrate --force || true
php artisan config:cache
php artisan route:cache

exec "$@"
