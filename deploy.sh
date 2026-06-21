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

# Composer must run where the runtime lives. In prod that's the php CONTAINER
# (PHP 8.4, root-owned vendor); running it on the host (different PHP, no write
# access to root-owned vendor/) corrupts the autoloader. Mirror run_artisan.
run_composer() {
    if docker_php_running; then
        compose exec -T php sh -c "cd /var/www/html && composer $*"
    else
        (cd "$DEPLOY_DIR/backend" && composer "$@")
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
        # `up -d` (NOT `restart`): docker `restart` does NOT reload env_file, so
        # backend/.env changes would be ignored and config:cache would bake stale
        # values. `up -d` recreates a container only when its config/env changed,
        # otherwise it's a no-op.
        compose up -d php nginx
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

# ---- Argument parsing -------------------------------------------------------
# Scoped + non-destructive by default. Database migrations (migrate --force) are
# DESTRUCTIVE-CAPABLE and therefore only run when --db is passed explicitly —
# never implied by a bare `./deploy.sh` or by --all.
DO_PHP=false
DO_FRONTEND=false
DO_DB=false
DO_REVERB=false
DO_PULL=true
EXPLICIT_SCOPE=false

usage() {
    cat <<'USAGE'
Aurora deploy — scoped, non-destructive by default.

Usage: ./deploy.sh [scopes...] [options]

Scopes (combine freely; default with no args = --php --frontend, i.e. a full
app deploy WITHOUT database migrations):
  --all         Backend + frontend (no migrations). Same as no arguments.
  --php         Backend: composer install, clear/rebuild caches, restart php+nginx.
  --frontend    Frontend: npm build, publish to public/build, restart nginx.
  --db          Run pending migrations (migrate --force). DESTRUCTIVE-CAPABLE —
                only runs when this flag is given explicitly. Never implied by
                --all or a bare ./deploy.sh.
  --reverb      Restart the realtime Reverb websocket server.

Options:
  --no-pull     Skip 'git pull' (deploy the working tree as-is).
  -h, --help    Show this help.

Examples:
  ./deploy.sh                 # full app deploy, NO migrations
  ./deploy.sh --frontend      # rebuild just the SPA
  ./deploy.sh --php           # re-cache config after editing backend/.env
  ./deploy.sh --db            # apply pending migrations (explicit, on purpose)
  ./deploy.sh --php --db      # backend + migrations
USAGE
}

while [ $# -gt 0 ]; do
    case "$1" in
        --all)      DO_PHP=true; DO_FRONTEND=true; EXPLICIT_SCOPE=true ;;
        --php)      DO_PHP=true; EXPLICIT_SCOPE=true ;;
        --frontend) DO_FRONTEND=true; EXPLICIT_SCOPE=true ;;
        --db)       DO_DB=true; EXPLICIT_SCOPE=true ;;
        --reverb)   DO_REVERB=true; EXPLICIT_SCOPE=true ;;
        --no-pull)  DO_PULL=false ;;
        -h|--help)  usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage; exit 2 ;;
    esac
    shift
done

# Default (no scope flags): full app deploy WITHOUT migrations.
if [ "$EXPLICIT_SCOPE" = false ]; then
    DO_PHP=true
    DO_FRONTEND=true
fi

# ---- Execution --------------------------------------------------------------
if [ "$DO_PULL" = true ]; then
    echo "[pull] Pulling latest code..."
    cd "$DEPLOY_DIR"
    git pull origin "$(git branch --show-current)" || true
fi

if [ "$DO_PHP" = true ]; then
    echo "[php] Installing backend dependencies (in container)..."
    # NOTE: full install (no --no-dev). config/scribe.php imports knuckleswtf/scribe
    # classes at boot, so pruning dev deps breaks the app. To enable --no-dev,
    # move knuckleswtf/scribe to "require" (and guard its config) first.
    run_composer install --optimize-autoloader --no-interaction --no-progress
    normalize_backend_permissions
fi

if [ "$DO_DB" = true ]; then
    echo "[db] Applying migrations (explicit --db)..."
    echo "     Pending before run:"
    run_artisan migrate:status 2>/dev/null | grep -i 'pending' || echo "     (none pending)"
    run_artisan migrate --force
else
    echo "[db] Skipped — migrations only run with explicit --db."
fi

if [ "$DO_PHP" = true ]; then
    echo "[php] Clearing + rebuilding caches..."
    run_artisan config:clear
    run_artisan cache:clear
    run_artisan route:clear
    run_artisan view:clear
    run_artisan config:cache
    run_artisan route:cache
    run_artisan view:cache
    normalize_backend_permissions
fi

if [ "$DO_FRONTEND" = true ]; then
    echo "[frontend] Building frontend..."
    cd "$DEPLOY_DIR/frontend"
    npm ci 2>/dev/null || npm install
    npm run build
    rm -rf "$DEPLOY_DIR/backend/public/build"
    mkdir -p "$DEPLOY_DIR/backend/public/build"
    cp -a dist/. "$DEPLOY_DIR/backend/public/build/" 2>/dev/null || echo "Frontend build copy skipped (dist may not exist yet)"
fi

echo "[reload] Reloading runtime..."
if [ "$DO_PHP" = true ] || [ "$DO_FRONTEND" = true ]; then
    restart_runtime
    restart_reverb            # self-guards: only restarts if the reverb service exists
elif [ "$DO_REVERB" = true ]; then
    restart_reverb
fi
if [ "$DO_FRONTEND" = true ]; then
    stop_dev_frontend_if_running
    verify_static_frontend
fi

echo "=== Deployment complete ==="
echo "Visit: https://aurora.acumenus.net"
