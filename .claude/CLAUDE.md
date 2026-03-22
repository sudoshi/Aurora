# Aurora -- Project Context for Claude Code

## What This Project Is

Aurora is a secure, real-time collaboration platform for multidisciplinary clinical teams to coordinate patient care. It provides synchronous collaboration (video conferencing, whiteboarding), asynchronous communication (threaded discussions, file sharing), clinical decision support, and team management with role-based access control.

## Tech Stack

- **Backend:** Laravel 10, PHP 8.1+, Sanctum auth, Spatie RBAC
- **Frontend:** React, TypeScript, Vite, Tailwind CSS, Zustand state, TanStack Query
- **AI Service:** Python, FastAPI
- **Database:** PostgreSQL 16 (Docker), Redis
- **Real-time:** Laravel WebSockets
- **Video:** Agora.io SDK
- **Infrastructure:** Docker Compose (nginx, php, node, postgres, redis), deploy.sh

## Project Structure

```
Root files:
  CLAUDE.md              -- This file (also at .claude/CLAUDE.md)
  docker-compose.yml     -- All Docker service definitions
  deploy.sh              -- Production deployment script
  Makefile               -- Top-level shortcuts

Application code:
  backend/               -- Laravel PHP application
    app/
      Contracts/         -- Interfaces (ClinicalDataAdapter)
      Http/
        Controllers/     -- AuthController, PatientController, EventController, CaseDiscussionController
        Requests/        -- Form Request validation classes
        Middleware/       -- SecurityHeaders
        Helpers/         -- ApiResponse helper
      Models/            -- User, Patient, Event, ClinicalCase, CaseDiscussion
        Clinical/        -- ClinicalPatient, Visit, Medication, Condition, Measurement, etc.
      Services/          -- Business logic
        Adapters/        -- FhirAdapter, OmopAdapter, ManualAdapter
      Providers/         -- AppServiceProvider, RouteServiceProvider
    routes/api.php       -- All API routes
    database/migrations/ -- Schema definitions
    config/              -- Laravel config

  frontend/              -- React + TypeScript SPA
    src/
      features/          -- Feature modules
        auth/            -- Login, Register, ChangePasswordModal
        patient-profile/ -- Patient demographics, timeline, labs, notes, visits
        administration/  -- Admin user management API
        settings/        -- Profile & notification preferences
        commons/         -- Shared types
      components/
        ui/              -- Reusable UI components (Button, Modal, DataTable, Toast, etc.)
        layout/          -- Sidebar
        navigation/      -- TopNavigation
        layouts/         -- DashboardLayout
      hooks/             -- useAbbyContext
      lib/               -- API client (Axios), query client, utils
      stores/            -- Zustand stores (auth, profile, ui, abby)

  ai/                    -- Python FastAPI AI service
    app/                 -- FastAPI application

  docker/                -- Dockerfiles and container configs
  e2e/                   -- Playwright end-to-end tests
  federation/            -- Federation layer

  docs/
    plans/               -- Implementation plans (v2 overhaul design & implementation)
    notes/               -- Market research notes
```

## Key Patterns

### Backend (Laravel)
- Use **Form Requests** for validation (StoreDiscussionRequest, StoreEventRequest, etc.)
- Use **Service classes** for business logic (PatientService, EventService, AuthService)
- **Adapter pattern** for clinical data: ClinicalDataAdapter interface with FHIR, OMOP, and Manual implementations
- **ApiResponse helper** for consistent JSON responses
- Return types on all public controller methods

### Frontend (React)
- API calls go through **TanStack Query** hooks
- State management via **Zustand** stores (authStore, profileStore, uiStore, abbyStore)
- Feature-based directory structure under `src/features/`
- Shared UI components under `src/components/ui/`

### Authentication
- Sanctum token-based auth
- Temp password flow: register with email only, receive temp password via Resend, forced password change on first login
- See `.claude/rules/auth-system.md` for CRITICAL auth rules -- DO NOT modify auth without reading that file

## Docker Services

```bash
docker compose up -d                  # Start all services
docker compose ps                     # Check health
```

Services: nginx (:8085), php, node (:5177 dev), postgres (:5485), redis

## Key URLs (Development)

- App: http://localhost:8085
- Vite dev server: http://localhost:5177
- Database: localhost:5485 (aurora/aurora)

## Project Memory (Aurora Brain)

This project has a persistent knowledge base stored in ChromaDB, accessible via
the `claude-devbrain` MCP server. It contains project documentation, design plans,
market research, and source code indexed for semantic search.

### CRITICAL: Always Query Before Working

**Before starting any task**, query the Aurora Brain to recall relevant context:

1. **At the start of every session**, use the Chroma MCP tools to search for
   context related to the current task. Search the `aurora_docs` collection
   for documentation and plans, and `aurora_code` for implementation details.

2. **Before making architectural decisions**, search for prior design decisions
   and plans. The v2 overhaul design and implementation plan contain detailed
   specifications.

3. **Before writing new code**, check if similar patterns already exist in the
   codebase via the `aurora_code` collection.

### How to Query

Use the Chroma MCP tools (available as `claude-devbrain` in your MCP server list):

- `chroma_query_documents` -- Semantic search across collections
  - Collection `aurora_docs`: ~99 chunks from documentation, plans, market notes
  - Collection `aurora_code`: ~727 chunks from PHP, TypeScript, Python source

- Filter by metadata when narrowing scope:
  - `doc_type`: documentation, planning, notes
  - `extension`: .php, .ts, .tsx, .py, .sql
  - `relative_path`: filter by directory (e.g., "backend/app/Services")

### Example Queries

- "How does Aurora handle clinical data adapters?"
- "What is the patient profile timeline implementation?"
- "Authentication flow and password change"
- "FHIR adapter data mapping"
- "Admin user management API endpoints"
- "UI component patterns" (use `aurora_code` collection)
- "Clinical data models and relationships" (use `aurora_code` collection)

### Brain Updates

For manual updates or to re-index after significant changes:

```bash
# Incremental docs only (fast -- skips unchanged files)
python3 ~/.claude-devbrain/ingest.py -s /home/smudoshi/Github/Aurora --collection aurora_docs --code-collection aurora_code -i

# Full re-index with code
python3 ~/.claude-devbrain/ingest.py -s /home/smudoshi/Github/Aurora --collection aurora_docs --code-collection aurora_code --include-code
```
