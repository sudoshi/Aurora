# Aurora — GA operator checklist (human-only items)

Last updated: 2026-06-20

Everything autonomously closable in the GA plan is done and on `main`. The items
below need a human: a decision, shared-infra coordination, a credential, or a
production action. **Almost none need `sudo`** — they run as `smudoshi` (docker
compose, php artisan, git). The few that do are marked **[sudo]**.

Prod topology (confirmed): **Apache (TLS :443) → docker `aurora-nginx` :8085 →
docker stack** (`php`, `redis`, optional `reverb`). The Apache vhost already
tunnels WebSockets to :8085, and `docker/nginx/default.conf.template` already
proxies `/app` → `reverb:8080`. So Reverb in prod is the **docker `reverb`
service**, NOT the systemd unit in `deploy/aurora-reverb.service` (that artifact
was for an alternate host-fpm topology — ignore it for this deployment).

---

## 1. Activate real-time (Reverb) in production — W1-T07/T08

No sudo. Run as `smudoshi` from `/home/smudoshi/Github/Aurora`.

```bash
# (a) Generate app credentials (any strong random strings; these are NOT secret
#     to the browser KEY, but SECRET must stay server-side).
RK=$(openssl rand -hex 16); RS=$(openssl rand -hex 24)

# (b) Set the realtime vars in backend/.env (edit the file; values below).
#     Client-facing values go over the public host on 443 (Apache → nginx → reverb).
cat >> backend/.env <<EOF
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=aurora
REVERB_APP_KEY=${RK}
REVERB_APP_SECRET=${RS}
REVERB_HOST=aurora.acumenus.net
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
VITE_REVERB_APP_KEY=${RK}
VITE_REVERB_HOST=aurora.acumenus.net
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
EOF
# NOTE: if any of these keys already exist in backend/.env, edit in place instead
# of appending duplicates.

# (c) Rebuild the frontend so VITE_REVERB_* are compiled into the bundle, and
#     re-render nginx (picks up the /app proxy) + start the reverb container.
./deploy.sh --frontend          # or a full ./deploy.sh
docker compose up -d nginx
docker compose --profile realtime up -d reverb

# (d) Verify
docker compose ps reverb                     # Up (healthy)
ss -ltn | grep 8080                          # reverb listening
curl -sS -o /dev/null -w '%{http_code}\n' \
  -H 'Connection: Upgrade' -H 'Upgrade: websocket' \
  -H 'Sec-WebSocket-Version: 13' -H 'Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==' \
  "https://aurora.acumenus.net/app/${RK}"    # expect 101 (or 426/400 from reverb, NOT 502/503)

# (e) Prove live multi-user delivery (the W1-T08 acceptance):
cd e2e && npx playwright test realtime --project=chromium && cd ..
```
If the WS check returns 502/503: the reverb container isn't up or nginx didn't
re-render — re-run (c). Realtime is optional: with it off the app uses the 8s
polling fallback, so this is not a hard outage risk.

---

## 2. Rotate the exposed Orthanc credential — W2-T02 / W2-T03

The old proxy credential is in git history (now redacted from the tree). Treat it
as compromised. **Coordinate with the Parthenon side** — Orthanc is shared infra.

```bash
# (a) [coordination] Change the Orthanc user's password on the Orthanc server.
#     Then set the new value the nginx /orthanc/ proxy sends upstream:
NEWPASS='<new-orthanc-password>'
printf 'ORTHANC_PROXY_AUTH=Basic %s\n' \
  "$(printf 'parthenon:%s' "$NEWPASS" | base64)" >> .env   # root .env (compose interpolates it)
# also update backend/.env ORTHANC_PASS / ORTHANC_USER if the indexing service uses them.

# (b) Re-render nginx with the injected credential:
docker compose up -d nginx
curl -sS -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8085/orthanc/system  # expect 200, not 401

# (c) Optional history scrub (W2-T03) — DECISION REQUIRED. Rewrites history; coordinate
#     with anyone who has clones. Using git filter-repo (no sudo):
#   git filter-repo --replace-text <(echo 'GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih==>REDACTED')
#   git push --force-with-lease origin main      # force-push rewritten history
# After the scrub, enable full-history secret scanning by removing `--no-git`
# from the gitleaks step in .github/workflows/ci.yml.
```

---

## 3. Production environment vars to set (backend/.env) — misc

No sudo.

```bash
# Queue throughput (W11-T02b): use Redis in prod.
#   QUEUE_CONNECTION=redis
# Metrics scrape token (W3-T05): set a strong token AND restrict /api/metrics by
# network/firewall to your Prometheus host.
#   METRICS_TOKEN=$(openssl rand -hex 24)
# After editing backend/.env: ./deploy.sh --php   (re-cache config)
```

---

## 4. Backups: schedule + prove a restore — W3-T08

No sudo (writes to /var/backups; if that dir needs root, use a user-writable
path or **[sudo]** mkdir once). See `docs/deployment/backup-restore.md` for the
full script + cron line. Minimum:

```bash
# Install the daily dump cron (edit via temp file; never pipe into `crontab -`):
crontab -l > /tmp/cron.$$ 2>/dev/null; \
  echo '15 2 * * * /usr/local/bin/aurora-backup.sh >> /var/log/aurora-backup.log 2>&1' >> /tmp/cron.$$; \
  crontab /tmp/cron.$$; rm /tmp/cron.$$
# Prove a restore into a scratch DB (the doc has the exact pg_restore commands).
```

---

## 5. Sign-offs & the GA tag — W13-T06

These are yours to decide:

1. **Security review sign-off:** read `docs/security/threat-model.md`; confirm the
   D1 (open clinical workspace + PHI audit logging) and D2 (internal-identified /
   external-de-identified) decisions are acceptable for your IRB/DUA.
2. **Retention windows:** confirm the defaults in `docs/deployment/data-retention.md`
   against institutional policy (audit logs 6yr/HIPAA, etc.).
3. **Tag GA** once 1–4 above are done and CI is green on `main`:
   ```bash
   git tag -a v2.0.0-ga -m "Aurora GA (Research Use Only)"
   git push origin v2.0.0-ga
   ```

---

## Still open in code (autonomous, but larger — not blocking the above)
- **W0-T03b** — clear the 9 ignored transitive CVEs. Needs a coordinated
  `pydantic 2.10→2.11+` migration + `fastapi`/`starlette` 1.x + `mcp`≥1.23, with a
  **live BioMCP smoke** (tests mock it). Safe state meanwhile: CVEs are explicitly
  ignored; new vulns still fail CI. (Analysis in the GA plan.)
- **W4-T01** — split the 1,990-line `ImagingController` (P2 maintainability).
- **W14** — other large-file refactors (P3).
- **W3-T05 follow-up** — request-latency/error-rate histograms need a Redis-backed
  Prometheus client (the point-in-time gauge endpoint ships now).
- **W12 follow-up** — color-contrast / keyboard-nav need a live-browser a11y pass
  (jsdom axe can't evaluate contrast).
