#!/bin/sh
set -e

echo "✅ Using PORT=$PORT"

APP_DIR="/var/www/html"   # adjust to your actual Laravel root

cd $APP_DIR

# Ensure Laravel cache + storage dirs exist
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache storage/framework
chmod -R 775 storage bootstrap/cache storage/framework

# Replace $PORT in nginx.conf
envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

# Make sure autoload is optimized
composer dump-autoload -o

php artisan migrate:fresh --seed --force

# # Run migrations + seed demo data
# if php artisan migrate:status >/dev/null 2>&1; then
#   php artisan migrate --force
# else
#   echo "⚠️  Skipping migrate: migrations table not ready"
# fi

# php artisan db:seed --class=UserSeeder --force
# php artisan db:seed --class=DiscountSeeder --force

# Start supervisord
exec "$@"
