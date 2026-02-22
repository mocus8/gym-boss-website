FROM php:8.2-fpm-alpine3.18

RUN apk update \
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
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./docker/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

EXPOSE 9000
CMD ["php-fpm"]
