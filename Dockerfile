ARG PHP_VERSION=7.4
FROM php:${PHP_VERSION}-cli

LABEL maintainer="Tomáš Lembacher <tomas.lembacher@seznam.cz>"

RUN apt-get update

RUN apt-get install -y \
		git \
		libzip-dev \
		zlib1g-dev \
        zip

RUN pecl install xdebug-2.9.5 \
	&& echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
	&& echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
	&& echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini \
	&& echo "xdebug.remote_connect_back=on" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN curl -sS https://getcomposer.org/installer | tee composer-setup.php \
	&& php composer-setup.php --1 --install-dir=/usr/local/bin --filename=composer \
	&& rm composer-setup.php

WORKDIR /usr/src
