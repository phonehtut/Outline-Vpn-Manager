FROM php:8.5-cli-bookworm AS composer-dependencies

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl pdo_pgsql pdo_sqlite pcntl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

FROM composer-dependencies AS frontend-assets

COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && npm ci \
    && npm run build

FROM nginx:stable-alpine-slim AS web

COPY --from=frontend-assets /var/www/html/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

FROM php:8.5-fpm-bookworm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl opcache pdo_pgsql pdo_sqlite pcntl zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=composer-dependencies --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=frontend-assets --chown=www-data:www-data /var/www/html/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

CMD ["php-fpm"]
