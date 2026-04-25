FROM php:8.4.16-fpm-alpine3.23

RUN addgroup -g 1000 webserver && adduser -G webserver -g webserver -s /bin/sh -D webserver

RUN apk add --no-cache \
        bash \
        nano \
        git \
        freetype \
        libpng \
        libzip \
        libwebp \
        icu \
        gmp \
        patch \
        libjpeg-turbo \
        mysql-client

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libpng-dev \
        libzip-dev \
        libwebp-dev \
        icu-dev \
        gmp-dev \
        libjpeg-turbo-dev \
        zlib-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) \
        bcmath \
        bz2 \
        exif \
        gd \
        gmp \
        zip \
        intl \
        pdo_mysql \
        opcache && \
    pecl install --onlyreqdeps --configureoptions='enable-redis-igbinary="yes" enable-redis-lzf="yes"' \
        igbinary \
        redis && \
    docker-php-ext-enable \
        igbinary \
        redis && \
    apk del --no-network .build-deps

RUN curl -L https://raw.githubusercontent.com/johanhaleby/bash-templater/master/templater.sh -o /usr/local/bin/templater && chmod +x /usr/local/bin/templater

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN rm /usr/local/etc/php-fpm.d/zz-docker.conf