FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker-entrypoint.sh /usr/local/bin/sme-entrypoint.sh
RUN chmod +x /usr/local/bin/sme-entrypoint.sh

WORKDIR /var/www/html
COPY . /var/www/html

ENTRYPOINT ["/usr/local/bin/sme-entrypoint.sh"]
CMD ["php-fpm"]


