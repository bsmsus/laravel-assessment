#!/bin/sh
set -e

echo "✅ Using PORT=$PORT"

# Ensure Laravel cache dirs exist
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache storage/framework

# Clear stale caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Replace $PORT in nginx.conf
envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

# Run migrations + seed demo data
php artisan migrate --force
php artisan db:seed --class=DiscountSeeder --force

exec "$@"
