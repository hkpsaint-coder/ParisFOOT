#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be set in the hosting environment." >&2
    exit 1
fi

if [ -z "${ADMIN_PASSWORD:-}" ] || [ "$ADMIN_PASSWORD" = "password" ]; then
    echo "Set a unique ADMIN_PASSWORD in the hosting environment." >&2
    exit 1
fi

php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan view:cache

exec "$@"
