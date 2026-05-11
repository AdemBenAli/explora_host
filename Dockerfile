FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip curl git libonig-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath zip mbstring

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --optimize-autoloader --no-scripts --no-interaction

ENV APP_ENV=prod

RUN php bin/console cache:clear --env=prod || true

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]