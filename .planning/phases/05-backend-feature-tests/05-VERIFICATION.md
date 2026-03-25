---
phase: 05-backend-feature-tests
verified: 2026-03-25T19:30:00Z
status: gaps_found
score: 7/8 must-haves verified
re_verification: false
gaps:
  - truth: "Backend test suite passes with 80%+ coverage or all feature tests green if coverage tooling unavailable"
    status: partial
    reason: "1 of 101 tests fails intermittently due to a unique constraint violation in GenomicsControllerTest. The GeneDrugInteractionFactory always sets variant_pattern='*' but randomizes gene and drug. When two factory calls in the 'filters by gene' test pick the same (gene='BRAF', drug) combination, the unique constraint (gene, variant_pattern, drug) is violated. The test was not deterministic at the time of verification."
    artifacts:
      - path: "backend/tests/Feature/Api/GenomicsControllerTest.php"
        issue: "Line 62-64: creates two BRAF interactions without fixing the drug field, risking (BRAF, *, <same_drug>) collision. Test result: 100 passed, 1 failed at run time."
      - path: "backend/database/factories/Clinical/GeneDrugInteractionFactory.php"
        issue: "Always uses variant_pattern='*' and random drugs. No sequence or unique() guard on drug field."
    missing:
      - "Fix the 'filters by gene' test to pin distinct drugs per record, e.g. ->create(['gene'=>'BRAF','drug'=>'Vemurafenib']) and ->create(['gene'=>'BRAF','drug'=>'Dabrafenib']), preventing unique-constraint collisions."
human_verification:
  - test: "Run full suite 3+ consecutive times to confirm zero intermittent failures after fix"
    expected: "101 tests, 303 assertions, all green on every run"
    why_human: "Intermittent failures require repeated execution to confirm determinism after factory fix"
  - test: "Verify coverage measurement is wired in CI"
    expected: "PCOV or Xdebug enabled in CI pipeline; pest --coverage runs and reports >= 80%"
    why_human: "Coverage tooling was explicitly deferred to CI; CI config not inspectable without running the pipeline"
---

# Phase 05: Backend Feature Tests Verification Report

**Phase Goal:** Every API controller has feature tests exercising its endpoints with realistic data
**Verified:** 2026-03-25T19:30:00Z
**Status:** gaps_found
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | AuthController tests pass (login valid/invalid, register, change-password, logout) | VERIFIED | AuthenticationTest.php: 12 tests, 205 lines, covers login valid/invalid/inactive, register new/existing, change-password, logout, health, superuser. Commits 3fd4dfb + 2e855a9 confirmed present. |
| 2 | PatientController index returns paginated patients, notes endpoint returns clinical notes, update/timeline documented as not implemented | VERIFIED | PatientTest.php: 21 tests, 342 lines. Index pagination (3 tests), notes endpoint (3 tests), gap docs (2 tests asserting error for PUT/timeline). Direct DB insert used for clinical_notes due to no factory. |
| 3 | DashboardController stats endpoint returns patient counts and system health | VERIFIED | DashboardTest.php: 3 tests, 48 lines. Covers stats structure, system_health.database/cache, auth requirement. Key link: getJson('/api/dashboard/stats') confirmed at line 16. |
| 4 | CaseController CRUD endpoints work with correct validation and team member management | VERIFIED | CaseControllerTest.php: 16 tests, 241 lines (plan min: 120). Covers index (paginated/filtered), store (valid/no-patient/missing/invalid), show, update, destroy, addTeamMember (success/duplicate 409), removeTeamMember. Key link: Json.*api/cases pattern confirmed in file. |
| 5 | SessionController CRUD, lifecycle, case management, and join/leave all work | VERIFIED | SessionControllerTest.php: 22 tests, 327 lines (plan min: 150). Covers CRUD + start/end lifecycle + addCase/removeCase/duplicate + join/leave/not-joined. SessionFactory created (28 lines, plan min: 15). |
| 6 | GenomicsController stats, interactions, variants, and stub endpoints return correct responses | VERIFIED (with caveat) | GenomicsControllerTest.php: 20 tests, 231 lines (plan min: 100). Covers stats aggregation, interactions (with filter), variants (paginated/filtered/single/404), upload stubs, criteria stubs, ClinVar status/search. BUT: one test ('filters by gene') has an intermittent unique-constraint failure. |
| 7 | RadiogenomicsController patient panel and variant-drug interactions return correct data | VERIFIED | RadiogenomicsTest.php: 7 tests, 100 lines (plan min: 60). Covers patient panel with genomic data, 404 for missing patient, auth requirement, variant-drug interactions, gene filter, relationship filter. |
| 8 | Backend test suite passes with 80%+ coverage or all feature tests green if coverage tooling unavailable | PARTIAL | 100 of 101 tests pass at verification time. 1 test fails intermittently (unique constraint in GenomicsControllerTest line 62-64). PCOV/Xdebug unavailable locally; coverage deferred to CI (documented limitation). |

**Score:** 7/8 truths verified (1 partial due to intermittent test failure)

---

## Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `backend/.env.testing` | DB_HOST=localhost | VERIFIED | Line 22: DB_HOST=localhost confirmed |
| `backend/tests/Feature/Auth/AuthenticationTest.php` | AuthController feature tests | VERIFIED | 205 lines, 12 tests |
| `backend/tests/Feature/Api/PatientTest.php` | PatientController tests, min 200 lines | VERIFIED | 342 lines, 21 tests |
| `backend/tests/Feature/Api/DashboardTest.php` | DashboardController feature tests, min 30 lines | VERIFIED | 48 lines, 3 tests |
| `backend/database/factories/SessionFactory.php` | Session model factory, min 15 lines | VERIFIED | 28 lines, valid definition |
| `backend/tests/Feature/Api/CaseControllerTest.php` | CaseController feature tests, min 120 lines | VERIFIED | 241 lines, 16 tests |
| `backend/tests/Feature/Api/SessionControllerTest.php` | SessionController feature tests, min 150 lines | VERIFIED | 327 lines, 22 tests |
| `backend/tests/Feature/Api/GenomicsControllerTest.php` | GenomicsController feature tests, min 100 lines | STUB-RISK | 231 lines, 20 tests. File is substantive but has intermittent failure at line 63. |
| `backend/tests/Feature/Api/RadiogenomicsTest.php` | RadiogenomicsController feature tests, min 60 lines | VERIFIED | 100 lines, 7 tests |
| `backend/config/database.php` | 'app' connection alias for exists:app.users validation | VERIFIED | Line 100: 'app' connection with search_path 'app,public' |

---

## Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| AuthenticationTest.php | /api/login, /api/register, /api/change-password, /api/logout | postJson/getJson calls | WIRED | Confirmed in test file |
| DashboardTest.php | /api/dashboard/stats | getJson endpoint call | WIRED | Lines 16, 38, 46 confirmed |
| PatientTest.php | /api/patients | getJson('/api/patients') | WIRED | Lines 245, 258, 269 confirmed |
| PatientTest.php | /api/patients/{patient}/notes | getJson('patients.*notes') | WIRED | Lines 288, 298, 304 confirmed |
| CaseControllerTest.php | /api/cases | Json.*api/cases | WIRED | Lines 18, 54, 70, 100, 130 confirmed |
| CaseControllerTest.php | /api/cases/{case}/team | Json.*api/cases.*team | WIRED | Lines 191, 206, 236 confirmed |
| SessionControllerTest.php | /api/sessions | Json.*api/sessions | WIRED | Lines 17, 52, 66, 87, 106, 161 confirmed |
| GenomicsControllerTest.php | /api/genomics/stats | getJson.*genomics | WIRED | Lines 20, 31, 40 confirmed |
| GenomicsControllerTest.php | /api/genomics/interactions | getJson.*genomics | WIRED | Lines 52, 67, 78 confirmed |
| RadiogenomicsTest.php | /api/radiogenomics/patients/{id} | getJson.*radiogenomics | WIRED | Lines 23, 45, 52 confirmed |
| RadiogenomicsTest.php | /api/radiogenomics/variant-drug-interactions | getJson.*radiogenomics | WIRED | Lines 62, 74, 86, 97 confirmed |

---

## Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| BTEST-01 | 05-01 | Feature tests for AuthController (login, register, change-password, logout) | SATISFIED | AuthenticationTest.php: 12 tests covering all endpoints |
| BTEST-02 | 05-01 | Feature tests for PatientController (index, show, store, update, clinical notes, timeline) | SATISFIED (partial endpoints) | PatientTest.php: 21 tests. update/timeline documented as not implemented via gap-doc tests asserting error status |
| BTEST-03 | 05-02 | Feature tests for CaseController (index, store, show, update, destroy, team members) | SATISFIED | CaseControllerTest.php: 16 tests covering all 7 endpoints including team management |
| BTEST-04 | 05-02 | Feature tests for SessionController (index, store, show, update, cases) | SATISFIED | SessionControllerTest.php: 22 tests covering CRUD + lifecycle + cases + participants |
| BTEST-05 | 05-03 | Feature tests for GenomicsController (stats, interactions, variants, uploads, criteria) | SATISFIED (with intermittent failure) | GenomicsControllerTest.php: 20 tests covering all endpoints. One intermittent failure in interactions filter. |
| BTEST-06 | 05-01 | Feature tests for DashboardController (index with patient counts) | SATISFIED | DashboardTest.php: 3 tests covering stats, system health, auth |
| BTEST-07 | 05-03 | Feature tests for RadiogenomicsController (panels, gene-drug interactions) | SATISFIED | RadiogenomicsTest.php: 7 tests covering patient panel and variant-drug interactions with filters |
| BTEST-13 | 05-03 | Backend test coverage reaches 80%+ | PARTIAL | 100/101 tests pass. PCOV/Xdebug unavailable locally; coverage deferred to CI. One intermittent failure must be fixed first. |

---

## Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `backend/tests/Feature/Api/GenomicsControllerTest.php` | 62-64 | Non-deterministic factory creates with same variant_pattern='*' and random drug for same gene, risks unique constraint violation | BLOCKER | Causes test failure: 1 failed, 100 passed seen at verification. Suite is not reliably green. |
| `backend/database/factories/Clinical/GeneDrugInteractionFactory.php` | 25 | `'variant_pattern' => '*'` hardcoded — all records share same pattern, increasing collision risk when gene+drug are random | WARNING | Root cause of above blocker; also affects FactorySmokeTest.php if run multiple times without truncation |

---

## Human Verification Required

### 1. Intermittent Test Determinism

**Test:** Run `cd backend && php vendor/bin/pest tests/Feature/Api/GenomicsControllerTest.php` five consecutive times
**Expected:** All 20 tests pass on every run
**Why human:** CI is needed to catch intermittent failures across multiple runs; single-run pass is insufficient

### 2. CI Coverage Measurement

**Test:** Trigger the backend CI pipeline with PCOV enabled and check that `pest --coverage --min=80` passes
**Expected:** Backend feature test suite reports 80%+ line coverage
**Why human:** PCOV/Xdebug not available locally; coverage measurement was explicitly deferred to CI per Phase 5 plan

---

## Gaps Summary

**1 gap blocking full goal achievement:**

The test suite is reported as 101 tests/303 assertions all passing by the SUMMARY, but at verification time 1 test fails. The root cause is in `GenomicsControllerTest.php` lines 62-64: the test creates two `GeneDrugInteraction` records both with gene='BRAF' but with random drugs. Since `GeneDrugInteractionFactory` always sets `variant_pattern='*'`, if the same drug is randomly selected for both records, the unique constraint `(gene, variant_pattern, drug) = (BRAF, *, <same_drug>)` fires.

**Fix required:** In `GenomicsControllerTest.php`, pin distinct drugs to the two BRAF factory calls:
```php
GeneDrugInteraction::factory()->create(['gene' => 'BRAF', 'drug' => 'Vemurafenib']);
GeneDrugInteraction::factory()->create(['gene' => 'BRAF', 'drug' => 'Dabrafenib']);
GeneDrugInteraction::factory()->create(['gene' => 'KRAS', 'drug' => 'Sotorasib']);
```

This is a narrow, targeted fix. All 7 controllers remain covered; this is a factory data-setup issue, not a missing-test issue. Once fixed, re-run to confirm 101/101 pass deterministically.

**BTEST-13 (80%+ coverage)** remains deferred to CI and is not a blocker for the feature-test phase goal itself — the REQUIREMENTS.md note explicitly acknowledges PCOV setup is out of scope locally.

---

_Verified: 2026-03-25T19:30:00Z_
_Verifier: Claude (gsd-verifier)_
