# Aurora - Clinical Case Intelligence Platform

Aurora is a multi-disciplinary clinical case management platform designed for tumor boards, surgical reviews, rare disease panels, and complex medical case conferences. It combines real-time collaboration, AI-assisted analysis (powered by Abby), and federated data sharing across institutions.

## Architecture

```
                           +------------------+
                           |   Browser / SPA  |
                           |  React 19 + TS   |
                           +--------+---------+
                                    |
                          HTTPS / WSS (Reverb)
                                    |
                    +---------------+---------------+
                    |         Apache Reverse Proxy   |
                    +------+--------+--------+------+
                           |        |        |
              +------------+   +----+----+   +----------+
              | Laravel 11 |   | FastAPI |   | Meilisearch|
              |  (PHP 8.4) |   | (Abby) |   |  (Search)  |
              +------+-----+   +----+----+   +----------+
                     |              |
              +------+------+      |
              | PostgreSQL  |------+
              | 16 + pgvector|
              +-------------+
```

### Service Layout (Monorepo)

```
Aurora/
  backend/        Laravel 11 API + Sanctum auth
  frontend/       React 19 SPA (Vite 6, Tailwind 4, Zustand)
  ai/             FastAPI service for Abby AI assistant
  federation/     Cross-institution federated query layer
  e2e/            Playwright end-to-end tests
  docs/           Documentation
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend API | Laravel 11 / PHP 8.4 / Sanctum |
| Frontend SPA | React 19 / TypeScript (strict) / Tailwind 4 |
| State Management | Zustand + TanStack Query |
| AI Service | Python 3.13 / FastAPI / Ollama |
| Database | PostgreSQL 16 + pgvector |
| WebSockets | Laravel Reverb |
| Full-Text Search | Meilisearch |
| Testing | Pest (PHP) / Vitest (JS) / pytest (Python) / Playwright (E2E) |
| CI/CD | GitHub Actions |
| Deployment | Docker Compose or bare-metal Apache |

## Key Features

- **Clinical Case Management** -- Create, assign, and track multi-disciplinary clinical cases with team-based workflows (tumor board, surgical review, rare disease, complex medical).
- **Live Session Coordination** -- Schedule and run real-time sessions with case queues, participant roles, and live status transitions.
- **Decision Tracking** -- Propose, vote on, and finalize clinical decisions with audit trails and follow-up task management.
- **Patient Profile & Clinical Data** -- Unified patient view with conditions, medications, procedures, imaging, genomics, measurements, and observations.
- **Abby AI Assistant** -- Conversational AI powered by Ollama for clinical summarization, literature search, and decision support.
- **Commons Workspace** -- Channels, direct messages, threads, file attachments, wiki, announcements, and review requests.
- **Imaging Viewer** -- DICOM study browser with RECIST measurements and response assessments.
- **Role-Based Access Control** -- Granular permissions via Spatie roles/permissions with admin panel.
- **Federated Queries** -- Opt-in cross-institution data sharing with certificate-based peer authentication.
- **Audit Logging** -- Comprehensive user activity tracking for compliance.

## Quick Start (Local Development)

```bash
# Prerequisites: PHP 8.4, Composer, Node 22, pnpm, Python 3.13, PostgreSQL 16, Ollama

# 1. Clone and enter the repo
git clone git@github.com:acumenus/Aurora.git && cd Aurora

# 2. Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve

# 3. Frontend (new terminal)
cd frontend
pnpm install
pnpm dev

# 4. AI service (new terminal)
cd ai
python -m venv .venv && source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload --port 8100
```

Open http://localhost:5173 in your browser. Login with `admin@acumenus.net` / `superuser`.

## Docker Deployment

```bash
docker compose up -d
```

See [docs/deployment/README.md](deployment/README.md) for full deployment instructions.

## API Overview

All endpoints are prefixed with `/api`. Authentication uses Laravel Sanctum bearer tokens.

| Domain | Prefix | Description |
|--------|--------|-------------|
| Auth | `/auth` | Register, login, logout, change password |
| Dashboard | `/dashboard` | Aggregated stats |
| Cases | `/cases` | CRUD, team management, discussions, annotations, documents |
| Sessions | `/sessions` | CRUD, start/end, case queue, participants |
| Decisions | `/cases/{id}/decisions` | Propose, vote, finalize, follow-ups |
| Patients | `/patients` | Search, profile, stats, imaging |
| AI Proxy | `/ai` | Forward requests to FastAPI |
| Abby | `/abby` | Conversation CRUD, chat |
| Commons | `/commons` | Channels, messages, DMs, wiki, announcements |
| Admin | `/admin` | Users, roles, AI providers, system health |

See [docs/api/README.md](api/README.md) for endpoint details.

## Screenshots

> Screenshots will be added as the UI stabilizes.

## License

Proprietary - Acumenus LLC. All rights reserved.
