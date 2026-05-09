FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    postgresql-client \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    git \
    unzip \
    curl

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    gd \
    mbstring \
    opcache \
    exif \
    bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/sh -D www

USER www

CMD ["php-fpm"]
