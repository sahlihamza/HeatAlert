#!/bin/sh
set -e

cd /var/www

PORT="${PORT:-8080}"
export PORT

echo "[entrypoint] HeatAlert demarre sur le port ${PORT} (env: ${APP_ENV:-production})"

# Permissions ecritures
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Recree le symlink public/storage
if [ ! -e public/storage ]; then
    rm -f public/storage 2>/dev/null || true
    ln -s ../storage/app/public public/storage
fi

# Prepare un .env minimal si absent
if [ ! -f .env ]; then
    echo "[entrypoint] Aucun .env detecte -> generation dun .env minimal"
    {
        echo "APP_NAME=HeatAlert"
        echo "APP_ENV=${APP_ENV:-production}"
        echo "APP_DEBUG=${APP_DEBUG:-false}"
        echo "APP_URL=${APP_URL:-https://heatalert-web.onrender.com}"
        echo "LOG_CHANNEL=stderr"
        echo "LOG_LEVEL=info"
        echo "DB_CONNECTION=${DB_CONNECTION:-mysql}"
        echo "DB_HOST=${DB_HOST:-127.0.0.1}"
        echo "DB_PORT=${DB_PORT:-3306}"
        echo "DB_DATABASE=${DB_DATABASE:-heatalert}"
        echo "DB_USERNAME=${DB_USERNAME:-heatalert}"
        echo "DB_PASSWORD=${DB_PASSWORD:-}"
        echo "SESSION_DRIVER=${SESSION_DRIVER:-database}"
        echo "SESSION_LIFETIME=120"
        echo "CACHE_STORE=database"
        echo "QUEUE_CONNECTION=database"
        echo "BROADCAST_CONNECTION=log"
        echo "FILESYSTEM_DISK=local"
        echo "MAIL_MAILER=${MAIL_MAILER:-log}"
    } > .env
fi

# Genere une APP_KEY valide (32 octets base64) si absente de .env ET de lenv.
# On le fait MANUELLEMENT (pas via artisan) pour eviter les echecs silencieux.
if ! grep -q '^APP_KEY=base64:.' .env 2>/dev/null && [ -z "$APP_KEY" ]; then
    echo "[entrypoint] Generation manuelle dune APP_KEY (32 octets base64)..."
    KEY_B64=$(php -r "echo base64_encode(random_bytes(32));")
    KEY_FULL="base64:${KEY_B64}"
    echo "APP_KEY=${KEY_FULL}" >> .env
    # Lexporter dans lenv php-fpm (qui herite de lenv du shell parent)
    export APP_KEY="${KEY_FULL}"
    echo "[entrypoint] APP_KEY generee et exportee (prefixe: ${KEY_B64})"
fi

chown www-data:www-data .env 2>/dev/null || true
chmod 644 .env 2>/dev/null || true

# Diagnostics
echo "[entrypoint] APP_KEY presente : $(grep -q '^APP_KEY=base64:.' .env 2>/dev/null && echo OUI || echo NON)"
echo "[entrypoint] DB_CONNECTION    : ${DB_CONNECTION:-non defini (default sqlite !)}"
echo "[entrypoint] DB_HOST          : ${DB_HOST:-non defini}"
echo "[entrypoint] DB_PORT          : ${DB_PORT:-non defini}"
echo "[entrypoint] SESSION_DRIVER   : ${SESSION_DRIVER:-non defini (default database)}"

# Test de connexion DB (non bloquant)
if [ "${DB_CONNECTION:-sqlite}" = "mysql" ] && [ -n "$DB_HOST" ]; then
    echo "[entrypoint] Test connexion MySQL ${DB_HOST}:${DB_PORT:-3306}..."
    php artisan db:monitor --timeout=5 2>/dev/null \
        && echo "[entrypoint] MySQL joignable" \
        || echo "[entrypoint] MySQL injoignable (verifiez DB_HOST/DB_PORT dans Render)"
fi

# Migrations
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "[entrypoint] php artisan migrate --force"
    if ! php artisan migrate --force; then
        echo "[entrypoint] ATTENTION: migration echouee, tentative de config:clear puis retry"
        php artisan config:clear >/dev/null 2>&1 || true
        sleep 5
        php artisan migrate --force || echo "[entrypoint] migration en echec (DB injoignable?), demarrage poursuivi"
    fi
else
    php artisan config:clear >/dev/null 2>&1 || true
fi

# php-fpm en mode demon (herite de lenv du shell, y compris APP_KEY exportee)
php-fpm -D

# nginx : genere la conf depuis le template (substitution de PORT)
for t in /etc/nginx/templates/*.template; do
    [ -f "$t" ] || continue
    out="/etc/nginx/conf.d/$(basename "$t" .template)"
    envsubst '${PORT}' < "$t" > "$out"
    echo "[entrypoint] nginx conf -> $out (port $PORT)"
done

# nginx en avant-plan
nginx -g 'daemon off;'