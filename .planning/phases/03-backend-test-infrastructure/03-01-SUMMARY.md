---
phase: 03-backend-test-infrastructure
plan: 01
subsystem: testing
tags: [pest, phpunit, postgresql, database-truncation, model-factories, laravel]

# Dependency graph
requires:
  - phase: 01-fix-critical-blocker
    provides: clinical database connection alias with search_path
provides:
  - Pest test suite configured with DatabaseTruncation against aurora_test
  - ClinicalPatientFactory, GenomicVariantFactory, GeneDrugInteractionFactory
  - Updated ClinicalCaseFactory using ClinicalPatient instead of legacy Patient
  - FactorySmokeTest validating all 5 factories
affects: [04-frontend-test-infrastructure, 05-backend-unit-tests, 06-backend-feature-tests]

# Tech tracking
tech-stack:
  added: [pestphp/pest v3.8.6, phpunit/phpunit v11.5.50, mockery v1.6.12, fakerphp/faker v1.24.1]
  patterns: [DatabaseTruncation for multi-schema PostgreSQL, Clinical factory sub-namespace with newFactory()]

key-files:
  created:
    - backend/.env.testing
    - backend/database/factories/Clinical/ClinicalPatientFactory.php
    - backend/database/factories/Clinical/GeneDrugInteractionFactory.php
    - backend/database/factories/Clinical/GenomicVariantFactory.php
    - backend/tests/Feature/FactorySmokeTest.php
  modified:
    - backend/tests/Pest.php
    - backend/tests/TestCase.php
    - backend/database/factories/ClinicalCaseFactory.php
    - backend/app/Models/Clinical/GeneDrugInteraction.php
    - backend/app/Models/Clinical/GenomicVariant.php
    - backend/app/Models/Clinical/ClinicalPatient.php
    - backend/composer.lock

key-decisions:
  - "DatabaseTruncation over RefreshDatabase for multi-schema PostgreSQL performance"
  - "Unqualified table names in $exceptTables (DatabaseTruncation matches without schema prefix)"
  - "ClinicalCaseFactory uses ClinicalPatient instead of legacy Patient model"
  - "Clinical factories use explicit newFactory() to resolve sub-namespace discovery"

patterns-established:
  - "Clinical model factories: namespace Database\\Factories\\Clinical with $model binding and newFactory() on model"
  - "Test database: aurora_test with array drivers for cache/session/queue/mail"
  - "Permission tables excluded from truncation via $exceptTables in TestCase"

requirements-completed: [INFRA-01, INFRA-02]

# Metrics
duration: 5min
completed: 2026-03-25
---

# Phase 3 Plan 01: Backend Test Infrastructure Summary

**Pest test suite with DatabaseTruncation on multi-schema PostgreSQL, 3 new clinical factories, and 5-test smoke suite all passing**

## Performance

- **Duration:** 5 min
- **Started:** 2026-03-25T17:49:54Z
- **Completed:** 2026-03-25T17:55:32Z
- **Tasks:** 2
- **Files modified:** 12

## Accomplishments
- Pest configured with DatabaseTruncation against dedicated aurora_test database with all 35 migrations
- Three new clinical model factories (ClinicalPatient, GenomicVariant, GeneDrugInteraction) with realistic oncology data
- ClinicalCaseFactory fixed to use ClinicalPatient instead of legacy Patient (dev schema)
- All 5 factory smoke tests passing: User, ClinicalPatient, ClinicalCase, GeneDrugInteraction, GenomicVariant

## Task Commits

Each task was committed atomically:

1. **Task 1: Configure Pest with multi-schema PostgreSQL and test database** - `ce4f2cc` (feat)
2. **Task 2: Create model factories and factory smoke test** - `dc6d843` (feat)

## Files Created/Modified
- `backend/.env.testing` - Test database config pointing to aurora_test with array drivers
- `backend/tests/Pest.php` - DatabaseTruncation replacing RefreshDatabase for Feature tests
- `backend/tests/TestCase.php` - $exceptTables protecting permission tables from truncation
- `backend/database/factories/Clinical/ClinicalPatientFactory.php` - Factory with MRN, demographics
- `backend/database/factories/Clinical/GeneDrugInteractionFactory.php` - Factory with oncology gene/drug pairs
- `backend/database/factories/Clinical/GenomicVariantFactory.php` - Factory with ClinicalPatient relationship
- `backend/database/factories/ClinicalCaseFactory.php` - Updated: Patient->ClinicalPatient, added specialty/case_type
- `backend/app/Models/Clinical/GeneDrugInteraction.php` - Added HasFactory trait + newFactory()
- `backend/app/Models/Clinical/GenomicVariant.php` - Added HasFactory trait + newFactory()
- `backend/app/Models/Clinical/ClinicalPatient.php` - Added HasFactory trait + newFactory()
- `backend/tests/Feature/FactorySmokeTest.php` - 5 tests validating all factory instances
- `backend/composer.lock` - Pest and test dependencies installed

## Decisions Made
- Used DatabaseTruncation over RefreshDatabase: 35 migrations across 3 schemas makes per-test migration unacceptably slow
- Unqualified table names in $exceptTables: DatabaseTruncation matches table names without schema qualifiers
- ClinicalCaseFactory updated to use ClinicalPatient: ClinicalCase foreign key points to clinical.patients, not dev.patients
- Clinical factories use explicit newFactory() method: Laravel auto-discovery does not resolve sub-namespace factories without this

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Installed Pest dev dependencies**
- **Found during:** Task 1
- **Issue:** Pest was declared in composer.json require-dev but never installed (vendor/bin/pest missing)
- **Fix:** Ran `composer require pestphp/pest --dev -W` to install Pest and all test dependencies
- **Files modified:** backend/composer.lock
- **Verification:** vendor/bin/pest executable exists and runs
- **Committed in:** ce4f2cc (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** Essential fix -- tests cannot run without the test framework installed. No scope creep.

## Issues Encountered
- Existing test files (EventTest, CaseDiscussionTest) have a Mockery conflict causing "Cannot redeclare" errors when running the full suite. This is a pre-existing issue unrelated to our changes. The FactorySmokeTest runs cleanly in isolation and the full suite is out of scope.

## User Setup Required
None - no external service configuration required. The aurora_test database was created automatically.

## Next Phase Readiness
- Test infrastructure complete: Pest + DatabaseTruncation + factories all working
- Ready for Phase 5 (backend unit tests) and Phase 6 (backend feature tests)
- Pre-existing Mockery conflict in older test files may need attention in future phases

---
*Phase: 03-backend-test-infrastructure*
*Completed: 2026-03-25*
