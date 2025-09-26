#!/bin/sh
set -e

if [ -z "$PORT" ]; then
  echo "❌ ERROR: \$PORT is not set."
  exit 1
fi

echo "✅ Using PORT=$PORT"
echo "👉 Environment dump"
env | grep PORT

envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

exec "$@"
