FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    icu-dev \
    libzip-dev \
    mysql-client \
    oniguruma-dev \
    unzip \
    zip \
    && docker-php-ext-install \
    bcmath \
    intl \
    mbstring \
    pdo_mysql \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/tms-entrypoint

RUN chmod +x /usr/local/bin/tms-entrypoint

ENTRYPOINT ["tms-entrypoint"]
CMD ["php-fpm"]
