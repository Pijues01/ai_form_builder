# syntax=docker/dockerfile:1

# ---------- Stage 1: Composer dependencies ----------
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
# Platform reqs are ignored here because the runtime stage installs the extensions.
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    --optimize-autoloader --no-scripts --ignore-platform-reqs

# ---------- Stage 2: Frontend assets ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

# ---------- Stage 3: Runtime ----------
FROM php:8.3-fpm-alpine

# System + PHP extensions (mysql, zip/xml for Word/Excel import, gd/bcmath for PhpSpreadsheet, opcache)
RUN apk add --no-cache \
        nginx supervisor curl \
        icu-dev libzip-dev oniguruma-dev libxml2-dev \
        freetype-dev libpng-dev libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql pcntl zip bcmath exif \
    && docker-php-ext-install -j"$(nproc)" gd \
    && docker-php-ext-enable opcache

# PHP config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /var/www/html

# Application code
COPY --from=composer /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY . .

# Storage permissions + clean caches
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R ug+rw storage bootstrap/cache

# Nginx + supervisor configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
