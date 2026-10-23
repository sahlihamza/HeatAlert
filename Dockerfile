FROM php:8.2-fpm AS app

WORKDIR /var/www

# Nginx + outils de santé/entrée dans la même image que PHP-FPM
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    curl \
    gettext-base \
    python3 \
    python3-venv \
    libgomp1 \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    && sed -i 's/^pm = dynamic$/pm = ondemand/; s/^pm.max_children = 5$/pm.max_children = 2/' /usr/local/etc/php-fpm.d/www.conf \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /var/www/html

# Keep the ML API in this Render image so Laravel can reach it on localhost.
# Bootstrap model artifacts are generated reproducibly during the image build.
COPY ml-service /opt/heatalert-ml
RUN python3 -m venv /opt/heatalert-ml/.venv \
    && /opt/heatalert-ml/.venv/bin/pip install --no-cache-dir -r /opt/heatalert-ml/requirements-runtime.txt \
    && cd /opt/heatalert-ml \
    && /opt/heatalert-ml/.venv/bin/python bootstrap_synthetic_models.py \
    && chown -R www-data:www-data /opt/heatalert-ml/artifacts

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Compile Vite assets as part of the same production image build.
FROM node:22-alpine AS frontend

WORKDIR /var/www

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=app /var/www/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM app AS runtime

COPY --from=frontend --chown=www-data:www-data /var/www/public/build /var/www/public/build

# Entrypoint et template nginx hors de /var/www : survivent au bind mount de docker-compose
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY nginx/default.conf /etc/nginx/templates/default.conf.template

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf

# Écritures Laravel (sessions, cache, logs, vues compilées) + symlink storage public
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache \
    && rm -f public/storage \
    && ln -s ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

ENV PORT=8080
ENV HEATALERT_ML_URL=http://127.0.0.1:8001
ENV OMP_NUM_THREADS=1
ENV OPENBLAS_NUM_THREADS=1
ENV MKL_NUM_THREADS=1
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8001/ready && curl -fsS "http://127.0.0.1:${PORT}/up" || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
