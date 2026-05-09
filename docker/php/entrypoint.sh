#!/bin/sh
set -e

cd /var/www/html

if [ ! -f ".env" ]; then
    cp .env.example .env
    sed -i 's/^DB_HOST=127\.0\.0\.1/DB_HOST=database/' .env
    sed -i 's/^DB_DATABASE=$/DB_DATABASE=zentra/' .env
    sed -i 's/^DB_USERNAME=$/DB_USERNAME=zentra/' .env
    sed -i 's/^DB_PASSWORD=$/DB_PASSWORD=secret/' .env
fi

if [ ! -d "vendor" ]; then
    echo "[Zentra] Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -z "$(grep '^APP_KEY=base64:' .env)" ]; then
    php artisan key:generate --force
fi

exec php-fpm
