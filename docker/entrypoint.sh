#!/usr/bin/env bash
set -e

cd /var/www

# The app container is the "primary" (RUN_MIGRATIONS=true by default). It owns
# bootstrapping the shared, volume-mounted source: installing dependencies,
# generating the app key, and running migrations. Worker containers (queue,
# scheduler) set RUN_MIGRATIONS=false and only WAIT for that bootstrap to finish,
# so they never race the primary writing into the same volume.
IS_PRIMARY="${RUN_MIGRATIONS:-true}"

if [ "${IS_PRIMARY}" = "true" ]; then
    # Install PHP dependencies if they are missing (e.g. fresh checkout / clean volume).
    if [ ! -f vendor/autoload.php ]; then
        echo "[entrypoint] Installing composer dependencies..."
        composer install --no-interaction --prefer-dist
    fi

    # Generate an app key if one is not set.
    if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
        echo "[entrypoint] Generating application key..."
        php artisan key:generate --force
    fi
else
    # Worker container: wait until the primary has installed dependencies and
    # set the app key before continuing.
    echo "[entrypoint] Waiting for the app to be bootstrapped (vendor + APP_KEY)..."
    until [ -f vendor/autoload.php ] && grep -q "^APP_KEY=base64:" .env 2>/dev/null; do
        sleep 2
    done
fi

# Front-end assets are handled by the dedicated `vite` service (HMR in dev).
# Run `docker compose exec app npm run build` if you want a production bundle.

# Read DB connection details from the .env file (the source of truth — we do
# not inject .env via Docker env_file, to avoid baking a stale/empty APP_KEY
# into the container environment).
DB_HOST="$(sed -n 's/^DB_HOST=//p' .env | head -1)"
DB_PORT="$(sed -n 's/^DB_PORT=//p' .env | head -1)"
DB_PORT="${DB_PORT:-5432}"

# Wait for PostgreSQL to accept connections before touching the database.
if [ -n "${DB_HOST}" ]; then
    echo "[entrypoint] Waiting for database at ${DB_HOST}:${DB_PORT}..."
    until php -r "exit(@fsockopen('${DB_HOST}', ${DB_PORT}) ? 0 : 1);" 2>/dev/null; do
        sleep 1
    done

    if [ "${IS_PRIMARY}" = "true" ]; then
        echo "[entrypoint] Database is up. Running migrations..."
        php artisan migrate --force
    else
        echo "[entrypoint] Database is up."
    fi
fi

exec "$@"
