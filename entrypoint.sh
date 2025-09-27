#!/bin/sh
set -e

echo "✅ Using PORT=$PORT"

# Replace $PORT in nginx.conf
envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

# Run migrations + seed demo data
php artisan migrate --force
php artisan db:seed --class=DiscountSeeder --force

# Hand over to supervisord
exec "$@"
