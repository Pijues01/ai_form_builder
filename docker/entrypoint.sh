#!/bin/sh
set -e

# Ensure storage dirs exist (Laravel 11 file-cache needs cache/data)
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs

# Auto-map Railway's managed MySQL variables (MYSQLHOST, MYSQLPORT, ...) to Laravel's DB_*
if [ -n "$MYSQLHOST" ]; then
    echo ">> Detected Railway MySQL at $MYSQLHOST"
    export DB_CONNECTION="${DB_CONNECTION:-mysql}"
    export DB_HOST="${DB_HOST:-$MYSQLHOST}"
    export DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
    export DB_DATABASE="${DB_DATABASE:-$MYSQLDATABASE}"
    export DB_USERNAME="${DB_USERNAME:-$MYSQLUSER}"
    export DB_PASSWORD="${DB_PASSWORD:-$MYSQLPASSWORD}"
fi

# Generate app key if not set (ephemeral containers; no .env file in image)
if [ -z "$APP_KEY" ]; then
    echo ">> Generating application key..."
    export APP_KEY="$(php artisan key:generate --force --no-interaction --show)"
fi

# Wait for the database before migrating
if [ -n "$DB_HOST" ]; then
    echo ">> Waiting for database at $DB_HOST:$DB_PORT ..."
    i=0
    until php -r "
        \$conn = @fsockopen(getenv('DB_HOST'), getenv('DB_PORT') ?: 3306, \$errno, \$errstr, 2);
        if (\$conn) { fclose(\$conn); exit(0); }
        exit(1);
    " 2>/dev/null; do
        i=$((i + 1))
        if [ "$i" -ge 30 ]; then
            echo "!! Database not reachable after 30s — continuing anyway."
            break
        fi
        sleep 1
    done
fi

# Apply migrations (idempotent) and seed the demo user
echo ">> Running migrations..."
php artisan migrate --force --no-interaction

if [ "$APP_SEED" = "true" ] || [ "$APP_SEED" = "1" ]; then
    echo ">> Seeding demo data..."
    php artisan db:seed --force --no-interaction || true
fi

# Persist session/cache/config caches (best-effort)
php artisan package:discover --ansi --no-interaction 2>/dev/null || true
php artisan config:cache --no-interaction 2>/dev/null || true
php artisan route:cache --no-interaction 2>/dev/null || true
php artisan view:cache --no-interaction 2>/dev/null || true

# Link public/storage -> storage/app/public so uploaded files are served
php artisan storage:link --no-interaction 2>/dev/null || true

# Point nginx at the platform-injected port (Railway sets $PORT, defaults to 80 locally)
nginx_port="${PORT:-80}"
sed -i "s/listen 80;/listen ${nginx_port};/" /etc/nginx/http.d/default.conf

echo ">> Starting supervisord..."
exec "$@"
