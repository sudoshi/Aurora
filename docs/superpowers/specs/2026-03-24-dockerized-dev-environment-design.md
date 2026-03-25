# Fully Dockerized Dev Environment Design

**Date:** 2026-03-24
**Status:** Approved
**Goal:** Replace the broken dual-setup (Apache direct-serve + incomplete Docker) with a single, fully dockerized development environment that serves the entire app via Docker, using only host Postgres.

## Problem

Aurora has two competing setups, neither fully working for local dev:
- **Apache vhost** (`aurora.acumenus.net`) serves backend via host PHP-FPM socket with pre-built frontend. This is what actually works in production.
- **Docker** (nginx + php + postgres + redis) is broken: the PHP Dockerfile copies `backend/` into `/var/www/html`, but the volume mount `.:/var/www/html` maps the repo root, so `public/index.php` is not found.

This causes confusion when testing API endpoints locally and means there's no single command to spin up the entire stack.

## Solution

Fix docker-compose.yml with correct volume mounts, rewrite nginx config to route to both PHP-FPM and Vite dev server, switch from Docker Postgres to host Postgres, and update the Apache vhost to reverse-proxy to Docker.

## Design

### 1. Docker Compose Services

**Remove:**
- `postgres` service — use host Postgres instead
- `postgres_data` volume — no longer needed

**Fix `php` service:**
- Volume mount: `./backend:/var/www/html` (was `.:/var/www/html`)
- Remove `depends_on: postgres`
- Add `extra_hosts: ["host.docker.internal:host-gateway"]` for host Postgres access
- Keep healthcheck, env_file, restart policy
- Add entrypoint script `docker/php/entrypoint.sh` that runs `composer install --no-interaction` if `vendor/autoload.php` is missing, then clears Laravel config cache for dev, then exec's `php-fpm`

**Fix `node` service:**
- Volume mount: `["./frontend:/app", "/app/node_modules"]` — bind mount for source, anonymous volume for node_modules (prevents host/container mismatch)
- Remove `profiles: [dev]` — always active
- Add `extra_hosts: ["host.docker.internal:host-gateway"]`
- Command: `sh -c "npm install && npm run dev"` — ensures deps are installed before starting
- Keep port mapping `5177:5173`

**Fix `nginx` service:**
- Volume mount: `./backend/public:/var/www/html/public:ro` for static assets (storage, favicon)
- Keep port `8085:80`
- Depends on both `php` and `node`

**Keep as-is:**
- `redis` — unchanged
- `mailhog` — stays on `dev` profile

### 2. Nginx Configuration

Rewrite `docker/nginx/default.conf` to route to three backends:

| Route | Backend | Purpose |
|-------|---------|---------|
| `/api/*`, `/sanctum/*`, `/broadcasting/*` | PHP-FPM (`php:9000`) | Laravel API |
| `/build/*` | Static files from `backend/public/build/` | Pre-built frontend assets (production fallback) |
| `/storage/*` | Static files from `backend/public/storage/` | Uploaded files |
| `/orthanc/*` | Proxy to `host.docker.internal:8042` | DICOM server |
| `/@vite/*`, `/__vite_ping`, `/ws` | Vite dev server (`node:5173`) | HMR WebSocket |
| Everything else | Vite dev server (`node:5173`) | SPA + hot reload |

**Key nginx details:**
- `upstream php { server php:9000; }` — PHP-FPM via FastCGI
- `upstream vite { server node:5173; }` — Vite dev server via HTTP proxy
- WebSocket upgrade headers for Vite HMR connections
- `client_max_body_size 50M` — preserve current setting for file uploads
- Orthanc proxy includes:
  - Basic Auth header: `proxy_set_header Authorization "Basic cGFydGhlbm9uOm9ydGhhbmNfc2VjcmV0"`
  - CORS header: `add_header Cross-Origin-Resource-Policy "cross-origin" always;` (required for OHIF DICOM viewer iframe)
  - `proxy_set_header Host $proxy_host;` (Orthanc expects its own hostname, not the client's)

### 3. Apache Reverse Proxy

Replace the current direct-serve Apache config with a reverse proxy to Docker.

**Remove from Apache vhost:**
- `DocumentRoot` and `<Directory>` block
- `<FilesMatch \.php$>` handler
- Orthanc `ProxyPass /orthanc/` rules (moved to nginx)

**Replace with:**
```
ProxyPreserveHost On
ProxyPass / http://127.0.0.1:8085/
ProxyPassReverse / http://127.0.0.1:8085/

# WebSocket support for Vite HMR
RewriteEngine On
RewriteCond %{HTTP:Upgrade} websocket [NC]
RewriteCond %{HTTP:Connection} upgrade [NC]
RewriteRule /(.*) ws://127.0.0.1:8085/$1 [P,L]
```

**Keep on Apache:**
- SSL termination (Let's Encrypt certs)
- `ServerName aurora.acumenus.net`
- Error/access logs

**Note:** The Orthanc proxy now goes through an extra hop (Apache → nginx → Orthanc) instead of Apache → Orthanc directly. This adds negligible latency for metadata requests but may be noticeable for large DICOM transfers. This trade-off is acceptable for dev — the alternative is maintaining Orthanc proxy config in two places.

**Result:** `aurora.acumenus.net` → Apache (HTTPS) → Docker nginx (:8085) → PHP-FPM or Vite.

### 4. Environment Configuration

**Host Postgres connection from Docker:**
- PHP container connects via `host.docker.internal` (enabled by `extra_hosts` directive)
- Host Postgres must allow connections from Docker's bridge network

**Postgres setup steps (one-time):**
1. Find the Docker network subnet: `docker network inspect aurora_aurora | grep Subnet`
2. Add to `/etc/postgresql/16/main/pg_hba.conf`: `host all all 172.18.0.0/16 md5` (adjust subnet to match step 1)
3. In `/etc/postgresql/16/main/postgresql.conf`, set `listen_addresses = 'localhost,172.18.0.1'` (bind to Docker bridge gateway, not `0.0.0.0` which exposes to all interfaces)
4. Reload: `sudo systemctl reload postgresql`

**`.env` key values for Docker dev:**
- `APP_URL=https://aurora.acumenus.net` (used by Laravel for URL generation in emails, redirects)
- `DB_HOST=host.docker.internal`
- `DB_PORT=5432` (direct host port, not the mapped 5485)
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — same as current production values
- `REDIS_HOST=redis` (Docker service name)

**Note:** If the Docker Postgres service is still running from the old config, stop it first to avoid port 5432 conflicts on the host.

**`.env.example`:** New file with all required keys and Docker-oriented defaults. Should include all keys from the current `.env` (APP_KEY, APP_ENV, DB_*, REDIS_*, RESEND_API_KEY, AI_SERVICE_URL, CLAUDE_API_KEY, OLLAMA_BASE_URL, FEDERATION_PORT, etc.) with safe placeholder values.

### 5. Vite Configuration

**Changes to `frontend/vite.config.ts`:**
- Set `server.host: '0.0.0.0'` — makes Vite reachable from the nginx container
- Set `server.port: 5173` — matches the container-internal port that nginx and the Docker port mapping (`5177:5173`) expect
- Remove the `/api` proxy block — nginx handles API routing now
- Change `base` to be conditional: `base: process.env.NODE_ENV === 'production' ? '/build/' : '/'` — in dev mode, Vite serves assets from root; in production builds, assets go under `/build/`

### 6. PHP Entrypoint Script

New file: `docker/php/entrypoint.sh`

Purpose: ensure the PHP container is ready for dev on startup without manual intervention.

```bash
#!/bin/sh
set -e

# Install composer deps if vendor is missing (first run after volume mount)
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction
fi

# Clear caches for dev (in case production caches were left)
php artisan config:clear
php artisan route:clear
php artisan view:clear

exec php-fpm
```

The Dockerfile needs an `ENTRYPOINT` directive pointing to this script (or docker-compose overrides the command).

## Files Modified

| File | Change |
|------|--------|
| `docker-compose.yml` | Remove postgres, fix volumes, activate node service, add extra_hosts, update commands |
| `docker/nginx/default.conf` | Full rewrite: multi-upstream routing (PHP, Vite, Orthanc, static) with CORS headers |
| `docker/php/entrypoint.sh` | New: composer install + cache clear + exec php-fpm |
| `docker/php/Dockerfile` | Add COPY for entrypoint.sh, set ENTRYPOINT |
| `frontend/vite.config.ts` | Add `server.host`, set port 5173, conditional `base`, remove proxy |
| `.env.example` | New: comprehensive Docker dev defaults with all required keys |
| Apache vhost | Replace direct-serve with ProxyPass to localhost:8085 |

## Not In Scope

- Production Docker deployment or image optimization
- CI/CD pipeline changes
- Database migration or schema changes
- Docker Compose production profile
- SSL inside Docker (Apache handles SSL)
