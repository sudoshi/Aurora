# Roadmap: Aurora Stabilization & Verification

## Overview

Aurora has a fully-built Patient Genomics Tab feature across all layers (Laravel backend, React/TypeScript frontend, Python FastAPI AI service) but critical bugs block endpoint access and zero automated test coverage exists. This roadmap fixes the blockers, verifies every existing endpoint works, stands up test infrastructure for all three services, writes comprehensive tests layer by layer, completes deferred feature stubs, and validates everything end-to-end with Playwright. The goal: every feature works, and automated tests prove it.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Fix Critical Blocker & Verify Core Endpoints** - Fix database connection alias and verify auth, dashboard, patient, and case endpoints return correct responses (completed 2026-03-25)
- [ ] **Phase 2: Verify Genomics & AI Endpoints** - Verify genomics interactions, stats, and AI briefing endpoints return correct data
- [ ] **Phase 3: Backend Test Infrastructure** - Configure Pest with multi-schema PostgreSQL and create model factories for all clinical models
- [x] **Phase 4: Frontend & AI Test Infrastructure** - Configure Vitest with coverage, set up MSW handlers, configure pytest, and update Playwright (completed 2026-03-25)
- [ ] **Phase 5: Backend Feature Tests** - Write feature tests for all six controllers plus dashboard, reaching 80%+ backend coverage
- [ ] **Phase 6: Backend Unit Tests** - Write unit tests for all service classes (Auth, Patient, Case, Radiogenomics, OncoKb)
- [ ] **Phase 7: Frontend Tests** - Write store, hook, and component tests for auth, genomics, and UI components
- [ ] **Phase 8: AI Service Tests** - Write endpoint and service tests for FastAPI health and genomic briefing
- [ ] **Phase 9: Feature Completion** - Implement OncoKB response parsing, genomics upload endpoints, and criteria endpoints
- [ ] **Phase 10: E2E Tests** - Write Playwright tests for login, patient profile, genomics tab, and case management flows

## Phase Details

### Phase 1: Fix Critical Blocker & Verify Core Endpoints
**Goal**: Every core API endpoint (auth, dashboard, patients, cases) responds correctly without 500 errors
**Depends on**: Nothing (first phase)
**Requirements**: BUG-01, BUG-02, BUG-03, BUG-04, BUG-05, BUG-06, BUG-07
**Success Criteria** (what must be TRUE):
  1. `POST /api/login` with admin@acumenus.net / superuser returns 200 with a Sanctum token
  2. `POST /api/register` with a new email returns success (temp password generated)
  3. `POST /api/change-password` under auth returns 200 and issues new token
  4. `GET /api/dashboard` returns patient domain counts without error
  5. `GET /api/patients` returns patient list; `POST /api/patients` creates a patient; case CRUD works without the `exists:clinical` 500 error
**Plans**: 1 plan

Plans:
- [ ] 01-01: Fix database connection alias and verify core endpoints

### Phase 2: Verify Genomics & AI Endpoints
**Goal**: All genomics and AI service endpoints return meaningful data from seeded records and Ollama
**Depends on**: Phase 1
**Requirements**: BUG-08, BUG-09, BUG-10
**Success Criteria** (what must be TRUE):
  1. `GET /api/genomics/interactions` returns the 42 seeded gene-drug interaction records
  2. `GET /api/genomics/stats` returns variant statistics in expected format
  3. `POST /decision-support/genomic-briefing` on the AI service returns a narrative briefing
**Plans**: 1 plan

Plans:
- [ ] 02-01: Verify genomics and AI service endpoints

### Phase 3: Backend Test Infrastructure
**Goal**: Pest test suite can run against multi-schema PostgreSQL with factories for all models
**Depends on**: Phase 1
**Requirements**: INFRA-01, INFRA-02
**Success Criteria** (what must be TRUE):
  1. Running `php artisan test` executes Pest with DatabaseTruncation across app, clinical, and public schemas
  2. Factories exist for User, Patient, ClinicalCase, GeneDrugInteraction, and GenomicVariant and produce valid model instances
  3. A sample test using factories passes against the test database
**Plans**: 1 plan

Plans:
- [ ] 03-01-PLAN.md — Configure Pest multi-schema and create model factories

### Phase 4: Frontend & AI Test Infrastructure
**Goal**: Vitest, MSW, pytest, and Playwright are all configured and a smoke test passes in each
**Depends on**: Phase 1
**Requirements**: INFRA-03, INFRA-04, INFRA-05, INFRA-06, INFRA-07, INFRA-08
**Success Criteria** (what must be TRUE):
  1. `npx vitest run` executes with coverage output (V8 provider) and jsdom/happy-dom environment
  2. MSW 2.x handlers intercept API calls in test environment and return realistic responses
  3. React test utilities (QueryClient wrapper, Router wrapper, Zustand reset) are available for component tests
  4. `pytest --cov` runs with asyncio_mode=auto and generates coverage output
  5. Playwright config points to correct dev server URL and a skeleton test launches the browser
**Plans**: 2 plans

Plans:
- [ ] 04-01: Configure Vitest, MSW handlers, and React test utilities
- [ ] 04-02: Configure pytest and update Playwright configuration

### Phase 5: Backend Feature Tests
**Goal**: Every API controller has feature tests exercising its endpoints with realistic data
**Depends on**: Phase 3
**Requirements**: BTEST-01, BTEST-02, BTEST-03, BTEST-04, BTEST-05, BTEST-06, BTEST-07, BTEST-13
**Success Criteria** (what must be TRUE):
  1. AuthController tests cover login (valid/invalid), register, change-password, and logout flows
  2. PatientController tests cover CRUD, clinical notes, and timeline endpoints
  3. CaseController tests cover CRUD, archive, and team member management
  4. SessionController, GenomicsController, DashboardController, and RadiogenomicsController each have passing feature tests
  5. Backend test coverage is at or above 80%
**Plans**: 3 plans

Plans:
- [ ] 05-01-PLAN.md � Fix .env.testing, verify AuthController tests, add PatientController and DashboardController tests
- [ ] 05-02-PLAN.md � Feature tests for CaseController and SessionController
- [ ] 05-03-PLAN.md � Feature tests for GenomicsController and RadiogenomicsController, coverage gate

### Phase 6: Backend Unit Tests
**Goal**: All service classes have unit tests validating business logic independently of HTTP layer
**Depends on**: Phase 3
**Requirements**: BTEST-08, BTEST-09, BTEST-10, BTEST-11, BTEST-12
**Success Criteria** (what must be TRUE):
  1. AuthService tests validate login logic, temp password generation, and password change flow
  2. PatientService tests validate domain count aggregation and patient retrieval
  3. CaseService tests validate create, update, archive, and team management logic
  4. RadiogenomicsService tests validate variant classification and panel generation
  5. OncoKbService tests validate connectivity check and response parsing logic
**Plans**: TBD

Plans:
- [ ] 06-01: Unit tests for AuthService and PatientService
- [ ] 06-02: Unit tests for CaseService, RadiogenomicsService, and OncoKbService

### Phase 7: Frontend Tests
**Goal**: Zustand stores, TanStack Query hooks, and all genomics/auth components have passing tests
**Depends on**: Phase 4
**Requirements**: FTEST-01, FTEST-02, FTEST-03, FTEST-04, FTEST-05, FTEST-06, FTEST-07, FTEST-08, FTEST-09, FTEST-10
**Success Criteria** (what must be TRUE):
  1. authStore and profileStore tests validate login/logout state transitions and profile loading
  2. useGenomics hook tests validate data fetching for interactions, briefing, variants, and radiogenomics
  3. Genomics component tests (GenomicBriefing, ActionableVariantsPanel, GenomicVariantTable, TreatmentTimeline, EvidenceBadge) render correctly with mock data
  4. LoginForm and RegisterPage tests validate form submission and validation behavior
  5. Frontend test coverage is at or above 80%
**Plans**: TBD

Plans:
- [ ] 07-01: Store tests for authStore and profileStore
- [ ] 07-02: Hook tests for useGenomics hooks
- [ ] 07-03: Component tests for genomics components
- [ ] 07-04: Component tests for auth components (LoginForm, RegisterPage)

### Phase 8: AI Service Tests
**Goal**: FastAPI health and genomic briefing endpoints have comprehensive tests with mocked Ollama
**Depends on**: Phase 4
**Requirements**: ATEST-01, ATEST-02, ATEST-03, ATEST-04
**Success Criteria** (what must be TRUE):
  1. Health check endpoint test verifies 200 response with expected payload
  2. Genomic briefing endpoint test verifies narrative generation with mocked Ollama responses
  3. Service-level tests validate prompt construction and narrative extraction logic
  4. AI service test coverage is at or above 80%
**Plans**: TBD

Plans:
- [ ] 08-01: AI service endpoint and service tests with mocked Ollama

### Phase 9: Feature Completion
**Goal**: All stub endpoints are fully implemented with real business logic and persistence
**Depends on**: Phase 5, Phase 6
**Requirements**: FEAT-01, FEAT-02, FEAT-03
**Success Criteria** (what must be TRUE):
  1. OncoKbService parses treatment annotations from OncoKB API responses, maps evidence levels, and upserts GeneDrugInteraction records
  2. `POST /api/genomics/uploads` accepts a file, stores it, and `GET /api/genomics/uploads` lists stored uploads
  3. Criteria CRUD endpoints (list, store, update, destroy) persist and retrieve genomic criteria records
**Plans**: TBD

Plans:
- [ ] 09-01: Implement OncoKB response parsing
- [ ] 09-02: Implement genomics upload and criteria endpoints

### Phase 10: E2E Tests
**Goal**: Critical user flows are validated end-to-end through the browser with Playwright
**Depends on**: Phase 7, Phase 9
**Requirements**: E2E-01, E2E-02, E2E-03, E2E-04
**Success Criteria** (what must be TRUE):
  1. Admin can log in at the login page and see the dashboard with patient counts
  2. User can navigate to a patient profile and view demographic, timeline, and clinical tabs
  3. User can open the Genomics tab and see the AI briefing, variant table, interactions, and treatment timeline
  4. User can create a clinical case, add a team member, and view the case detail page
**Plans**: TBD

Plans:
- [ ] 10-01: E2E tests for login and patient profile flows
- [ ] 10-02: E2E tests for genomics tab and case management flows

## Progress

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 7 -> 8 -> 9 -> 10
Note: Phases 3 and 4 can run in parallel (both depend only on Phase 1). Phases 5 and 6 can run in parallel (both depend on Phase 3). Phases 7 and 8 can run in parallel (both depend on Phase 4). Sequential execution per config.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Fix Critical Blocker & Verify Core Endpoints | 1/1 | Complete   | 2026-03-25 |
| 2. Verify Genomics & AI Endpoints | 0/1 | Not started | - |
| 3. Backend Test Infrastructure | 0/1 | Not started | - |
| 4. Frontend & AI Test Infrastructure | 2/2 | Complete   | 2026-03-25 |
| 5. Backend Feature Tests | 0/3 | Not started | - |
| 6. Backend Unit Tests | 0/2 | Not started | - |
| 7. Frontend Tests | 0/4 | Not started | - |
| 8. AI Service Tests | 0/1 | Not started | - |
| 9. Feature Completion | 0/2 | Not started | - |
| 10. E2E Tests | 0/2 | Not started | - |
