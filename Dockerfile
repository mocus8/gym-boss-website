FROM php:8.2-fpm-alpine3.18

RUN apk update \
    && apk add --no-cache --virtual .build-deps \
        build-base \
        autoconf \
        gcc \
        g++ \
        make \
    && apk add --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        zip \
        unzip \
        git \
        libpq-dev \
        zlib-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mysqli \
        gd \
        pcntl \
        opcache \
    && apk del .build-deps \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /tmp/php-sessions /var/log \
    && chown www-data:www-data /tmp/php-sessions /var/log \
    && chmod 700 /tmp/php-sessions

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 9000
CMD ["php-fpm"]
