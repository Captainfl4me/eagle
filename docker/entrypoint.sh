#!/bin/sh
set -e

cd /var/www/html

# ---------------------------------------------------------------- application key
# No .env is shipped with the image: Laravel is configured entirely through OS
# environment variables (see Dockerfile ENV). Generate a key on first boot if
# none is provided, and expose it to the environment for this process tree.
if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="$(php artisan key:generate --show --no-interaction)"
fi

# ---------------------------------------------------------------- database
# Ensure the SQLite database exists and the schema is up to date.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    if [ "${DB_DATABASE:-:memory:}" = ":memory:" ]; then
        echo ">> DB_DATABASE=:memory: (no persistence); skipping migrate"
        php artisan migrate --no-interaction --force --pretend >/dev/null 2>&1 || true
    else
        mkdir -p "$(dirname "$DB_DATABASE")"
        if [ ! -f "$DB_DATABASE" ]; then
            : > "$DB_DATABASE"
        fi
        php artisan migrate --no-interaction --force
    fi
fi

# ---------------------------------------------------------------- cache
# Warm Laravel caches for a production boot (best-effort).
php artisan config:clear >/dev/null 2>&1 || true
php artisan config:cache >/dev/null 2>&1 || true
php artisan route:cache >/dev/null 2>&1 || true
php artisan event:cache >/dev/null 2>&1 || true

# ---------------------------------------------------------------- serve
exec php /var/www/html/artisan serve --host=0.0.0.0 --port="${APP_PORT:-8080}"