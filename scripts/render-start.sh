#!/usr/bin/env sh
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

if [ -n "$DB_DATABASE" ]; then
  mkdir -p "$(dirname "$DB_DATABASE")"
  if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
  fi
fi

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan migrate --force
php artisan storage:link || true
php artisan optimize

php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
