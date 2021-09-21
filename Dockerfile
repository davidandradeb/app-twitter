FROM php:7.3-apache

RUN apt-get update && apt-get install -y libssl-dev pkg-config unzip \
    && rm -r /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions gd intl pdo_mysql soap zip gmp

WORKDIR /var/www/app

COPY . .
COPY docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf
COPY docker/app.conf /etc/apache2/conf-enabled/z-app.conf
COPY docker/app.ini $PHP_INI_DIR/conf.d/app.ini

COPY --from=composer:1.10 /usr/bin/composer /usr/bin/composer
RUN composer install --prefer-dist --no-progress --no-suggest --no-ansi --no-interaction

RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
RUN mkdir -p bootstrap/cache && chown -R www-data:www-data bootstrap/cache storage
RUN a2enmod headers rewrite
