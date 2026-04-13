FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip \
    && pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html
RUN composer install --no-interaction --prefer-dist --no-scripts || true

CMD ["php-fpm"]


