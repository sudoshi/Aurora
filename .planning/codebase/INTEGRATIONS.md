# External Integrations

**Analysis Date:** 2026-03-24

## APIs & External Services

**Email Delivery:**
- Resend - Temp password and transactional emails
  - SDK/Client: `resend/resend-php` (Laravel)
  - Auth: `RESEND_API_KEY` environment variable
  - Implementation: `backend/app/Services/AuthService.php` (temp password), `backend/app/Http/Controllers/Admin/UserController.php` (admin creation)
  - Endpoint: `https://api.resend.com/emails` (HTTP POST via Laravel Http facade)
  - Email sender: `Aurora <noreply@acumenus.net>`

**AI / LLM Services:**
- Anthropic (Claude API) - Language model inference for Abby AI
  - SDK/Client: `anthropic==0.40.0` (Python)
  - Auth: `CLAUDE_API_KEY` environment variable
  - Implementation: `ai/app/config.py` (claude_api_key, claude_model: claude-sonnet-4-20250514)
  - Endpoint: `https://api.anthropic.com/v1/messages`
  - Usage: Abby briefings, clinical decision support, genomic analysis
  - Config: Max 4096 tokens, 60s timeout

- Ollama (Local LLM Runtime) - MedGemma and other open models
  - SDK/Client: HTTP calls via httpx in Python
  - Auth: None (local service)
  - Implementation: `ai/app/config.py` (ollama_base_url, ollama_model: medgemma-q4:latest)
  - Endpoint: `http://host.docker.internal:11434` (dev) or configured URL
  - Usage: Fallback LLM for cost control, local inference
  - Config: 120s timeout

**Genomics & Clinical Data:**
- OncoKB (Precision Oncology Knowledge Base) - Cancer variant interpretation
  - SDK/Client: HTTP calls via Laravel Http facade
  - Auth: `ONCOKB_API_TOKEN` environment variable
  - Implementation: `backend/app/Services/Genomics/OncoKbService.php`
  - Command: `php artisan refresh-evidence` (scheduled sync)
  - Usage: Gene-level evidence, therapeutic implications, clinical trials
  - Config: `backend/config/services.php` (oncokb.token)

- ClinVar (NCBI ClinVar) - Variant pathogenicity interpretations
  - SDK/Client: HTTP FTP downloads from NCBI
  - Auth: None (public database)
  - Implementation: `backend/app/Services/Genomics/ClinVarSyncService.php`, `backend/app/Services/Genomics/ClinVarAnnotationService.php`
  - Commands: `php artisan genomics:sync-clinvar`, `php artisan refresh-evidence`
  - Usage: Variant classification, evidence aggregation, PAPU subset filtering
  - Data: VCF format from `ftp://ftp.ncbi.nlm.nih.gov/pub/clinvar/`
  - Storage: `clinical.clinvar_variants` table with pgvector embeddings

## Data Storage

**Databases:**
- PostgreSQL 16
  - Connection: `host.docker.internal:5432` (dev) or configured host (prod)
  - Client: Laravel Eloquent ORM (backend), SQLAlchemy (Python AI service)
  - Authentication: DB_USERNAME / DB_PASSWORD
  - Schemas: `app`, `clinical`, `public` (search_path in config)
  - Features: pgvector extension for vector similarity search (embeddings)
  - Models: User, Patient, ClinicalPatient, Visit, Medication, Condition, ClinVarVariant, etc.

**Caching:**
- Redis 7
  - Connection: `redis://redis:6379` (dev Docker) or `REDIS_*` env vars
  - Client: phpredis (PHP), redis-py (Python)
  - Purpose: Cache store, session backend, queue jobs
  - Implementation: `backend/config/cache.php` (redis store), `backend/config/session.php`
  - Key Prefix: `aurora_` (configurable)
  - Databases: Default (0) and Cache (1) separated in config
  - TTL: Configurable per key, typically 5-60 minutes

**File Storage:**
- Local filesystem (development)
  - Path: `backend/storage/` for uploads
  - Driver: Laravel local disk
  - Implementation: `backend/config/filesystems.php` (FILESYSTEM_DISK=local)
  - Genomics files: Patient VCF uploads, ClinVar data imports

- AWS S3 (production optional)
  - Auth: `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`
  - Config: Bucket name via `AWS_BUCKET`, region via `AWS_DEFAULT_REGION`
  - Not currently deployed but configured in `backend/config/filesystems.php`

## Authentication & Identity

**Auth Provider:**
- Custom Sanctum-based (Laravel native)
  - Implementation: `backend/app/Http/Controllers/AuthController.php`
  - Flow: Temp password email (Resend) → login → forced password change → token issued
  - Features:
    - No user-chosen password at registration (temp password only)
    - Forced password change on first login (must_change_password flag)
    - Token revocation on logout or password change
    - Superuser account: `admin@acumenus.net` (seeded with all 8 roles, must_change_password=false)
  - RBAC: Spatie Laravel Permission with 8 system roles (seeded in database)
  - Token: Bearer token in Authorization header for all protected routes

**API Endpoints:**
- `POST /api/auth/register` - User registration with email only
- `POST /api/auth/login` - Login with email/password, returns must_change_password flag
- `POST /api/auth/change-password` - Forced password change after first login
- `POST /api/auth/logout` - Revoke all tokens
- Middleware: `auth:sanctum` on all protected routes

## Monitoring & Observability

**Error Tracking:**
- Log channel: Configurable (log, Slack, single file, stack)
  - Implementation: `backend/config/logging.php`
  - Driver: `log` (default to file), `slack` (webhook integration available)
  - Env vars: `LOG_SLACK_WEBHOOK_URL`, `LOG_SLACK_USERNAME`, `LOG_SLACK_EMOJI`

**Logs:**
- File-based logging
  - Path: `backend/storage/logs/`
  - Rotation: Daily or single (configurable)
  - Level: Configurable via `LOG_LEVEL` env var (debug in dev, warning/error in prod)
  - Implementation: Laravel Monolog integration
  - Viewer: Laravel Pail (`php artisan pail`)

- Real-time log viewer
  - Command: `php artisan pail` (streams logs to terminal)
  - Used in dev environment: `npm run dev` starts pail alongside server

## CI/CD & Deployment

**Hosting:**
- Target: aurora.acumenus.net (Apache vhost on Linux)
- DocumentRoot: `/home/smudoshi/Github/Aurora/backend/public`
- SSL: Let's Encrypt (aurora.acumenus.net-le-ssl.conf)

**CI Pipeline:**
- Platform: GitHub Actions
- Config: `.github/workflows/` directory
- Services tested: Backend (Laravel/PHP), Frontend (React), E2E (Playwright)
- Triggers: Push to main, PR creation

**Deployment Artifacts:**
- Frontend: Built dist/ copied to backend/public/build/
- Backend: Migrations run, services restarted
- Script: `deploy.sh` handles pull, install, build, and server restart

## Environment Configuration

**Required Environment Variables:**

*Core Application:*
- `APP_NAME=Aurora`
- `APP_ENV=local|production`
- `APP_KEY=base64:...` (generated via `php artisan key:generate`)
- `APP_DEBUG=true|false`
- `APP_URL=https://aurora.acumenus.net`

*Database:*
- `DB_CONNECTION=pgsql`
- `DB_HOST=localhost` (or host.docker.internal in Docker)
- `DB_PORT=5432`
- `DB_DATABASE=aurora`
- `DB_USERNAME=postgres_user`
- `DB_PASSWORD=postgres_password`

*Cache & Queue:*
- `REDIS_HOST=redis` (Docker) or localhost
- `REDIS_PORT=6379`
- `REDIS_PASSWORD=null`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=database` (or redis)

*Email:*
- `RESEND_API_KEY=re_...`
- `MAIL_MAILER=resend` (production) or `log` (development)
- `MAIL_FROM_ADDRESS=hello@example.com`
- `MAIL_FROM_NAME=Aurora`

*AI Services:*
- `CLAUDE_API_KEY=sk-ant-...` (Anthropic)
- `AI_SERVICE_URL=http://ai:8100` (FastAPI service)
- `OLLAMA_BASE_URL=http://host.docker.internal:11434` (Ollama)
- `ONCOKB_API_TOKEN=...` (optional, for genomics)

*Frontend:*
- `VITE_APP_NAME=Aurora`
- `VITE_API_URL=https://aurora.acumenus.net/api` (production)

*Observability (optional):*
- `LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/...`
- `LOG_SLACK_USERNAME=Aurora Bot`

**Secrets Location:**
- Backend: `.env` file (not committed, never checked in)
- CI/CD: GitHub Actions secrets
- Dev: Docker .env file or local .env
- Prod: Environment variables set at deployment

**Lockfiles:**
- `composer.lock` - PHP dependencies frozen
- `package-lock.json` - Frontend dependencies frozen
- `requirements.txt` - Python dependencies pinned by version

## Webhooks & Callbacks

**Incoming Webhooks:**
- Not currently implemented
- Future: ClinVar update notifications, OncoKB changes, Slack integrations

**Outgoing Webhooks:**
- Not currently implemented
- Federation layer (`federation/`) prepared for external integrations

**Event Publishing:**
- Laravel Events: User created, password changed, patient updated
- Broadcasting: Laravel Reverb (WebSocket support available, currently disabled - broadcast_connection=log)
- Queues: Database queue for async jobs, notifications

## Federation Layer

**Purpose:** SSO and cross-instance communication with Parthenon
- Service: `federation/` Python FastAPI service
- Config: `federation/requirements.txt` (FastAPI, cryptography for JWT signing)
- Implementation: JWT validation from external Parthenon instance
- Usage: Allows users from Parthenon SSO to access Aurora without re-login

---

*Integration audit: 2026-03-24*
