#!/bin/sh
# entrypoint.sh
set -e

# Replace env vars in nginx.conf (PORT)
envsubst '$PORT' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

exec "$@"
