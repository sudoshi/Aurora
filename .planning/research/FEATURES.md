# Feature Landscape: Testing & Coverage

**Domain:** Testing infrastructure for clinical collaboration platform
**Researched:** 2026-03-25

## Table Stakes

Features required for the 80%+ coverage target to be meaningful and maintainable.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Unit tests for all services | Services contain business logic; untested services = untested product | Medium | PatientService, EventService, AuthService, GenomicsController |
| Feature tests for all API endpoints | API is the contract between frontend and backend; broken endpoints = broken app | Medium | Auth, Patient, Case, Session, Genomics, Dashboard endpoints |
| Component tests for UI components | UI components are reusable building blocks; regressions break multiple pages | Medium | Modal, DataTable, Button, Toast, Sidebar, TopNavigation |
| Hook tests for TanStack Query hooks | Hooks manage server state; broken hooks = broken data flow | Low | usePatients, useGenomics, useAuth, useCases |
| Store tests for Zustand stores | Stores hold client state; untested mutations = state bugs | Low | authStore, profileStore, uiStore, abbyStore |
| AI endpoint tests | AI service returns clinical data; broken endpoints = no Abby | Low | Health, genomic briefing, therapy matching |
| Coverage thresholds in CI | Without enforcement, coverage degrades over time | Low | 80% minimum, fail CI on drop |
| E2E login flow | Auth is the gateway; if login breaks, nothing works | Low | Login, temp password, change password, logout |

## Differentiators

Features that elevate testing quality beyond basic coverage numbers.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| MSW-based API mocking | Realistic network-level mocking catches integration bugs that vi.mock() misses | Medium | Shared handlers reusable across Vitest and Playwright |
| Database factory completeness | Patient, Case, Event, GeneDrugInteraction factories enable combinatorial testing | Medium | Factories for all clinical models, not just User |
| Parallel test execution (ParaTest) | 2-4x faster backend test suite as tests grow | Low | Install ParaTest, run with --parallel |
| E2E patient profile flow | Validates the core clinical workflow end-to-end | Medium | Navigate to patient, view timeline, labs, notes |
| E2E genomics tab flow | Validates the newest feature, most likely to have regressions | Medium | View variants, drug interactions, AI briefing |
| Unified coverage dashboard (Codecov) | Single view of all three services, trend tracking, PR annotations | Low | Free tier sufficient for private repo |

## Anti-Features

Features to explicitly NOT build during this stabilization milestone.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| Visual regression testing | Requires baseline screenshots, maintenance burden, not needed for stabilization | Component tests with RTL verify behavior, not pixels |
| Performance/load testing | Correctness first, performance optimization is out of scope | Defer to dedicated performance milestone |
| Mutation testing | Slow, complex setup, diminishing returns at 80% coverage | Focus on meaningful tests, not mutation score |
| Snapshot testing | Brittle, generates noise on every UI change, provides false confidence | Use explicit assertions on behavior and content |
| Full browser matrix (Firefox, WebKit) | Chromium-only is sufficient for internal clinical tool | Keep Playwright config with chromium project only |
| Contract testing (Pact) | Overkill for single-team monorepo where backend and frontend deploy together | Feature tests on backend + MSW mocks on frontend cover the contract |
| Test data seeding service | Separate service for test data adds complexity | Use Laravel factories and pytest fixtures directly |

## Feature Dependencies

```
PCOV in Docker -> Backend coverage reports -> CI coverage gate
Vitest config with coverage -> Frontend coverage reports -> CI coverage gate
pytest-cov in requirements -> AI coverage reports -> CI coverage gate
Laravel factories (Patient, Case, Event) -> Feature tests for endpoints
MSW handlers -> Frontend hook tests -> Frontend component tests
E2E login flow -> E2E patient profile flow -> E2E genomics flow
```

## MVP Recommendation

Prioritize (in order):

1. **Backend feature tests for all endpoints** -- fixes the critical 500 error AND proves every route works
2. **Frontend Vitest configuration + setup file** -- unblocks all frontend testing
3. **Frontend hook and store tests** -- highest value-to-effort ratio, covers data flow
4. **AI service endpoint tests** -- small surface area, quick wins
5. **E2E login flow** -- validates the sacred auth system end-to-end
6. **Coverage thresholds in CI** -- locks in gains, prevents regression

Defer:
- Unified Codecov dashboard: nice-to-have, can be added after coverage exists
- ParaTest: only valuable once test suite is large enough to benefit
- E2E genomics flow: depends on bug fixes completing first

## Sources

- Project requirements from `.planning/PROJECT.md`
- Current test state from `.planning/codebase/TESTING.md`
- [Testing Library Best Practices](https://testing-library.com/docs/)
- [MSW Integration Patterns](https://mswjs.io/docs/quick-start/)

---

*Feature landscape: 2026-03-25*
