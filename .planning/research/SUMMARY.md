# Research Summary: Aurora Stabilization & Testing Stack

**Domain:** Testing infrastructure for multi-service clinical collaboration platform
**Researched:** 2026-03-25
**Overall confidence:** HIGH

## Executive Summary

The testing stack for Aurora's Laravel 11 + React 19 + FastAPI monorepo is well-established with mature, stable tooling. Pest 3.8 (already installed) with PCOV for coverage handles the backend. Vitest 3 (already installed) with @vitest/coverage-v8 and MSW 2.x for API mocking handles the frontend. pytest 8.3 (already installed) with pytest-cov 7.1 handles the AI service. Playwright (installed but outdated at 1.49) handles E2E testing.

The critical finding is that most of the testing tools are already installed but not configured or actively used. The frontend has zero test files. The AI service has one test file. The backend has some tests but incomplete endpoint coverage. The gap is not tooling but configuration and test authoring.

Three blockers must be addressed before testing begins: (1) the `clinical` database connection alias issue that causes 500 errors on Case endpoints, (2) PCOV must be installed in the PHP Docker container for coverage reporting, and (3) the Vitest config needs a `test` block added to `vite.config.ts` to enable frontend testing with coverage.

The recommended approach is infrastructure-first: fix blockers, configure all three test runners with coverage thresholds, then write tests layer by layer starting with backend Feature tests (which validate the API contract), followed by frontend store/hook tests, then component tests, and finally E2E.

## Key Findings

**Stack:** Pest 3.8 + PCOV, Vitest 3 + V8 coverage + MSW, pytest 8.3 + pytest-cov 7.1, Playwright 1.58. All standard, all stable.
**Architecture:** Three independent test suites with shared E2E. Each generates coverage in XML. CI aggregates.
**Critical pitfall:** The `exists:clinical.patients,id` validation rule interprets `clinical` as a database connection name (not schema). Must fix before Case endpoint tests can pass.

## Implications for Roadmap

Based on research, suggested phase structure:

1. **Phase 1: Infrastructure & Blockers** - Fix database connection alias, install PCOV in Docker, configure Vitest with coverage, create pytest.ini, set up MSW handlers
   - Addresses: All test runner configuration, coverage output formats
   - Avoids: Every downstream test failing due to missing infrastructure

2. **Phase 2: Backend Tests** - Write Feature tests for all API endpoints, Unit tests for services, complete factories for clinical models
   - Addresses: Auth, Patient, Case, Session, Genomics, Dashboard endpoint coverage
   - Avoids: Schema/connection pitfalls (fixed in Phase 1)

3. **Phase 3: Frontend Tests** - Write store tests, hook tests, component tests using MSW
   - Addresses: Zustand stores, TanStack Query hooks, UI components
   - Avoids: Flaky tests from missing MSW setup (configured in Phase 1)

4. **Phase 4: AI Service Tests** - Write endpoint tests and service tests for FastAPI
   - Addresses: Health, genomic briefing, therapy matching endpoints
   - Avoids: Async testing issues (pytest.ini configured in Phase 1)

5. **Phase 5: E2E & CI Gates** - Write Playwright tests for critical flows, add coverage thresholds to CI
   - Addresses: Login flow, patient profile, genomics tab E2E validation
   - Avoids: E2E against production (Playwright config fixed in Phase 1)

**Phase ordering rationale:**
- Infrastructure must come first because every test suite depends on correct config
- Backend tests come before frontend because MSW handlers should mirror real API responses -- writing backend tests validates what those responses actually look like
- Frontend tests come before E2E because unit/integration tests catch 90% of bugs faster than E2E
- CI gates come last because they enforce coverage that must already exist

**Research flags for phases:**
- Phase 1: Needs careful Docker config work (PCOV installation). LOW risk of research gap.
- Phase 2: Standard Laravel testing. No research needed.
- Phase 3: MSW 2.x setup may need experimentation for TanStack Query wrapper patterns. LOW risk.
- Phase 4: Small surface area, straightforward. No research needed.
- Phase 5: Playwright config is minimal. No research needed.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All tools verified via official docs and recent releases. Versions confirmed current. |
| Features | HIGH | Based on codebase analysis of what exists and what is missing. |
| Architecture | HIGH | Standard three-tier test architecture for monorepos. Well-documented patterns. |
| Pitfalls | HIGH | Database connection issue verified in PROJECT.md. Docker/config pitfalls from community experience. |

## Gaps to Address

- **Exact PCOV Docker installation:** Need to verify PCOV compiles cleanly on `php:8.4-fpm-alpine`. May need `apk add` build dependencies.
- **MSW 2.x + React 19 compatibility:** HIGH confidence this works (MSW is framework-agnostic), but no explicit React 19 verification found. Test early.
- **Codecov integration:** Deferred to after tests exist. No immediate research needed, but will need a `codecov.yml` config file when ready.
- **Factory completeness:** Need to audit which Laravel factories exist for clinical models (Patient, ClinicalPatient, Visit, Medication, Condition, etc.) vs which need to be created.
