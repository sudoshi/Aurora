# Aurora

**Advanced Clinical Case Intelligence Platform**

Aurora is a secure collaboration platform for multidisciplinary clinical teams to coordinate complex patient care. It combines clinical data aggregation, AI-powered decision support (Abby), collaboration sessions, and structured decision capture into a single unified workspace.

Built by [Acumenus](https://acumenus.net). Live at [aurora.acumenus.net](https://aurora.acumenus.net).

---

## What Aurora Does

Aurora enables clinical teams to:

- **Review complex cases together** — oncology tumor boards, surgical planning, rare disease diagnostic odysseys, complex medical reviews
- **View complete patient profiles** — demographics, conditions, medications, labs, imaging, genomics, clinical notes, and visit timelines in one place
- **Make structured decisions** — propose recommendations, vote, finalize, and track follow-ups with full audit trails
- **Get AI-powered insights** — Abby provides clinical trial matching, guideline concordance checking, drug interaction alerts, genomic variant interpretation, prognostic scoring, and "Patients Like This" similarity search
- **Collaborate as a team** — Commons channels with threaded discussions, wiki, announcements, and presence-oriented UI; durable realtime transport is still on the hardening roadmap

## Architecture

```
aurora/
├── backend/          Laravel 12 / PHP 8.4+ — API, auth, business logic
├── frontend/         React 19 / TypeScript / Tailwind 4 — SPA
├── ai/               Python FastAPI — Abby AI, similarity engine, clinical NLP
├── federation/       Python FastAPI — cross-institutional relay (opt-in)
├── e2e/              Playwright — end-to-end test suite
└── docker/           Dockerfiles + nginx config for containerized deployment
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 12, PHP 8.4+, Sanctum auth, Spatie RBAC |
| **Frontend** | React 19, TypeScript (strict), Vite 6, Tailwind 4, Zustand, TanStack Query |
| **AI Service** | Python 3.12 container / Python 3.13 CI, FastAPI, SapBERT, Ollama/MedGemma, Claude API |
| **Database** | PostgreSQL 16 + pgvector |
| **Cache/Queue** | Redis |
| **Search** | pgvector cosine similarity, full-text search |
| **Deployment** | Docker Compose or native Apache/Nginx |

## Features

### Case Management
- Create and manage clinical cases across 4 specialties (oncology, surgical, rare disease, complex medical)
- Specialty workflow templates with pre-configured data tabs, decision types, and guideline sets
- Team member assignment with role-based permissions (presenter, reviewer, observer)
- Threaded case discussions with attachments
- Domain-specific annotations anchored to clinical data points

### Live Collaboration Sessions
- Schedule and run tumor boards, MDC meetings, surgical planning, grand rounds
- Session agenda with case ordering, presenter assignment, and time allocation
- Start/end lifecycle with participant tracking
- Per-case and overall session management

### Decision Capture
- Structured decision proposals with recommendation text and rationale
- Team voting (agree/disagree/abstain) with comments
- Decision finalization with audit trail
- Follow-up task assignment and tracking

### Patient Profiles
- Demographics, conditions, medications, procedures, observations
- Era timelines (condition and drug eras)
- Lab results with reference ranges
- Clinical notes (paginated)
- Imaging studies with measurements and response assessments
- Genomic variants with ClinVar classification and actionable gene identification
- "Patients Like This" similarity search powered by pgvector embeddings

### Abby AI (Clinical Intelligence)
- **Copilot Chat** — contextual clinical Q&A with streaming responses
- **Patient Summarization** — structured summaries with key findings
- **Session Notes** — auto-generated clinical notes (SOAP, narrative, brief)
- **Case Briefs** — presentation-ready briefs for tumor boards, MDR, handoffs
- **Clinical Trial Matching** — eligibility-based trial suggestions
- **Guideline Concordance** — evaluate recommendations against clinical guidelines
- **Drug Interaction Checking** — identify drug-drug interactions
- **Genomic Variant Interpretation** — AMP/ASCO/CAP classification
- **Prognostic Scoring** — ECOG, Charlson Comorbidity Index, risk stratification
- **Rare Disease Matching** — phenotype-based differential diagnosis
- **Clinical NLP** — entity extraction with negation detection

### Similarity Engine ("Patients Like This")
- Patient embeddings via SapBERT (768-dim) stored in pgvector
- Multi-domain re-ranking: diagnosis (0.30), genomics (0.25), treatment (0.20), labs (0.15), demographics (0.10)
- Federated search across institutions (opt-in, de-identified)

### Commons (Team Collaboration)
- Topic and announcement channels
- Threaded messages with reactions, pins, attachments
- Wiki pages for institutional knowledge
- Activity feeds and notifications
- Online presence indicators

### Administration
- User management with role-based access (super-admin, admin, analyst, clinician, viewer)
- AI provider configuration (OpenAI, Anthropic, Ollama)
- System health monitoring (database, cache, queue, AI service)
- User audit logging with activity tracking
- App settings management

### Imaging
- Study browser with modality and body site filtering
- Measurement tracking with longitudinal trends
- Response assessment (RECIST 1.1, Lugano, Deauville, RANO)
- AI-powered segmentation and volumetric analysis
- Radiogenomics / precision medicine integration

### Federation (Opt-in)
- mTLS-authenticated peer-to-peer relay
- De-identified federated "Patients Like This" queries
- k-anonymity enforcement (minimum 5 patients)
- Institution registry with capability negotiation

## Quick Start

### Prerequisites
- PHP 8.4+, Composer
- Node.js 22+, npm
- PostgreSQL 16 (with pgvector extension)
- Redis
- Python 3.12 via the `aurora-ai:dev` container for AI service tests. CI also runs on Python 3.13; host Python 3.14 is not supported by the pinned AI dependency set.

### Local Development

```bash
# Clone
git clone https://github.com/AcumenusAI/Aurora.git
cd Aurora

# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
cd ..

# Frontend
cd frontend
npm install
npm run dev    # Dev server on :5177
cd ..

# AI Service (optional)
cd ai
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --port 8100
cd ..
```

### Docker

```bash
# Production-like static frontend stack
cd frontend && npm ci && npm run build
rm -rf ../backend/public/build
mkdir -p ../backend/public/build
cp -a dist/. ../backend/public/build/
cd ..
docker compose up -d

# Local Vite HMR stack
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev up -d
```

See [docs/deployment/](docs/deployment/) for full setup guide.

## Key URLs (Development)

| Service | URL |
|---------|-----|
| App (via nginx/Apache) | http://localhost:8085 |
| Vite dev server | http://localhost:5177 |
| AI service | http://localhost:8100 |
| Federation relay | http://localhost:8200 |
| PostgreSQL | localhost:5485 |

## API Overview

~100+ REST endpoints organized by domain:

| Domain | Prefix | Description |
|--------|--------|-------------|
| Auth | `/api/auth/*` | Login, register, password change, logout |
| Cases | `/api/cases/*` | CRUD + team, discussions, annotations, documents |
| Sessions | `/api/sessions/*` | CRUD + lifecycle, cases, participants |
| Decisions | `/api/decisions/*` | Propose, vote, finalize, follow-ups |
| Patients | `/api/patients/*` | Clinical data via adapter pattern |
| Imaging | `/api/imaging/*` | Studies, measurements, response assessments |
| Commons | `/api/commons/*` | Channels, messages, wiki, notifications |
| Admin | `/api/admin/*` | Users, roles, AI providers, health, audit |
| Dashboard | `/api/dashboard/*` | Unified stats |
| AI | `/api/ai/*` | Abby chat, similarity, copilot, decision support, NLP, imaging |
| Federation | `/federation/*` | Peer registry, queries, similarity |

See [docs/api/](docs/api/) for full endpoint reference.

## Testing

```bash
# Backend (Pest against local PostgreSQL aurora_test)
cd backend
DB_PASSWORD="$(awk -F= '/^DB_PASSWORD=/{print substr($0,index($0,"=")+1)}' .env)" \
  APP_ENV=testing DB_CONNECTION=pgsql DB_HOST=localhost DB_PORT=5432 \
  DB_DATABASE=aurora_test DB_USERNAME=smudoshi \
  DB_MIGRATIONS_TABLE=public.migrations \
  ./vendor/bin/pest --exclude-group=mockery-alias

# Frontend (Vitest)
cd frontend && npm test

# AI (pytest in the supported Python 3.12 container)
docker build -t aurora-ai:dev ai
docker run --rm aurora-ai:dev python -m pytest

# E2E (Playwright)
cd e2e && npx playwright test --project=chromium
```

## Security

- Sanctum token-based authentication with forced password change flow
- Spatie RBAC with granular permissions
- CSP headers, HSTS, X-Frame-Options
- Rate limiting on public auth/upload endpoints and authenticated API traffic
  keyed by user ID
- PHI sanitization before cloud LLM routing
- Encrypted fields for sensitive configuration
- Activity logging for supported clinical and administrative workflows
- Environment-based secret configuration is the target; remaining hardcoded service credentials are tracked for hardening

## Documentation

- [Deployment Guide](docs/deployment/)
- [API Reference](docs/api/)
- [Federation Setup](docs/federation/)
- [V2 Design Document](docs/plans/2026-03-09-aurora-v2-complete-overhaul-design.md)
- [Implementation Plan](docs/plans/2026-03-09-aurora-v2-implementation-plan.md)

## License

Proprietary. Copyright 2026 Acumenus, Inc. All rights reserved.
