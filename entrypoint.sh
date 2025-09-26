#!/bin/sh

# Replace ${PORT} in nginx.conf with actual Railway-provided $PORT
envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

exec "$@"
