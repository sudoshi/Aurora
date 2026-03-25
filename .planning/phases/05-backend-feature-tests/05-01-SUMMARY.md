---
phase: 05-backend-feature-tests
plan: 01
subsystem: testing
tags: [pest, laravel, feature-tests, patient-api, dashboard-api]

requires:
  - phase: 03-backend-test-infrastructure
    provides: Pest test framework, DatabaseTruncation config, ClinicalPatient factory, SuperuserSeeder
provides:
  - Green auth test baseline (12 tests)
  - PatientController full endpoint coverage (21 tests)
  - DashboardController feature tests (3 tests)
  - Documented gaps for PUT update and GET timeline endpoints
affects: [05-backend-feature-tests, 09-feature-completion]

tech-stack:
  added: []
  patterns: [ApiResponse::success with paginator serialization, DB::table for direct inserts in tests]

key-files:
  created:
    - backend/tests/Feature/Api/DashboardTest.php
  modified:
    - backend/.env.testing
    - backend/tests/Feature/Api/PatientTest.php

key-decisions:
  - "Assert >=400 for unimplemented endpoints because catch-all exception handler converts all exceptions to 500"
  - "Index pagination tests use data.data path since ApiResponse::success wraps paginator (not ApiResponse::paginated)"

patterns-established:
  - "Gap documentation: test unimplemented endpoints by asserting error status and success=false"
  - "Direct DB::table inserts for clinical_notes in tests (no factory exists)"

requirements-completed: [BTEST-01, BTEST-02, BTEST-06]

duration: 3min
completed: 2026-03-25
---

# Phase 05 Plan 01: Fix Test Blocker + Patient & Dashboard Tests Summary

**Fixed DB_HOST blocker, verified 12 auth tests green, added 8 PatientController tests (index/notes/gap docs) and 3 DashboardController tests**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-25T19:00:33Z
- **Completed:** 2026-03-25T19:03:42Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- Fixed .env.testing DB_HOST from host.docker.internal to localhost, unblocking all local test execution
- Verified all 12 existing AuthenticationTest tests pass (BTEST-01 complete)
- Added 8 new PatientController tests: index pagination (3), notes endpoint (3), update/timeline gap documentation (2)
- Created DashboardTest with 3 tests covering stats structure, system health, and auth requirement (BTEST-06 complete)
- Documented PUT /api/patients/{id} and GET /api/patients/{id}/timeline as not implemented (BTEST-02 gap coverage)

## Task Commits

Each task was committed atomically:

1. **Task 1: Fix .env.testing and verify existing auth tests** - `3fd4dfb` (fix)
2. **Task 2: Add PatientController full endpoint tests and create DashboardController tests** - `2e855a9` (feat)

## Files Created/Modified
- `backend/.env.testing` - Changed DB_HOST to localhost for local test execution
- `backend/tests/Feature/Api/PatientTest.php` - Added index pagination, notes, and gap documentation tests
- `backend/tests/Feature/Api/DashboardTest.php` - New file with dashboard stats endpoint tests

## Decisions Made
- Assert >=400 (not specific 405/404) for unimplemented endpoints because bootstrap/app.php catch-all exception handler converts MethodNotAllowedHttpException and NotFoundHttpException to 500 for JSON requests
- Index endpoint uses ApiResponse::success() with paginator (not ApiResponse::paginated()), so pagination data is at data.per_page not meta.per_page

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed per_page assertion path for index endpoint**
- **Found during:** Task 2
- **Issue:** Plan specified assertJsonPath('meta.per_page', 2) but index uses ApiResponse::success() which wraps paginator differently than ApiResponse::paginated()
- **Fix:** Changed to expect($response->json('data.per_page'))->toBe(2) and data items at data.data
- **Files modified:** backend/tests/Feature/Api/PatientTest.php
- **Committed in:** 2e855a9

**2. [Rule 1 - Bug] Adjusted expected status codes for unimplemented endpoints**
- **Found during:** Task 2
- **Issue:** Plan expected 405/404 for PUT/timeline but catch-all exception handler in bootstrap/app.php converts all exceptions to 500
- **Fix:** Changed to assert success=false and status >= 400 (documents the gap without brittle status code dependency)
- **Files modified:** backend/tests/Feature/Api/PatientTest.php
- **Committed in:** 2e855a9

**3. [Rule 1 - Bug] Used correct column name for clinical_notes insert**
- **Found during:** Task 2
- **Issue:** Plan referenced 'note_text' column but schema uses 'content'
- **Fix:** Used DB::table insert with correct column: 'content'
- **Files modified:** backend/tests/Feature/Api/PatientTest.php
- **Committed in:** 2e855a9

---

**Total deviations:** 3 auto-fixed (3 bugs)
**Impact on plan:** All auto-fixes necessary for test correctness. No scope creep.

## Issues Encountered
None beyond the deviations documented above.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All auth, patient, and dashboard tests green (36 total across 3 files)
- Ready for Plan 02 (CaseDiscussion/Event tests) and Plan 03 (remaining controller tests)
- Note: catch-all exception handler converts specific HTTP exceptions to generic 500 -- future plans may want to address this

---
*Phase: 05-backend-feature-tests*
*Completed: 2026-03-25*
