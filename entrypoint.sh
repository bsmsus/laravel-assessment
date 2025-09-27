#!/bin/sh
set -e

echo "✅ Using PORT=$PORT"

# Ensure Laravel cache + storage dirs exist
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache storage/framework
chmod -R 775 storage bootstrap/cache storage/framework

# Clear stale caches (skip view:clear in production)
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true

# Replace $PORT in nginx.conf
envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

# Run migrations + seed demo data
php artisan migrate --force
php artisan db:seed --class=DiscountSeeder --force
php artisan db:seed --class=UserSeeder --force

# Start supervisord
exec "$@"
