# Aurora — Stabilization & Verification

## What This Is

Aurora is a secure, real-time collaboration platform for multidisciplinary clinical teams. A comprehensive Patient Genomics Tab feature was just built across all layers (Laravel backend, React/TypeScript frontend, Python FastAPI AI service). This milestone focuses on fixing critical bugs, completing deferred implementations, and achieving automated test coverage across the entire platform.

## Core Value

Every existing feature — auth, patients, cases, genomics, AI briefing — must work end-to-end with automated tests proving it. No regressions, no 500 errors, no dead endpoints.

## Requirements

### Validated

- ✓ Monorepo structure (backend/, frontend/, ai/, federation/, e2e/) — existing
- ✓ Docker Compose services (nginx, php, node, redis) — existing
- ✓ PostgreSQL with app/clinical/public schemas — existing
- ✓ GeneDrugInteraction model + migration + seeder (42 records) — existing
- ✓ GenomicsController with interactions endpoint — existing
- ✓ Genomic briefing AI service (Ollama-powered) — existing
- ✓ Frontend Genomics tab with 7 components — existing
- ✓ TanStack Query hooks for genomics — existing
- ✓ Auth system (Sanctum, temp password, Resend email) — existing

### Active

- [ ] Fix critical 500 error: add `clinical` database connection to config/database.php
- [ ] Fix CaseController validation rules referencing non-existent connection
- [ ] Verify all auth endpoints work (login, register, change-password, logout)
- [ ] Verify dashboard endpoint loads patient counts
- [ ] Verify patient CRUD and profile endpoints
- [ ] Verify case management endpoints (create, update, archive, team members)
- [ ] Verify session management endpoints
- [ ] Verify genomics interactions API returns seeded data
- [ ] Verify genomics stats endpoint
- [ ] Verify AI genomic briefing generation (Ollama)
- [ ] Verify radiogenomics panel endpoint
- [ ] Complete OncoKB response parsing in OncoKbService
- [ ] Implement GenomicsController upload endpoints (listUploads, storeUpload, showUpload)
- [ ] Implement GenomicsController criteria endpoints (listCriteria, storeCriterion, updateCriterion, destroyCriterion)
- [ ] Automated backend tests (Pest) — 80%+ coverage for controllers and services
- [ ] Automated frontend tests (Vitest) — 80%+ coverage for hooks and components
- [ ] Automated AI service tests (pytest) — 80%+ coverage for endpoints and services
- [ ] E2E tests (Playwright) — login flow, patient profile, genomics tab

### Out of Scope

- New feature development — stabilization only
- Federation layer — off by default, future milestone
- WebSocket/real-time features — not in current scope
- Mobile optimization — web-first
- Performance optimization — correctness first
- CI/CD pipeline changes — existing GitHub Actions sufficient

## Context

- Branch: `v2/phase-0-scaffold` with 14 genomics commits
- Critical blocker: `exists:clinical.patients,id` validation in CaseController interpreted by Laravel as connection `clinical` (not schema) — connection doesn't exist in database.php
- PostgreSQL search_path is `app,clinical,public` on the `pgsql` connection
- All 72 tables exist in the Aurora database on host PostgreSQL (port 5432)
- Tinker confirms auth, models, and token generation all work — the 500 is a request-level issue
- OncoKB service has connectivity check but no response parsing (explicit TODO)
- GenomicsController has 7 stub endpoints returning empty responses
- Codebase map: `.planning/codebase/` (7 documents, 2,217 lines)

## Constraints

- **Auth system**: Sacred — see `.claude/rules/auth-system.md`, no modifications to auth flow
- **Tech stack**: Laravel 11 / React 19 / FastAPI — no changes
- **Database**: PostgreSQL on host (not Docker), connection via host.docker.internal
- **Testing**: Pest (PHP), Vitest (JS), pytest (Python), Playwright (E2E) — 80%+ coverage target
- **Deployment**: Must deploy to aurora.acumenus.net and verify after completion
- **Credentials**: admin@acumenus.net / superuser (must_change_password: false)

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Add `clinical` connection alias to database.php | Laravel interprets `exists:clinical.X` as connection name, not schema | — Pending |
| Fix validation rules vs add connection | Adding connection is simpler and preserves schema-qualified model tables | — Pending |
| Test all layers before new features | Can't build on broken foundation | — Pending |

---
*Last updated: 2026-03-25 after initialization*
