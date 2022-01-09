FROM php:8.1-cli
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && curl -o /usr/local/bin/composer https://getcomposer.org/composer-stable.phar \
    && chmod ugo+rx /usr/local/bin/composer

ENV XDEBUG_MODE=coverage
