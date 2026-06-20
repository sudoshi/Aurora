# Realtime (Laravel Reverb) — deployment runbook

Aurora's Commons collaboration (live messages, reactions, presence, typing,
notifications) is delivered over WebSockets by **Laravel Reverb**.

- **Backend**: `ShouldBroadcastNow` events in `app/Events/Commons/*` dispatched
  from the Commons controllers; channel auth in `routes/channels.php`
  (registered under `auth:sanctum` via `bootstrap/app.php`).
- **Frontend**: `src/lib/echo.ts` (laravel-echo + pusher-js) connects to
  `wss://<host>/app/<key>`; `useMessages` falls back to 8s polling whenever the
  socket is not `connected`, with a "Reconnecting…" indicator.

Realtime is **optional** — with no Reverb running the app degrades to polling.

---

## Local / dev (Docker stack, no sudo)

1. Add the Reverb vars to `backend/.env` (values must match what the browser
   build uses; see `.env.example`):

   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=aurora
   REVERB_APP_KEY=aurora-local-key
   REVERB_APP_SECRET=aurora-local-secret
   REVERB_HOST=localhost
   REVERB_PORT=8085          # the nginx port — /app is proxied to reverb:8080
   REVERB_SCHEME=http
   VITE_REVERB_APP_KEY=aurora-local-key
   VITE_REVERB_HOST=localhost
   VITE_REVERB_PORT=8085
   VITE_REVERB_SCHEME=http
   ```

2. Start the Reverb container (opt-in profile) and rebuild the frontend so the
   `VITE_REVERB_*` values are baked in:

   ```bash
   docker compose --profile realtime up -d reverb
   ./deploy.sh --frontend   # or: npm --prefix frontend run build
   ```

3. nginx already proxies `/app` → `reverb:8080` (see
   `docker/nginx/*.conf.template`). Open the app in two browsers and confirm a
   message posted in one appears in the other without refresh.

---

## Production (Apache + host php-fpm) — requires sudo

Prod runs Reverb as a systemd service behind the Apache vhost.

1. **Install the systemd unit:**

   ```bash
   sudo cp deploy/aurora-reverb.service /etc/systemd/system/aurora-reverb.service
   sudo systemctl daemon-reload
   sudo systemctl enable --now aurora-reverb
   systemctl status aurora-reverb        # expect: active (running)
   ```

2. **Set prod env** in `backend/.env` (browser-facing values use the public host
   over TLS on 443; Apache proxies `/app` to the local server):

   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=<random>
   REVERB_APP_KEY=<random>
   REVERB_APP_SECRET=<random>
   REVERB_HOST=aurora.acumenus.net
   REVERB_PORT=443
   REVERB_SCHEME=https
   VITE_REVERB_APP_KEY=<same as REVERB_APP_KEY>
   VITE_REVERB_HOST=aurora.acumenus.net
   VITE_REVERB_PORT=443
   VITE_REVERB_SCHEME=https
   ```

   Generate credentials with `php artisan reverb:install` (or any random
   strings). After editing `.env`, rebuild the frontend so `VITE_REVERB_*` are
   compiled in: `./deploy.sh --frontend` (or a full `./deploy.sh`).

3. **Enable the Apache WebSocket proxy:**

   ```bash
   sudo a2enmod proxy proxy_http proxy_wstunnel rewrite
   # paste deploy/apache-aurora-reverb.conf into the <VirtualHost *:443> for
   # aurora.acumenus.net-le-ssl.conf, then:
   sudo apachectl configtest && sudo systemctl reload apache2
   ```

4. **Verify:**

   ```bash
   # Reverb listening locally
   ss -ltnp | grep 8080
   # WebSocket handshake through Apache (101 Switching Protocols expected)
   curl -sSI -o /dev/null -w '%{http_code}\n' \
     -H 'Connection: Upgrade' -H 'Upgrade: websocket' \
     https://aurora.acumenus.net/app/<REVERB_APP_KEY>
   ```

   Then run the multi-user E2E: `npx playwright test realtime --project=chromium`
   against `https://aurora.acumenus.net`.

5. **Ongoing:** `./deploy.sh` calls `restart_reverb`, which restarts the
   `aurora-reverb` unit on each deploy. Logs: `journalctl -u aurora-reverb -f`.

---

## Rollback / disable

Set `BROADCAST_CONNECTION=log` in `backend/.env`, rebuild the frontend without
`VITE_REVERB_APP_KEY` (the client then reports `disabled` and uses polling), and
`sudo systemctl stop aurora-reverb`. No data is affected.
