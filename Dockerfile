# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: PHP dependencies (composer)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

# ---------------------------------------------------------------------------
# Stage 2: Frontend assets (npm / Vite)
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts --no-audit --no-fund

COPY resources ./resources
COPY vite.config.js ./
RUN NODE_ENV=production npm run build

# ---------------------------------------------------------------------------
# Stage 3: Runtime image — PHP 8.4 required (composer.lock pins Symfony to >=8.4.1)
# ---------------------------------------------------------------------------
FROM php:8.4-cli

# System deps + PHP extensions required by Laravel.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libonig-dev \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        zip \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer binary.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source.
COPY . .

# Copy built dependencies and assets from the earlier stages.
COPY --from=vendor  /app/vendor  ./vendor
COPY --from=assets  /app/public/build ./public/build

# Regenerate the Composer autoloader in the app context so package discovery
# (e.g. Laravel's package:discover) runs against the final source tree.
RUN composer dump-autoload --no-dev --optimize --no-interaction

# Make framework directories writable and create a persistent data volume.
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

# Default configuration overridable at run time (see README "Docker" section).
ENV APP_NAME=Eagle \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/eagle.sqlite \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log

# Persistent SQLite + uploads live on this volume.
VOLUME ["/data"]

EXPOSE 8080

COPY --chown=www-data:www-data docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && mkdir -p /data && chown www-data:www-data /data

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost:8080/ > /dev/null || exit 1

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]