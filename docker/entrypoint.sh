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
        # Forcer https QUOI QU'IL ARRIVE : APP_URL peut arriver vide ou en
        # http depuis Render, et c'est lui qui declenche le mixed-content.
        _APP_URL="${APP_URL:-https://heatalert.onrender.com}"
        case "$_APP_URL" in http://*) _APP_URL="https://${_APP_URL#http://}";; esac
        echo "APP_URL=${_APP_URL}"
        echo "FORCE_HTTPS=true"
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

# Genere une APP_KEY valide (32 octets base64) si absente de .env.
# Fait en PHP pur (artisan exige deja une cle valide pour booter).
if ! grep -q '^APP_KEY=base64:.' .env 2>/dev/null; then
    echo "[entrypoint] Generation APP_KEY..."
    KEY_B64=$(php -r 'echo base64_encode(random_bytes(32));')
    printf 'APP_KEY=base64:%s\n' "$KEY_B64" >> .env
fi
APP_KEY=$(grep '^APP_KEY=' .env 2>/dev/null | cut -d= -f2-)
export APP_KEY
# NB : pas de zz-appkey.conf -> Laravel lit APP_KEY depuis /var/www/.env
# directement ; un fichier pool fpm sans en-tete [www] faisait echouer FPM.
rm -f /usr/local/etc/php-fpm.d/zz-appkey.conf 2>/dev/null || true
chown www-data:www-data .env 2>/dev/null || true
chmod 644 .env 2>/dev/null || true

# Synchronise les cles critiques a chaque boot (le .env peut etre fige
# depuis un deploy precedent avec APP_URL en http ou APP_ENV=local).
_APP_URL="${APP_URL:-https://heatalert.onrender.com}"
case "$_APP_URL" in http://*) _APP_URL="https://${_APP_URL#http://}";; esac
for _kv in "APP_ENV=${APP_ENV:-production}" "APP_URL=${_APP_URL}" "FORCE_HTTPS=true"; do
    _k="${_kv%%=*}"; _v="${_kv#*=}"
    if grep -q "^${_k}=" .env 2>/dev/null; then
        sed -i "s|^${_k}=.*|${_k}=${_v}|" .env
    else
        printf '%s=%s\n' "$_k" "$_v" >> .env
    fi
done

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
