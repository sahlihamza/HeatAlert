#!/bin/sh
set -e

cd /var/www

PORT="${PORT:-8080}"
export PORT

echo "[entrypoint] HeatAlert démarre sur le port ${PORT} (env: ${APP_ENV:-production})"

# Permissions écritures (utile quand /var/www est monté en bind mount)
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Recrée le symlink public/storage (exclu du build car cassé sous Windows)
if [ ! -e public/storage ]; then
    rm -f public/storage 2>/dev/null || true
    ln -s ../storage/app/public public/storage
fi

# Génération d'une clé si aucune n'est fournie (démo/preview uniquement)
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] Aucun APP_KEY fourni -> génération d'une clé éphémère"
    php artisan key:generate --force >/dev/null 2>&1 || true
fi

# Migrations au démarrage (Render n'a pas accès à "php artisan" sur le host)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "[entrypoint] php artisan migrate --force"
    if ! php artisan migrate --force; then
        echo "[entrypoint] ATTENTION: migration échouée, tentative de config:clear puis retry"
        php artisan config:clear >/dev/null 2>&1 || true
        sleep 5
        php artisan migrate --force || echo "[entrypoint] migration en échec (DB injoignable?), démarrage poursuivi"
    fi
else
    # Sans APP_KEY (donc sans .env exploitable), inutile de garder un cache de config périmé
    php artisan config:clear >/dev/null 2>&1 || true
fi

# php-fpm en mode démon (PID écrit dans /usr/local/var/run)
php-fpm -D

# nginx : génère la conf depuis le template (substitution de $PORT)
for t in /etc/nginx/templates/*.template; do
    [ -f "$t" ] || continue
    out="/etc/nginx/conf.d/$(basename "$t" .template)"
    envsubst '${$PORT}' < "$t" > "$out"
    echo "[entrypoint] nginx conf -> $out (port $PORT)"
done

# nginx en avant-plan -> reçoit SIGTERM à l'arrêt du conteneur
nginx -g 'daemon off;'
