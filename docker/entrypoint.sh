#!/bin/sh
# T-17: the database starts empty; migrations create the schema (ADR-001),
# then the initial admin is created from ADMIN_USERNAME / ADMIN_PASSWORD (D-10).
set -e

if [ -z "$APP_KEY" ]; then
  export APP_KEY="$(php artisan key:generate --show)"
fi

until php artisan migrate --force; do
  echo "Waiting for the database..."
  sleep 2
done

php artisan library:bootstrap-admin
exec php artisan serve --host=0.0.0.0 --port=8000
