#!/bin/sh
set -e

cd /var/www/html

# Install composer deps if vendor is missing (first run after volume mount)
if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Clear caches for dev (in case production caches were left)
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

exec php-fpm
