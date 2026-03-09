#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="/home/smudoshi/Github/Aurora"
echo "=== Aurora V2 Deployment ==="

echo "[1/6] Pulling latest code..."
cd "$DEPLOY_DIR"
git pull origin "$(git branch --show-current)" || true

echo "[2/6] Installing backend dependencies..."
cd "$DEPLOY_DIR/backend"
composer install --no-dev --optimize-autoloader 2>/dev/null || composer install

echo "[3/6] Running migrations..."
php artisan migrate --force

echo "[4/6] Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[5/6] Building frontend..."
cd "$DEPLOY_DIR/frontend"
npm ci 2>/dev/null || npm install
npm run build
mkdir -p "$DEPLOY_DIR/backend/public/build"
cp -r dist/* "$DEPLOY_DIR/backend/public/build/" 2>/dev/null || echo "Frontend build copy skipped (dist may not exist yet)"

echo "[6/6] Reloading PHP-FPM..."
sudo systemctl reload php8.4-fpm 2>/dev/null || echo "PHP-FPM reload skipped (may need sudo)"

echo "=== Deployment complete ==="
echo "Visit: https://aurora.acumenus.net"
