---
phase: 10-e2e-tests
plan: 01
subsystem: testing
tags: [playwright, e2e, login, patient-profile, storageState]

requires:
  - phase: 04-frontend-ai-test-infrastructure
    provides: Playwright installation and initial smoke tests
provides:
  - Rewritten auth E2E tests (login, invalid credentials, create account link)
  - Rewritten patient-profile E2E tests (list, detail tabs, view mode switching)
  - Playwright storageState auth setup reducing API rate-limit pressure
affects: []

tech-stack:
  added: []
  patterns: [Playwright storageState auth setup, project-based test splitting]

key-files:
  created:
    - e2e/tests/auth.setup.ts
    - e2e/.gitignore
  modified:
    - e2e/tests/auth.spec.ts
    - e2e/tests/patient-profile.spec.ts
    - e2e/playwright.config.ts

key-decisions:
  - "storageState auth setup to share login across patient-profile tests, avoiding throttle:5,1 rate limit exhaustion"
  - "Three Playwright projects (setup, auth-tests, chromium) to separate auth-testing from authenticated-tests"
  - "Single worker + no parallel to avoid rate-limit flakiness against deployed app"

patterns-established:
  - "Auth setup pattern: auth.setup.ts logs in once, saves storageState for reuse by chromium project"
  - "Auth tests run without storageState (they test login itself); other tests depend on setup project"

requirements-completed: [E2E-01, E2E-02]

duration: 15min
completed: 2026-03-25
---

# Phase 10 Plan 01: E2E Login and Patient Profile Tests Summary

**Playwright E2E tests for login flow (3 tests) and patient profile navigation (3 tests) using v2 selectors with storageState auth sharing**

## Performance

- **Duration:** 15 min
- **Started:** 2026-03-25T21:41:44Z
- **Completed:** 2026-03-25T21:57:25Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- Rewrote auth.spec.ts with 3 tests: admin login to dashboard, invalid credentials error, create account link
- Rewrote patient-profile.spec.ts with 3 tests: patient list table, profile detail with tabs, view mode switching
- Added storageState auth setup to share login across tests and avoid API rate-limit exhaustion
- All 7 tests (1 setup + 3 auth + 3 patient) pass against aurora.acumenus.net

## Task Commits

Each task was committed atomically:

1. **Task 1: Rewrite auth.spec.ts for v2 login flow** - `b6b3170` (feat)
2. **Task 2: Rewrite patient-profile.spec.ts for v2 patient navigation** - `0fac4c2` (feat)

## Files Created/Modified
- `e2e/tests/auth.spec.ts` - Login flow E2E tests (admin login, invalid credentials, create account link)
- `e2e/tests/patient-profile.spec.ts` - Patient profile navigation E2E tests (list, detail tabs, view modes)
- `e2e/tests/auth.setup.ts` - Playwright global auth setup (logs in once, saves storageState)
- `e2e/playwright.config.ts` - Updated with setup/auth-tests/chromium project split
- `e2e/.gitignore` - Excludes .auth/ directory with saved browser state

## Decisions Made
- Used Playwright storageState pattern (auth.setup.ts) to share login state, reducing login API calls from 6 to 3 per suite run
- Split Playwright config into 3 projects: setup (auth.setup.ts), auth-tests (auth.spec.ts without storageState), chromium (all other tests with storageState)
- Set workers=1 and fullyParallel=false to avoid rate-limit flakiness against the deployed app
- Used getByRole("heading") for patient profile detail assertion to avoid strict mode violation from multiple matching elements

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Rate-limit exhaustion from login endpoint throttle:5,1**
- **Found during:** Task 1 (auth.spec.ts)
- **Issue:** Login endpoint has throttle:5,1 middleware (5 requests/min). Multiple test runs + retries exhaust the limit, causing tests to fail with "An unexpected error occurred" (the generic catch-all for rate-limited requests)
- **Fix:** Added Playwright storageState auth setup (auth.setup.ts) to login once and share state. Split projects so auth tests run independently.
- **Files modified:** e2e/tests/auth.setup.ts (new), e2e/playwright.config.ts
- **Verification:** All 7 tests pass in a single suite run within the rate limit
- **Committed in:** 0fac4c2 (Task 2 commit)

**2. [Rule 1 - Bug] Strict mode violation on patient profile locator**
- **Found during:** Task 2 (patient-profile.spec.ts)
- **Issue:** `getByText(/patient profile|demographics|mrn/i)` matched 2 elements (a "Patient Profiles" button and a "Patient Profile" heading), causing Playwright strict mode error
- **Fix:** Changed to `getByRole("heading", { name: /patient profile/i })` for unambiguous matching
- **Files modified:** e2e/tests/patient-profile.spec.ts
- **Verification:** All 3 patient profile tests pass
- **Committed in:** 0fac4c2 (Task 2 commit)

---

**Total deviations:** 2 auto-fixed (1 blocking, 1 bug)
**Impact on plan:** Both fixes necessary for test reliability. StorageState pattern is a Playwright best practice. No scope creep.

## Issues Encountered
- Login endpoint rate limit (throttle:5,1) caused intermittent test failures. Resolved by storageState auth sharing.
- Patient profile heading matched multiple elements. Resolved by using getByRole("heading") instead of getByText.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All 6 E2E tests pass against aurora.acumenus.net
- Phase 10 (E2E tests) complete

---
*Phase: 10-e2e-tests*
*Completed: 2026-03-25*
