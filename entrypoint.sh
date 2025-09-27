#!/bin/sh
set -e

echo "✅ Using PORT=$PORT"

# Ensure Laravel cache + storage dirs exist
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache storage/framework
chmod -R 775 storage bootstrap/cache storage/framework

# Replace $PORT in nginx.conf
envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

# Optimize autoload (ensure factories load cleanly)
composer dump-autoload -o

# Run migrations + seed demo data as www-data
su -s /bin/sh -c "php artisan migrate --force" www-data
su -s /bin/sh -c "php artisan db:seed --class=UserSeeder --force" www-data
su -s /bin/sh -c "php artisan db:seed --class=DiscountSeeder --force" www-data

# Start supervisord
exec "$@"
