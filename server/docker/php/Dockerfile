FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    mysql-dev \
    libzip-dev \
    unzip \
    git

RUN docker-php-ext-install pdo pdo_mysql zip pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS production

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .
RUN composer run-script post-autoload-dump || true

RUN chown -R www-data:www-data storage bootstrap/cache
