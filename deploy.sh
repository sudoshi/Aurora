#!/usr/bin/env bash
set -euo pipefail
umask 0022

DEPLOY_DIR="/home/smudoshi/Github/Aurora"
echo "=== Aurora V2 Deployment ==="

docker_php_running() {
    (cd "$DEPLOY_DIR" && docker compose ps --services --status running 2>/dev/null | grep -qx php)
}

run_artisan() {
    if docker_php_running; then
        (cd "$DEPLOY_DIR" && docker compose exec -T php php artisan "$@")
    else
        (cd "$DEPLOY_DIR/backend" && php artisan "$@")
    fi
}

normalize_backend_permissions() {
    if [ -d "$DEPLOY_DIR/backend/vendor" ]; then
        find "$DEPLOY_DIR/backend/vendor" -type d -exec chmod 755 {} + 2>/dev/null || true
        find "$DEPLOY_DIR/backend/vendor" -type f -exec chmod 644 {} + 2>/dev/null || true
        find "$DEPLOY_DIR/backend/vendor/bin" -type f -exec chmod 755 {} + 2>/dev/null || true
    fi

    if [ -d "$DEPLOY_DIR/backend/bootstrap/cache" ]; then
        find "$DEPLOY_DIR/backend/bootstrap/cache" -type d -exec chmod 775 {} + 2>/dev/null || true
        find "$DEPLOY_DIR/backend/bootstrap/cache" -type f -exec chmod 644 {} + 2>/dev/null || true
    fi

    if docker_php_running; then
        (cd "$DEPLOY_DIR" && docker compose exec -T php sh -lc '
            if [ -d /var/www/html/vendor ]; then
                find /var/www/html/vendor -type d -exec chmod 755 {} +
                find /var/www/html/vendor -type f -exec chmod 644 {} +
                find /var/www/html/vendor/bin -type f -exec chmod 755 {} + 2>/dev/null || true
            fi
            if [ -d /var/www/html/bootstrap/cache ]; then
                find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} +
                find /var/www/html/bootstrap/cache -type f -exec chmod 644 {} +
            fi
        ') || true
    fi
}

restart_runtime() {
    if docker_php_running; then
        (cd "$DEPLOY_DIR" && docker compose restart php nginx)
    else
        sudo systemctl reload php8.4-fpm 2>/dev/null || echo "PHP-FPM reload skipped (may need sudo)"
    fi
}

echo "[1/6] Pulling latest code..."
cd "$DEPLOY_DIR"
git pull origin "$(git branch --show-current)" || true

echo "[2/6] Installing backend dependencies..."
cd "$DEPLOY_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction --no-progress || composer install --no-interaction --no-progress
normalize_backend_permissions

echo "[3/6] Running migrations..."
run_artisan migrate --force

echo "[4/6] Clearing caches..."
run_artisan config:clear
run_artisan cache:clear
run_artisan route:clear
run_artisan view:clear
run_artisan config:cache
run_artisan route:cache
run_artisan view:cache
normalize_backend_permissions

echo "[5/6] Building frontend..."
cd "$DEPLOY_DIR/frontend"
npm ci 2>/dev/null || npm install
npm run build
rm -rf "$DEPLOY_DIR/backend/public/build"
mkdir -p "$DEPLOY_DIR/backend/public/build"
cp -a dist/. "$DEPLOY_DIR/backend/public/build/" 2>/dev/null || echo "Frontend build copy skipped (dist may not exist yet)"

echo "[6/6] Reloading runtime..."
restart_runtime

echo "=== Deployment complete ==="
echo "Visit: https://aurora.acumenus.net"
