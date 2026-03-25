# Requirements: Aurora Stabilization & Verification

**Defined:** 2026-03-25
**Core Value:** Every existing feature works end-to-end with automated tests proving it

## v1 Requirements

### Bug Fixes

- [x] **BUG-01**: Add `clinical` database connection alias to `config/database.php` so `exists:clinical.patients,id` validation resolves
- [x] **BUG-02**: Verify `/api/login` returns 200 with valid credentials after DB fix
- [x] **BUG-03**: Verify `/api/register` returns success response for new email
- [x] **BUG-04**: Verify `/api/change-password` works under auth
- [x] **BUG-05**: Verify `/api/dashboard` returns patient counts without error
- [x] **BUG-06**: Verify `/api/patients` CRUD endpoints respond correctly
- [x] **BUG-07**: Verify `/api/cases` CRUD endpoints respond correctly (the validation fix target)
- [x] **BUG-08**: Verify `/api/genomics/interactions` returns seeded gene-drug data
- [x] **BUG-09**: Verify `/api/genomics/stats` returns variant statistics
- [x] **BUG-10**: Verify AI service `/decision-support/genomic-briefing` endpoint responds

### Test Infrastructure

- [x] **INFRA-01**: Configure Pest with multi-schema PostgreSQL support (DatabaseTruncation or custom)
- [x] **INFRA-02**: Create Laravel model factories for User, Patient, ClinicalCase, GeneDrugInteraction, GenomicVariant
- [x] **INFRA-03**: Configure Vitest with coverage in `vite.config.ts` (test block, jsdom/happy-dom)
- [x] **INFRA-04**: Set up MSW 2.x handlers mirroring real API responses
- [x] **INFRA-05**: Create React test utilities (provider wrappers for QueryClient, Router, Zustand)
- [x] **INFRA-06**: Configure pytest with coverage and `asyncio_mode = auto`
- [x] **INFRA-07**: Create FastAPI test client fixtures with mocked Ollama
- [x] **INFRA-08**: Update Playwright configuration for current app state

### Backend Tests

- [x] **BTEST-01**: Feature tests for AuthController (login, register, change-password, logout)
- [x] **BTEST-02**: Feature tests for PatientController (index, show, store, update, clinical notes, timeline)
- [x] **BTEST-03**: Feature tests for CaseController (index, store, show, update, destroy, team members)
- [x] **BTEST-04**: Feature tests for SessionController (index, store, show, update, cases)
- [x] **BTEST-05**: Feature tests for GenomicsController (stats, interactions, variants, uploads, criteria)
- [x] **BTEST-06**: Feature tests for DashboardController (index with patient counts)
- [x] **BTEST-07**: Feature tests for RadiogenomicsController (panels, gene-drug interactions)
- [x] **BTEST-08**: Unit tests for AuthService (login, register, password change logic)
- [x] **BTEST-09**: Unit tests for PatientService (domain count aggregation, patient retrieval)
- [x] **BTEST-10**: Unit tests for CaseService (create, update, archive, team management)
- [x] **BTEST-11**: Unit tests for RadiogenomicsService (variant classification, panel generation)
- [x] **BTEST-12**: Unit tests for OncoKbService (connectivity check, response parsing)
- [x] **BTEST-13**: Backend test coverage reaches 80%+

### Frontend Tests

- [ ] **FTEST-01**: Store tests for authStore (login, logout, token management)
- [ ] **FTEST-02**: Store tests for profileStore (profile loading, updates)
- [ ] **FTEST-03**: Hook tests for useGenomics hooks (useInteractions, useBriefing, useVariants, useRadiogenomics)
- [ ] **FTEST-04**: Component tests for GenomicBriefing (renders briefing, handles loading/error)
- [ ] **FTEST-05**: Component tests for ActionableVariantsPanel (renders variants, VUS accordion)
- [ ] **FTEST-06**: Component tests for GenomicVariantTable (filtering, sorting, search, expansion)
- [ ] **FTEST-07**: Component tests for TreatmentTimeline (renders drug exposures proportionally)
- [ ] **FTEST-08**: Component tests for EvidenceBadge (renders correct badge for evidence level)
- [ ] **FTEST-09**: Component tests for LoginForm and RegisterPage (form submission, validation)
- [ ] **FTEST-10**: Frontend test coverage reaches 80%+

### AI Service Tests

- [ ] **ATEST-01**: Endpoint tests for health check
- [ ] **ATEST-02**: Endpoint tests for POST /decision-support/genomic-briefing
- [ ] **ATEST-03**: Service tests for genomic_briefing.py (narrative generation with mocked Ollama)
- [ ] **ATEST-04**: AI service test coverage reaches 80%+

### E2E Tests

- [ ] **E2E-01**: Login flow — admin logs in, sees dashboard
- [ ] **E2E-02**: Patient profile — navigate to patient, view tabs
- [ ] **E2E-03**: Genomics tab — view briefing, variants, interactions, timeline
- [ ] **E2E-04**: Case management — create case, add team member, view case

### Feature Completion

- [ ] **FEAT-01**: OncoKB response parsing in OncoKbService (parse treatment annotations, map evidence levels, upsert GeneDrugInteraction records)
- [ ] **FEAT-02**: GenomicsController upload endpoints (listUploads, storeUpload, showUpload with file handling)
- [ ] **FEAT-03**: GenomicsController criteria endpoints (listCriteria, storeCriterion, updateCriterion, destroyCriterion with persistence)

## v2 Requirements

### CI/CD Integration

- **CI-01**: Coverage threshold enforcement in GitHub Actions
- **CI-02**: Codecov integration with codecov.yml config
- **CI-03**: Test result reporting in PR checks

### Performance Testing

- **PERF-01**: Load testing for genomics endpoints
- **PERF-02**: Response time benchmarks for critical paths

## Out of Scope

| Feature | Reason |
|---------|--------|
| New feature development | Stabilization milestone only |
| Federation layer | Off by default, future milestone |
| WebSocket/real-time testing | Not in current scope |
| Mobile optimization | Web-first |
| HIPAA compliance audit | Separate compliance milestone |
| Docker PCOV installation | Coverage runs locally, CI deferred to v2 |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| BUG-01 | Phase 1 | Complete |
| BUG-02 | Phase 1 | Complete |
| BUG-03 | Phase 1 | Complete |
| BUG-04 | Phase 1 | Complete |
| BUG-05 | Phase 1 | Complete |
| BUG-06 | Phase 1 | Complete |
| BUG-07 | Phase 1 | Complete |
| BUG-08 | Phase 2 | Complete |
| BUG-09 | Phase 2 | Complete |
| BUG-10 | Phase 2 | Complete |
| INFRA-01 | Phase 3 | Complete |
| INFRA-02 | Phase 3 | Complete |
| INFRA-03 | Phase 4 | Complete |
| INFRA-04 | Phase 4 | Complete |
| INFRA-05 | Phase 4 | Complete |
| INFRA-06 | Phase 4 | Complete |
| INFRA-07 | Phase 4 | Complete |
| INFRA-08 | Phase 4 | Complete |
| BTEST-01 | Phase 5 | Complete |
| BTEST-02 | Phase 5 | Complete |
| BTEST-03 | Phase 5 | Complete |
| BTEST-04 | Phase 5 | Complete |
| BTEST-05 | Phase 5 | Complete |
| BTEST-06 | Phase 5 | Complete |
| BTEST-07 | Phase 5 | Complete |
| BTEST-08 | Phase 6 | Complete |
| BTEST-09 | Phase 6 | Complete |
| BTEST-10 | Phase 6 | Complete |
| BTEST-11 | Phase 6 | Complete |
| BTEST-12 | Phase 6 | Complete |
| BTEST-13 | Phase 5 | Complete |
| FTEST-01 | Phase 7 | Pending |
| FTEST-02 | Phase 7 | Pending |
| FTEST-03 | Phase 7 | Pending |
| FTEST-04 | Phase 7 | Pending |
| FTEST-05 | Phase 7 | Pending |
| FTEST-06 | Phase 7 | Pending |
| FTEST-07 | Phase 7 | Pending |
| FTEST-08 | Phase 7 | Pending |
| FTEST-09 | Phase 7 | Pending |
| FTEST-10 | Phase 7 | Pending |
| ATEST-01 | Phase 8 | Pending |
| ATEST-02 | Phase 8 | Pending |
| ATEST-03 | Phase 8 | Pending |
| ATEST-04 | Phase 8 | Pending |
| FEAT-01 | Phase 9 | Pending |
| FEAT-02 | Phase 9 | Pending |
| FEAT-03 | Phase 9 | Pending |
| E2E-01 | Phase 10 | Pending |
| E2E-02 | Phase 10 | Pending |
| E2E-03 | Phase 10 | Pending |
| E2E-04 | Phase 10 | Pending |

**Coverage:**
- v1 requirements: 52 total
- Mapped to phases: 52
- Unmapped: 0

---
*Requirements defined: 2026-03-25*
*Last updated: 2026-03-25 after roadmap creation*
