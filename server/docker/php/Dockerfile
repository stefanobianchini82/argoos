FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    mysql-dev \
    libzip-dev \
    unzip \
    git

RUN docker-php-ext-install pdo pdo_mysql zip pcntl

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS composer-deps

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

FROM node:22-alpine AS node-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources/ ./resources/
COPY --from=composer-deps /var/www/html/vendor/livewire/livewire/dist/ ./vendor/livewire/livewire/dist/

RUN npm run build

FROM base AS production

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .
RUN composer run-script post-autoload-dump || true

RUN chown -R www-data:www-data storage bootstrap/cache

COPY --from=node-build /app/public/build /var/www/html-assets/public/build

COPY docker/php/entrypoint.sh /usr/local/bin/argoos-entrypoint
RUN chmod +x /usr/local/bin/argoos-entrypoint

ENTRYPOINT ["/usr/local/bin/argoos-entrypoint"]
CMD ["php-fpm"]
