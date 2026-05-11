#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f vendor/autoload_runtime.php ]; then
  echo "[entrypoint] vendor/autoload_runtime.php missing - running composer install"
  composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts
fi

php -S 0.0.0.0:${PORT:-8000} -t public
