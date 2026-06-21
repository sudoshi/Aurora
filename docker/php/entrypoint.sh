#!/bin/sh
set -e

cd /var/www/html

# Install composer deps if vendor is missing (first run after volume mount)
if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Ensure php-fpm (www-data) can read the application tree even when the
# bind-mounted host files were created under a restrictive umask (e.g. a dev
# session writing 0660 files). Without this, a single unreadable file like
# bootstrap/app.php takes the whole API down with "Permission denied". This
# mirrors deploy.sh: world-readable files, traversable dirs. o+rX only ADDS
# read/traverse bits (capital X = dirs/already-exec only), never removes
# write, so storage/ and bootstrap/cache stay writable. Idempotent & cheap
# (skips vendor/). Runs as root here, before php-fpm drops to www-data.
for p in app bootstrap config database public resources routes artisan composer.json composer.lock; do
    [ -e "$p" ] && chmod -R o+rX "$p" 2>/dev/null || true
done

# If a command was provided (compose `command:` / CMD — e.g. the reverb and
# queue sidecars run `php artisan reverb:start` / `queue:work`), exec it instead
# of php-fpm. It arrives as "$@" because ENTRYPOINT is this wrapper. Without
# this, every sidecar silently ran php-fpm. Sidecars skip the cache-clear below
# so they never wipe the web tier's shared bootstrap/cache.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# Web tier (php-fpm): clear any stale dev caches that may have been baked into
# the bind-mounted tree, then serve.
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

exec php-fpm
