# Backup & restore runbook (W3-T08)

Aurora's data lives in a **host PostgreSQL** database (`aurora`, user `smudoshi`,
port 5432). There is no database container — the app containers reach it via
`host.docker.internal`. Backups therefore run on the host with `pg_dump`.

Schemas of record: `app` (application), `clinical` (patient/genomic/imaging),
plus Laravel/Spatie tables. The read-only `omop` vocabulary is large and
restorable from source — exclude it from routine app backups if size is a concern.

> Credentials come from `~/.pgpass` (already configured for `smudoshi`). Never
> put the password in scripts or cron.

---

## Daily logical backup

```bash
# /usr/local/bin/aurora-backup.sh
set -euo pipefail
BACKUP_DIR=/var/backups/aurora
STAMP=$(date +%F-%H%M)
mkdir -p "$BACKUP_DIR"

# Custom-format dump (compressed, supports selective restore). Exclude the
# read-only omop vocabulary to keep app backups small/fast.
pg_dump -h localhost -U smudoshi -d aurora -Fc \
  --exclude-schema=omop \
  -f "$BACKUP_DIR/aurora-$STAMP.dump"

# Retain 14 daily dumps.
ls -1t "$BACKUP_DIR"/aurora-*.dump | tail -n +15 | xargs -r rm -f
```

Schedule (host crontab — edit via `crontab -e`, never pipe into `crontab -`):

```cron
15 2 * * *  /usr/local/bin/aurora-backup.sh >> /var/log/aurora-backup.log 2>&1
```

Verify a fresh dump exists and is non-trivial in size each morning; alert if the
newest dump is older than 26h (ties into W3-T06 alerting).

---

## Restore (validated procedure)

**Always restore into a scratch database first and validate before touching prod.**

```bash
# 1. Restore the latest dump into a scratch DB
createdb -h localhost -U smudoshi aurora_restore_check
pg_restore -h localhost -U smudoshi -d aurora_restore_check --no-owner \
  /var/backups/aurora/aurora-<STAMP>.dump

# 2. Sanity-check row counts in the critical schemas
psql -h localhost -U smudoshi -d aurora_restore_check -c \
  "select 'patients', count(*) from clinical.patients
   union all select 'cases', count(*) from app.cases
   union all select 'users', count(*) from app.users;"

# 3. Drop the scratch DB when satisfied
dropdb -h localhost -U smudoshi aurora_restore_check
```

**Real recovery (prod data loss)** — confirm with the owner first; this is
destructive:

```bash
# Stop the app so nothing writes mid-restore
cd /home/smudoshi/Github/Aurora && docker compose stop php nginx
# Restore over prod (or restore into a new DB and repoint backend/.env)
pg_restore -h localhost -U smudoshi -d aurora --clean --if-exists --no-owner \
  /var/backups/aurora/aurora-<STAMP>.dump
docker compose up -d php nginx
curl -fsS http://127.0.0.1:8085/api/health/ready   # confirm readiness
```

---

## Off-host copy

Daily dumps must be replicated off the host (the database and backups on the same
machine is not a backup). Sync `/var/backups/aurora` to the Fort Knox / offsite
target on the same cadence. **Validate a restore from the offsite copy quarterly.**

## Test cadence

- **Monthly:** restore the latest dump into a scratch DB and run the row-count
  sanity check above. Record the date in `docs/devlog.md`.
- A backup that has never been restored is not a backup.
