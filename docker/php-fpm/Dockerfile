FROM php:8.3-fpm-alpine AS development

# Useful PHP extension installer image, copy binary into your container
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

COPY . .

RUN apk update \
    && apk add vim bash make

RUN echo 'alias ll="ls -al"' >> ~/.bashrc

COPY ./docker/php-fpm/xdebug.ini $PHP_INI_DIR/conf.d/

RUN set -eux; \
    install-php-extensions \
    	xdebug \
        simplexml \
        http \
        pdo_mysql \
    ;
