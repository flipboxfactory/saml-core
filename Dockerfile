ARG PHP_IMAGE=flipbox/php:72-apache
FROM ${PHP_IMAGE} AS composer

COPY ./composer.json /var/www/html/
COPY ./src ./src

RUN composer install --no-interaction --prefer-dist --no-scripts && \
    ls -l && pwd

