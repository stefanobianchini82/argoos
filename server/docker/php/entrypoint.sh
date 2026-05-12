#!/bin/sh
set -e

mkdir -p /var/www/html/public/build
cp -rf /var/www/html-assets/public/build/. /var/www/html/public/build/

exec docker-php-entrypoint "$@"
