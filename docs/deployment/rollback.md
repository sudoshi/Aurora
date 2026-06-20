# Rollback runbook (W3-T09)

How to revert a bad Aurora deploy. Prod is **Apache (TLS) → docker nginx:8085 →
docker stack** (`php`, `nginx`, `redis`, optional `reverb`), served from
`/home/smudoshi/Github/Aurora`, deployed via `./deploy.sh` (or the CI deploy job
on push to `main`). DB is host PostgreSQL `aurora` (see `backup-restore.md`).

> Golden rule: **code rolls back freely; migrations do not.** Prefer rolling
> forward for schema. Only restore the database if a migration or bug corrupted
> data — and back up first.

---

## 1. Code-only rollback (no schema change in the bad deploy)

This is the common case and is safe.

```bash
cd /home/smudoshi/Github/Aurora
git log --oneline -10                     # find the last-good commit
git revert --no-edit <bad_sha>            # or: git revert <range>
git push origin main                      # CI redeploys, or run ./deploy.sh
./deploy.sh                               # rebuilds frontend, clears caches, reloads
curl -fsS http://127.0.0.1:8085/api/health/ready
```

`deploy.sh` rebuilds `backend/public/build` from source each run, so the frontend
assets roll back with the code — no separate asset step needed. `restart_reverb`
and `verify_static_frontend` run automatically.

If you must revert *without* a new commit (emergency), check out the last-good
SHA and redeploy, then reconcile `main` afterward:

```bash
git checkout <good_sha> -- .   # or: git reset --hard <good_sha> on a hotfix branch
./deploy.sh
```

---

## 2. Rollback involving a migration

`deploy.sh` runs `php artisan migrate --force`. Laravel migrations are
**authoritative** and are written expand/contract where possible, but a bad
migration still needs care.

1. **Reversible migration (has a correct `down()`):**
   ```bash
   cd /home/smudoshi/Github/Aurora
   docker compose exec -T php php artisan migrate:rollback --step=1 --force
   ```
   Then revert the code (section 1). Confirm the rolled-back migration's `down()`
   actually restores the prior schema (test on the scratch DB first if unsure).

2. **Irreversible / destructive migration (dropped a column, transformed data):**
   Do **not** trust `down()`. Restore the database from the pre-deploy backup:
   ```bash
   docker compose stop php nginx
   pg_restore -h localhost -U smudoshi -d aurora --clean --if-exists --no-owner \
     /var/backups/aurora/aurora-<pre-deploy-STAMP>.dump
   git revert --no-edit <bad_sha> && ./deploy.sh
   ```
   Always take a fresh `pg_dump` immediately **before** any deploy that includes
   a destructive migration so this path exists.

---

## 3. Decision guide

| Symptom | Action |
|---------|--------|
| 5xx / broken UI, no schema change | Section 1 (revert code + `./deploy.sh`) |
| Bad but reversible migration | Section 2.1 (`migrate:rollback`) then revert code |
| Data corrupted / destructive migration | Section 2.2 (DB restore) — confirm with owner |
| Realtime broken only | `BROADCAST_CONNECTION=log` + rebuild frontend; app keeps working via polling |
| Site down (nginx) | `docker compose up -d nginx php` and check `/api/health/ready` |

## 4. After any rollback

- Confirm `GET /api/health/ready` returns `200 ready`.
- Confirm the admin System Health page shows all green.
- Record the incident + root cause in `docs/devlog.md`.
- Open a follow-up to fix-forward properly (rollbacks are temporary).
