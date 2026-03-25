---
phase: 10-e2e-tests
plan: 02
subsystem: testing
tags: [playwright, e2e, genomics, cases, clinical]

# Dependency graph
requires:
  - phase: 10-e2e-tests-01
    provides: "Playwright storageState auth setup, auth and patient-profile E2E specs"
provides:
  - "Genomics tab E2E test (genomics.spec.ts)"
  - "Case management E2E test (case-lifecycle.spec.ts)"
affects: []

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "test.describe.serial for dependent E2E tests sharing state"
    - "test.skip() with clear message for data-dependent tests"

key-files:
  created:
    - e2e/tests/genomics.spec.ts
  modified:
    - e2e/tests/case-lifecycle.spec.ts

key-decisions:
  - "Genomics tests use test.skip() when Genomics button absent (data-dependent, not a failure)"
  - "Case lifecycle uses serial describe with shared caseTitle for create-then-detail flow"
  - "Assert Add Member button (not heading .or() button) to avoid strict mode violation with multiple matches"

patterns-established:
  - "Data-dependent E2E tests: skip with clear reason rather than conditionally passing"
  - "Serial E2E tests: share unique identifiers (Date.now()) for create-then-verify flows"

requirements-completed: [E2E-03, E2E-04]

# Metrics
duration: 3min
completed: 2026-03-25
---

# Phase 10 Plan 02: Genomics and Case Lifecycle E2E Summary

**Playwright E2E tests for Genomics tab (data-dependent skip) and case management lifecycle (create, list, detail with team tab)**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-25T22:00:21Z
- **Completed:** 2026-03-25T22:03:30Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- Created genomics.spec.ts with 2 tests that gracefully skip when no genomic data is seeded
- Rewrote case-lifecycle.spec.ts from scratch with 3 passing tests targeting v2 selectors
- Case creation test uses unique Date.now() title to prevent collisions across runs
- All tests use user-facing locators (getByRole, getByLabel, getByText) -- no data-testid or v1 sidebar selectors

## Task Commits

Each task was committed atomically:

1. **Task 1: Create genomics.spec.ts** - `874ca67` (feat)
2. **Task 2: Rewrite case-lifecycle.spec.ts** - `9657237` (feat)

## Files Created/Modified
- `e2e/tests/genomics.spec.ts` - Genomics tab E2E: access genomics view, verify briefing/variant sections
- `e2e/tests/case-lifecycle.spec.ts` - Case management E2E: list page, create case, view detail with team tab

## Decisions Made
- Genomics tests use test.skip() with descriptive message when Genomics button is absent (conditionally rendered based on profile.genomics array length)
- Case lifecycle uses test.describe.serial to ensure create-case runs before view-case-detail
- Fixed strict mode violation by asserting single "Add Member" button instead of .or() with heading+button

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed strict mode violation in team tab assertion**
- **Found during:** Task 2 (case-lifecycle.spec.ts)
- **Issue:** `.or()` locator resolved to 2 elements (Team Members heading + Add Member button), causing Playwright strict mode error
- **Fix:** Changed assertion to target only the Add Member button
- **Files modified:** e2e/tests/case-lifecycle.spec.ts
- **Verification:** Test passes on retry
- **Committed in:** 9657237 (Task 2 commit)

---

**Total deviations:** 1 auto-fixed (1 bug)
**Impact on plan:** Minimal -- selector refinement for Playwright strict mode compliance.

## Issues Encountered
- Auth setup intermittently fails due to pre-existing host.docker.internal DNS issue (retry resolves it)

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All Phase 10 E2E tests complete (Plan 01 + Plan 02)
- Full E2E suite: auth, patient-profile, genomics, case-lifecycle
- Ready for /gsd:verify-work

---
*Phase: 10-e2e-tests*
*Completed: 2026-03-25*
