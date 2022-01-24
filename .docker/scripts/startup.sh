#!/usr/bin/env sh

set -e

cd /var/www/html

chown -R www-data:www-data /var/www/html/bootstrap/cache /var/www/html/storage

composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

php artisan passport:install && \
    php artisan key:generate --ansi && \
    php artisan storage:link && \
    php artisan config:cache && \
    php artisan cache:clear

php artisan migrate --seed

npm install

/usr/sbin/apache2ctl -D FOREGROUND
