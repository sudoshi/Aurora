#!/usr/bin/env bash
set -euo pipefail
umask 0022

DEPLOY_DIR="/home/smudoshi/Github/Aurora"
echo "=== Aurora V2 Deployment ==="

compose() {
    (cd "$DEPLOY_DIR" && docker compose -f docker-compose.yml "$@")
}

docker_service_running() {
    local service="$1"
    compose ps --services --status running 2>/dev/null | grep -qx "$service"
}

docker_php_running() {
    docker_service_running php
}

run_artisan() {
    if docker_php_running; then
        compose exec -T php php artisan "$@"
    else
        (cd "$DEPLOY_DIR/backend" && php artisan "$@")
    fi
}

normalize_backend_permissions() {
    local readable_paths=(
        app
        bootstrap
        config
        database
        public
        resources
        routes
    )
    local path

    for path in "${readable_paths[@]}"; do
        if [ -d "$DEPLOY_DIR/backend/$path" ]; then
            find "$DEPLOY_DIR/backend/$path" -type d -exec chmod 755 {} + 2>/dev/null || true
            find "$DEPLOY_DIR/backend/$path" -type f -exec chmod 644 {} + 2>/dev/null || true
        fi
    done

    if [ -f "$DEPLOY_DIR/backend/artisan" ]; then
        chmod 755 "$DEPLOY_DIR/backend/artisan" 2>/dev/null || true
    fi

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
        compose exec -T php sh -lc '
            for path in app bootstrap config database public resources routes; do
                if [ -d "/var/www/html/$path" ]; then
                    find "/var/www/html/$path" -type d -exec chmod 755 {} +
                    find "/var/www/html/$path" -type f -exec chmod 644 {} +
                fi
            done
            if [ -f /var/www/html/artisan ]; then
                chmod 755 /var/www/html/artisan
            fi
            if [ -d /var/www/html/vendor ]; then
                find /var/www/html/vendor -type d -exec chmod 755 {} +
                find /var/www/html/vendor -type f -exec chmod 644 {} +
                find /var/www/html/vendor/bin -type f -exec chmod 755 {} + 2>/dev/null || true
            fi
            if [ -d /var/www/html/bootstrap/cache ]; then
                find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} +
                find /var/www/html/bootstrap/cache -type f -exec chmod 644 {} +
            fi
        ' || true
    fi
}

restart_runtime() {
    if docker_php_running; then
        compose restart php nginx
    else
        sudo systemctl reload php8.4-fpm 2>/dev/null || echo "PHP-FPM reload skipped (may need sudo)"
    fi
}

restart_reverb() {
    # Restart the realtime WebSocket server if installed (see
    # deploy/aurora-reverb.service + docs/deployment/realtime-reverb.md).
    if docker_service_running reverb; then
        compose restart reverb
    elif systemctl list-unit-files 2>/dev/null | grep -q '^aurora-reverb\.service'; then
        sudo systemctl restart aurora-reverb 2>/dev/null \
            || echo "Reverb restart skipped (may need sudo)"
    else
        echo "Reverb not installed; skipping (realtime disabled)."
    fi
}

stop_dev_frontend_if_running() {
    if docker ps --format '{{.Names}}' | grep -qx 'aurora-node'; then
        echo "Stopping dev Vite service for production static serving..."
        compose --profile dev stop node >/dev/null 2>&1 || docker stop aurora-node >/dev/null
    fi
}

verify_static_frontend() {
    local base_url="http://127.0.0.1:${NGINX_PORT:-8085}"
    local index_file
    local route_file
    index_file="$(mktemp)"
    route_file="$(mktemp)"

    for _ in $(seq 1 20); do
        if curl -fsSL "$base_url/" -o "$index_file"; then
            break
        fi
        sleep 1
    done

    if [ ! -s "$index_file" ]; then
        echo "Static frontend smoke failed: $base_url/ returned no HTML" >&2
        rm -f "$index_file" "$route_file"
        exit 1
    fi

    if grep -Eq '(@vite/client|/src/main|react-refresh|vite\.client)' "$index_file"; then
        echo "Static frontend smoke failed: production HTML still contains Vite dev markers" >&2
        rm -f "$index_file" "$route_file"
        exit 1
    fi

    if ! grep -q '/build/assets/' "$index_file"; then
        echo "Static frontend smoke failed: production HTML does not reference built /build/assets files" >&2
        rm -f "$index_file" "$route_file"
        exit 1
    fi

    curl -fsSL "$base_url/imaging" -o "$route_file"
    if ! grep -q '/build/assets/' "$route_file"; then
        echo "Static frontend smoke failed: SPA fallback did not serve the built frontend" >&2
        rm -f "$index_file" "$route_file"
        exit 1
    fi

    rm -f "$index_file" "$route_file"
    echo "Static frontend smoke passed: built assets are served without Vite dev markers."
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
restart_reverb
stop_dev_frontend_if_running
verify_static_frontend

echo "=== Deployment complete ==="
echo "Visit: https://aurora.acumenus.net"
