# Dockerized Dev Environment — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the Docker setup so `docker compose up` serves the entire Aurora app (nginx + PHP-FPM + Vite + Redis) using host Postgres, with Apache reverse-proxying for HTTPS at aurora.acumenus.net.

**Architecture:** Nginx routes requests to PHP-FPM (API) or Vite dev server (frontend). PHP connects to host Postgres via `host.docker.internal`. Apache becomes a thin HTTPS reverse proxy. No Docker Postgres.

**Tech Stack:** Docker Compose, nginx, PHP-FPM 8.4, Node 22, Vite 6, Redis 7, Apache (reverse proxy only)

**Spec:** `docs/superpowers/specs/2026-03-24-dockerized-dev-environment-design.md`

---

## File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `docker/php/entrypoint.sh` | Create | Composer install + cache clear + exec php-fpm |
| `docker/php/Dockerfile` | Modify | Add entrypoint script, adjust for dev volume mount |
| `docker/nginx/default.conf` | Rewrite | Multi-upstream routing: PHP, Vite, Orthanc, static |
| `docker-compose.yml` | Rewrite | Remove postgres, fix volumes, activate node, add extra_hosts |
| `frontend/vite.config.ts` | Modify | host 0.0.0.0, port 5173, conditional base, remove proxy |
| `.env.example` | Create | Comprehensive Docker dev defaults |
| Apache vhost | Modify | Replace direct-serve with ProxyPass to Docker |

---

## Task 1: Create PHP Entrypoint Script

**Files:**
- Create: `docker/php/entrypoint.sh`
- Modify: `docker/php/Dockerfile`

- [ ] **Step 1: Create the entrypoint script**

Create `docker/php/entrypoint.sh`:

```bash
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
```

- [ ] **Step 2: Update the Dockerfile**

Replace the contents of `docker/php/Dockerfile` with:

```dockerfile
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    fcgi \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    opcache

# Install php-fpm-healthcheck
RUN wget -O /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Enable status page for healthcheck
RUN echo "pm.status_path = /status" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose PHP-FPM port
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
```

Key changes from original:
- Removed `COPY backend/composer.json ...`, `COPY backend/ .`, `RUN composer install`, `RUN composer dump-autoload`, `RUN chown` — all handled at runtime by entrypoint + volume mount
- Added `COPY` and `ENTRYPOINT` for the entrypoint script
- Removed `CMD ["php-fpm"]` — entrypoint `exec php-fpm` handles this

- [ ] **Step 3: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add docker/php/entrypoint.sh docker/php/Dockerfile
git commit -m "feat(docker): add PHP entrypoint script, simplify Dockerfile for dev"
```

---

## Task 2: Rewrite Nginx Configuration

**Files:**
- Rewrite: `docker/nginx/default.conf`

- [ ] **Step 1: Replace the nginx config**

Replace the entire contents of `docker/nginx/default.conf` with:

```nginx
upstream php_fpm {
    server php:9000;
}

upstream vite {
    server node:5173;
}

server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php;

    charset utf-8;
    client_max_body_size 50M;

    # ── Laravel API routes ────────────────────────────────────────────
    location ~ ^/(api|sanctum|broadcasting)(/|$) {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ── PHP-FPM (handles index.php for API routes) ───────────────────
    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_index index.php;
        fastcgi_buffering off;
    }

    # ── Static assets from backend/public ─────────────────────────────
    location /build/ {
        alias /var/www/html/public/build/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location /storage/ {
        alias /var/www/html/public/storage/;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # ── Orthanc DICOM proxy ───────────────────────────────────────────
    location /orthanc/ {
        proxy_pass http://host.docker.internal:8042/;
        proxy_set_header Authorization "Basic cGFydGhlbm9uOm9ydGhhbmNfc2VjcmV0";
        proxy_set_header Host $proxy_host;
        add_header Cross-Origin-Resource-Policy "cross-origin" always;
        proxy_read_timeout 300s;
        proxy_send_timeout 300s;
    }

    # ── Vite HMR WebSocket ────────────────────────────────────────────
    location /@vite/ {
        proxy_pass http://vite;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    location /__vite_ping {
        proxy_pass http://vite;
    }

    location /ws {
        proxy_pass http://vite;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }

    # ── Deny hidden files ─────────────────────────────────────────────
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # ── Everything else → Vite dev server (SPA) ───────────────────────
    location / {
        proxy_pass http://vite;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

Routing logic:
- `/api/*`, `/sanctum/*`, `/broadcasting/*` → try_files → `index.php` → PHP-FPM
- `/build/*` → static files (production frontend assets)
- `/storage/*` → static files (uploads)
- `/orthanc/*` → reverse proxy to host Orthanc with auth + CORS
- `/@vite/*`, `/__vite_ping` → Vite dev server with WebSocket upgrade
- `/*` (everything else) → Vite dev server for SPA routing + HMR

- [ ] **Step 2: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add docker/nginx/default.conf
git commit -m "feat(docker): rewrite nginx for multi-upstream routing (PHP + Vite + Orthanc)"
```

---

## Task 3: Rewrite Docker Compose

**Files:**
- Rewrite: `docker-compose.yml`

- [ ] **Step 1: Replace docker-compose.yml**

Replace the entire contents of `docker-compose.yml` with:

```yaml
services:
  nginx:
    image: nginx:1.27-alpine
    ports: ["${NGINX_PORT:-8085}:80"]
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - ./backend/public:/var/www/html/public:ro
    depends_on:
      php:
        condition: service_healthy
      node:
        condition: service_started
    extra_hosts: ["host.docker.internal:host-gateway"]
    networks: [aurora]
    restart: unless-stopped

  php:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes: ["./backend:/var/www/html"]
    env_file: [backend/.env]
    depends_on:
      redis:
        condition: service_healthy
    extra_hosts: ["host.docker.internal:host-gateway"]
    healthcheck:
      test: ["CMD-SHELL", "php-fpm-healthcheck || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 3
      start_period: 30s
    networks: [aurora]
    restart: unless-stopped

  node:
    image: node:22-alpine
    working_dir: /app
    command: sh -c "[ -d node_modules/.package-lock.json ] && npm run dev || npm install && npm run dev"
    ports: ["${VITE_PORT:-5177}:5173"]
    volumes:
      - ./frontend:/app
      - /app/node_modules
    environment: [NODE_ENV=development]
    extra_hosts: ["host.docker.internal:host-gateway"]
    networks: [aurora]
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    ports: ["${REDIS_PORT:-6385}:6379"]
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks: [aurora]
    restart: unless-stopped

  mailhog:
    image: mailhog/mailhog
    ports: ["${MAILHOG_UI_PORT:-8030}:8025", "${MAILHOG_SMTP_PORT:-1030}:1025"]
    networks: [aurora]
    profiles: [dev]

networks:
  aurora:
    driver: bridge
```

Key changes from original:
- **Removed** `postgres` service and `postgres_data` volume entirely
- **php**: volume `./backend:/var/www/html`, env_file `backend/.env`, removed `depends_on: postgres`, added `extra_hosts`
- **node**: removed `profiles: [dev]`, volume `["./frontend:/app", "/app/node_modules"]`, command `sh -c "npm install && npm run dev"`, added `extra_hosts`
- **nginx**: volume `./backend/public:/var/www/html/public:ro`, depends on `node`, added `extra_hosts`

- [ ] **Step 2: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add docker-compose.yml
git commit -m "feat(docker): rewrite compose for dev — host Postgres, Vite HMR, correct mounts"
```

---

## Task 4: Update Vite Configuration

**Files:**
- Modify: `frontend/vite.config.ts`

- [ ] **Step 1: Update vite.config.ts**

Replace the contents of `frontend/vite.config.ts` with:

```typescript
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
  base: process.env.NODE_ENV === 'production' ? '/build/' : '/',
  build: {
    outDir: 'dist',
    manifest: true,
    rollupOptions: {
      input: resolve(__dirname, 'index.html'),
    },
  },
});
```

Changes:
- `server.host: '0.0.0.0'` — reachable from nginx container
- `server.port: 5173` — matches Docker port mapping (`5177:5173`) and nginx upstream
- Removed `server.proxy` — nginx handles API routing
- `base` is now conditional — `'/'` in dev, `'/build/'` in production

- [ ] **Step 2: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add frontend/vite.config.ts
git commit -m "feat(docker): configure Vite for Docker — host 0.0.0.0, conditional base path"
```

---

## Task 5: Create .env.example and Update backend/.env

**Files:**
- Create: `.env.example`
- Modify: `backend/.env`

- [ ] **Step 1: Create .env.example at repo root**

Create `.env.example` (informational only — the actual env_file is `backend/.env`):

```bash
# Aurora Docker Dev Environment
# Copy to backend/.env and fill in real values

APP_NAME=Aurora
APP_ENV=local
APP_KEY=base64:GENERATE_WITH_php_artisan_key_generate
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=https://aurora.acumenus.net

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database — host Postgres (not Docker)
DB_CONNECTION=pgsql
DB_HOST=host.docker.internal
DB_PORT=5432
DB_DATABASE=aurora
DB_USERNAME=smudoshi
DB_PASSWORD=your_password_here

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

# Redis — Docker service
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MEMCACHED_HOST=127.0.0.1

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Resend (email delivery)
RESEND_API_KEY=re_xxxx

# AI Services
AI_SERVICE_URL=http://ai:8100
CLAUDE_API_KEY=sk-ant-xxxx
OLLAMA_BASE_URL=http://host.docker.internal:11434

# Federation
FEDERATION_PORT=8200

# Frontend
VITE_APP_NAME="${APP_NAME}"
VITE_API_URL="https://aurora.acumenus.net/api"

# Pusher / Broadcasting (using log driver for now)
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
```

- [ ] **Step 2: Update backend/.env for Docker**

In `backend/.env`, change these two values:

Replace `DB_HOST=127.0.0.1` with `DB_HOST=host.docker.internal`
Replace `REDIS_HOST=127.0.0.1` with `REDIS_HOST=redis`

All other values stay the same — they're already correct (DB_PORT=5432, DB_DATABASE=aurora, APP_URL=https://aurora.acumenus.net, etc.)

- [ ] **Step 3: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add .env.example
git commit -m "feat(docker): add .env.example with Docker dev defaults, update backend .env"
```

Note: `backend/.env` is gitignored so only `.env.example` gets committed.

---

## Task 6: Configure Host Postgres for Docker Access

**Files:** System configuration (not in repo)

This is a one-time setup on the host machine. Requires sudo.

- [ ] **Step 1: Stop old Docker Postgres if running**

```bash
cd /home/smudoshi/Github/Aurora
docker compose down
```

This ensures the old Docker Postgres on port 5485 doesn't interfere.

- [ ] **Step 2: Configure pg_hba.conf**

The user must run this command manually (requires sudo):

```bash
# Add Docker bridge access — 172.0.0.0/8 covers all Docker bridge networks
echo "host all all 172.0.0.0/8 md5" | sudo tee -a /etc/postgresql/16/main/pg_hba.conf
```

Using `172.0.0.0/8` is safe for dev — Docker always uses subnets in the 172.x range. For tighter security in production, inspect the exact subnet with `docker network inspect aurora_aurora | grep Subnet` after the stack is up.

- [ ] **Step 3: Configure postgresql.conf**

The user must verify listen_addresses:

```bash
grep listen_addresses /etc/postgresql/16/main/postgresql.conf
```

If it shows `listen_addresses = 'localhost'`, change to:
```
listen_addresses = '*'
```

For dev this is fine (the machine is presumably on a private network). For tighter security, use `listen_addresses = 'localhost,172.18.0.1'`.

- [ ] **Step 4: Reload Postgres**

```bash
sudo systemctl reload postgresql
```

---

## Task 7: Rebuild and Bring Up Docker Stack

- [ ] **Step 1: Rebuild the PHP container**

```bash
cd /home/smudoshi/Github/Aurora
docker compose build --no-cache php
```

This rebuilds the PHP image with the new entrypoint.

- [ ] **Step 2: Bring up all services**

```bash
docker compose up -d
```

Expected: nginx, php, node, redis all start. Watch logs:

```bash
docker compose logs -f --tail=50
```

- [ ] **Step 3: Verify PHP container health**

```bash
docker compose ps
```

Expected: `php` shows `healthy`, `nginx` is `Up`, `node` is `Up`, `redis` is `healthy`.

If php is unhealthy, check logs:
```bash
docker compose logs php
```

Common issues:
- `vendor/autoload.php` not found → entrypoint should handle this, check logs for composer errors
- Can't connect to Postgres → pg_hba.conf not reloaded (Task 6)

- [ ] **Step 4: Test API via Docker**

```bash
curl -s http://localhost:8085/api/login -X POST \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"admin@acumenus.net","password":"superuser"}' | head -c 200
```

Expected: JSON response with `token` field (successful login) or `{"success":false,"message":"Invalid credentials"}` (wrong password but API is working).

- [ ] **Step 5: Test Vite dev server via Docker**

```bash
curl -s http://localhost:8085/ | head -c 200
```

Expected: HTML with Vite script tags (e.g., `/@vite/client`), not "File not found".

- [ ] **Step 6: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -u
git commit -m "feat(docker): fully dockerized dev environment working"
```

---

## Task 8: Update Apache Vhost

**Files:** `/etc/apache2/sites-available/aurora.acumenus.net-le-ssl.conf` (requires sudo)

- [ ] **Step 1: Show the new Apache config to the user**

The user needs to replace the Apache vhost content (requires sudo). The new config:

```apache
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName aurora.acumenus.net
    ServerAdmin webmaster@aurora.acumenus.net

    # Reverse proxy to Docker nginx
    ProxyPreserveHost On
    ProxyPass / http://127.0.0.1:8085/
    ProxyPassReverse / http://127.0.0.1:8085/

    # WebSocket support for Vite HMR
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule /(.*) ws://127.0.0.1:8085/$1 [P,L]

    ErrorLog ${APACHE_LOG_DIR}/aurora.acumenus.net-error.log
    CustomLog ${APACHE_LOG_DIR}/aurora.acumenus.net-access.log combined

    SSLCertificateFile /etc/letsencrypt/live/aurora.acumenus.net/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/aurora.acumenus.net/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf
</VirtualHost>
</IfModule>
```

- [ ] **Step 2: Enable required Apache modules**

```bash
sudo a2enmod proxy proxy_http proxy_wstunnel rewrite
```

- [ ] **Step 3: Reload Apache**

```bash
sudo systemctl reload apache2
```

- [ ] **Step 4: Verify via aurora.acumenus.net**

```bash
curl -sk https://aurora.acumenus.net/api/login -X POST \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"admin@acumenus.net","password":"superuser"}' | head -c 200
```

Expected: Same JSON response as Step 4 of Task 7.

Then open `https://aurora.acumenus.net` in a browser — should show the Aurora frontend with hot reload.

---

## Task 9: Smoke Test Case-Patient Integration

This completes the paused Task 5 from the case-patient integration plan.

- [ ] **Step 1: Verify case detail page with patient profile**

Open `https://aurora.acumenus.net/cases/15` (or any case with a `patient_id`).

Verify:
- Collapsible case context header shows clinical question, summary, stats
- Overview tab shows embedded patient profile (demographics card, view modes)
- All 9 view modes work (briefing, timeline, list, labs, visits, notes, imaging, genomics, similar)
- "Full profile" link navigates to standalone profile page
- Documents and Team tabs still work

- [ ] **Step 2: Verify standalone profile page**

Open `https://aurora.acumenus.net/profiles/154`.

Verify: All view modes work, Export CSV works, no regressions.

- [ ] **Step 3: Push all changes**

```bash
cd /home/smudoshi/Github/Aurora
git push origin v2/phase-0-scaffold
```
