FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip curl git libonig-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath zip mbstring \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor

ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN php bin/console cache:clear --env=prod || true

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]