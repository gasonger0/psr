FROM php:8.2.0-fpm

ARG FILE_UID=1000
ARG FILE_GID=1000
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/


RUN groupmod -g ${FILE_GID} www-data \
    && usermod -d /var/www/html -u ${FILE_UID} -g www-data -s /bin/bash www-data \
    && chown -R www-data:www-data /var/www/html

RUN docker-php-ext-install mysqli pdo_mysql
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd imagick exif zip

WORKDIR /var/www/html


USER www-data