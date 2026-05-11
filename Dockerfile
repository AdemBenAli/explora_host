FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip curl git \
    && docker-php-ext-install pdo pdo_mysql bcmath zip mbstring \
    && a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --optimize-autoloader --no-scripts --no-interaction

ENV APP_ENV=prod

RUN php bin/console cache:clear --env=prod || true