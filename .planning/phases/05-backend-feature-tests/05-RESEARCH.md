# Phase 5: Backend Feature Tests - Research

**Researched:** 2026-03-25
**Domain:** Laravel Pest feature testing, HTTP endpoint testing, Sanctum auth in tests
**Confidence:** HIGH

## Summary

Phase 5 requires feature tests for 7 controllers (AuthController, PatientController, CaseController, SessionController, GenomicsController, DashboardController, RadiogenomicsController) exercising every API endpoint with realistic data. The test infrastructure from Phase 3 (Pest + DatabaseTruncation + model factories) is in place, and significant existing tests already cover AuthController and PatientController almost completely.

The critical blocker is that `.env.testing` uses `DB_HOST=host.docker.internal` which does not resolve outside Docker -- tests must run with `DB_HOST=localhost` since PostgreSQL runs on the host at port 5432. Additionally, factories for Session, SessionCase, SessionParticipant, and CaseTeamMember do not exist yet and must be created. The existing CaseDiscussionTest and EventTest use the legacy Patient model and Mockery, causing conflicts when run in the full suite -- these should be updated or isolated.

**Primary recommendation:** Fix `.env.testing` DB_HOST to `localhost`, create missing factories (Session, SessionCase, SessionParticipant), leverage existing AuthenticationTest and PatientTest as-is (they already satisfy BTEST-01 and most of BTEST-02), then write new test files for Case, Session, Genomics, Dashboard, and Radiogenomics controllers.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BTEST-01 | Feature tests for AuthController (login, register, change-password, logout) | Existing `tests/Feature/Auth/AuthenticationTest.php` covers all 4 flows with 11 tests. May only need minor additions. |
| BTEST-02 | Feature tests for PatientController (index, show, store, update, clinical notes, timeline) | Existing `tests/Feature/Api/PatientTest.php` covers store, profile, search, stats (14 tests). Missing: index pagination, notes endpoint. |
| BTEST-03 | Feature tests for CaseController (index, store, show, update, destroy, team members) | No existing tests for CaseController directly. CaseDiscussionTest exists but uses legacy Patient model. New test file needed. |
| BTEST-04 | Feature tests for SessionController (index, store, show, update, cases) | No existing tests. SessionFactory does not exist -- must be created. |
| BTEST-05 | Feature tests for GenomicsController (stats, interactions, variants, uploads, criteria) | No existing tests. Factories for GenomicVariant and GeneDrugInteraction exist from Phase 3. |
| BTEST-06 | Feature tests for DashboardController (index with patient counts) | No existing tests. Simple controller, uses raw DB queries. |
| BTEST-07 | Feature tests for RadiogenomicsController (panels, gene-drug interactions) | No existing tests. Uses RadiogenomicsService which queries multiple clinical tables. |
| BTEST-13 | Backend test coverage reaches 80%+ | 7 controllers + 4 services in scope = ~1,922 lines. 126 total PHP files. Coverage tooling (PCOV/Xdebug) status needs verification. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| pestphp/pest | v3.8.6 | Test framework | Already installed, Pest.php configured with DatabaseTruncation |
| phpunit/phpunit | v11.5.50 | Test runner (underlying) | Pest delegates to PHPUnit |
| Laravel Sanctum | (bundled) | Auth token testing | `actingAs($user, 'sanctum')` for authenticated requests |
| DatabaseTruncation | (Laravel trait) | Test isolation | Configured in Phase 3 for multi-schema PostgreSQL |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| fakerphp/faker | v1.24.1 | Realistic test data | In model factories |
| Illuminate\Support\Facades\Http | (bundled) | HTTP mocking | Fake Resend API calls in auth tests |

## Architecture Patterns

### Test File Organization
```
backend/tests/Feature/
  Auth/
    AuthenticationTest.php     # EXISTING - covers BTEST-01
  Api/
    PatientTest.php            # EXISTING - covers most of BTEST-02
    CaseControllerTest.php     # NEW
    SessionControllerTest.php  # NEW
    GenomicsControllerTest.php # NEW
    DashboardTest.php          # NEW
    RadiogenomicsTest.php      # NEW
    EventTest.php              # EXISTING (pre-existing, has Mockery issues)
    CaseDiscussionTest.php     # EXISTING (pre-existing, uses legacy Patient)
  FactorySmokeTest.php         # EXISTING
```

### Pattern: Feature Test with Sanctum Auth
**What:** Every protected endpoint test uses `actingAs()` with a factory-created User
**When to use:** All authenticated endpoint tests
**Example:**
```php
// Source: Existing tests/Feature/Auth/AuthenticationTest.php
beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('creates a patient with valid data', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/patients', $payload);
    $response->assertStatus(201);
});
```

### Pattern: Testing ApiResponse Envelope
**What:** All endpoints return `{success, message, data}` or `{success, message, data, meta}` for paginated
**When to use:** Every assertion block
**Example:**
```php
$response->assertStatus(200)
    ->assertJsonPath('success', true)
    ->assertJsonStructure(['success', 'message', 'data']);

// Paginated:
$response->assertJsonStructure([
    'success', 'message', 'data',
    'meta' => ['total', 'page', 'per_page', 'last_page'],
]);
```

### Pattern: Testing 401 for Unauthenticated Access
**What:** Every protected route must return 401 without auth token
**When to use:** Include for each controller's endpoint group
**Example:**
```php
it('requires authentication', function () {
    $this->getJson('/api/dashboard/stats')->assertStatus(401);
});
```

### Anti-Patterns to Avoid
- **Using `Patient` (legacy) instead of `ClinicalPatient`:** The legacy Patient model references `dev.patients` which may not exist in test DB. Always use `ClinicalPatient` (clinical schema).
- **Seeding SuperuserSeeder in every test group:** Only seed when tests actually need the superuser. Use `User::factory()->create()` for generic authenticated users.
- **Testing stub endpoints for side effects:** GenomicsController upload/criteria endpoints are stubs -- test the HTTP interface (status codes, response shape) not database writes.
- **Running tests inside Docker:** Tests run on the host machine with `php vendor/bin/pest`, not inside the Docker PHP container. DB_HOST must be `localhost`.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Auth token setup | Manual token creation + headers | `$this->actingAs($user, 'sanctum')` | Laravel handles token lifecycle |
| HTTP mocking (Resend) | Custom mock classes | `Http::fake(['api.resend.com/*' => ...])` | Laravel facade faking is built-in |
| Test DB isolation | Manual truncation SQL | `DatabaseTruncation` trait (configured in Pest.php) | Handles multi-schema tables automatically |
| JSON assertions | Manual JSON decode + expect | `$response->assertJsonPath()`, `assertJsonStructure()` | Laravel test response methods are richer |

## Common Pitfalls

### Pitfall 1: .env.testing DB_HOST = host.docker.internal
**What goes wrong:** All tests fail with "could not translate host name" because `host.docker.internal` only resolves inside Docker containers.
**Why it happens:** The .env.testing was copied from Docker-oriented .env without adjusting the host.
**How to avoid:** Change `DB_HOST=host.docker.internal` to `DB_HOST=localhost` in `.env.testing`. PostgreSQL runs locally on port 5432.
**Warning signs:** Every test fails with `SQLSTATE[08006] [7] could not translate host name`.

### Pitfall 2: ClinicalCase validation requires `exists:clinical.patients,id`
**What goes wrong:** Creating cases with `patient_id` fails validation unless a ClinicalPatient exists first.
**Why it happens:** CaseController store/update validates `patient_id` against `clinical.patients` table.
**How to avoid:** Always create a `ClinicalPatient::factory()->create()` before creating cases with patient_id.
**Warning signs:** 422 validation errors mentioning "patient_id".

### Pitfall 3: Session `scheduled_at` must be in the future
**What goes wrong:** SessionController store validates `scheduled_at` with `after:now`.
**Why it happens:** Validation rule prevents creating sessions in the past.
**How to avoid:** Use `now()->addDay()` or `Carbon::now()->addHour()` for scheduled_at in test payloads.
**Warning signs:** 422 on session creation with "scheduled_at must be after now".

### Pitfall 4: Pre-existing Mockery conflict in EventTest and CaseDiscussionTest
**What goes wrong:** Running the full test suite triggers "Cannot redeclare" errors from Mockery.
**Why it happens:** Old test files may have conflicting Mockery setup or namespace issues.
**How to avoid:** Run new tests by directory or filter. If full-suite run is needed for coverage, fix or isolate the conflicting files first.
**Warning signs:** Fatal error mentioning Mockery when running `vendor/bin/pest` without filters.

### Pitfall 5: CaseDiscussionTest uses legacy Patient model
**What goes wrong:** `Patient::factory()->create()` references `dev.patients` table which may not be seeded in test DB.
**Why it happens:** Old test was written before ClinicalPatient refactor.
**How to avoid:** Update to use `ClinicalPatient::factory()->create()` if modifying this file.
**Warning signs:** Factory errors or missing table errors.

### Pitfall 6: GenomicsController inconsistent response format
**What goes wrong:** Some GenomicsController endpoints return `ApiResponse::success()` (envelope), while others return `response()->json()` directly.
**Why it happens:** Controller was built incrementally with inconsistent patterns.
**How to avoid:** Test actual response shapes, not assumed envelope. Check `interactions()`, `clinvarStatus()`, `clinvarSearch()` -- they return non-standard formats.
**Warning signs:** `assertJsonPath('success', true)` fails on endpoints returning raw JSON.

### Pitfall 7: RadiogenomicsService queries `drug_eras` table
**What goes wrong:** `RadiogenomicsService::getPatientPanel()` queries `drug_eras` table directly via DB facade.
**Why it happens:** Uses raw `DB::table('drug_eras')` instead of a model.
**How to avoid:** Either seed `drug_eras` table in test setup, or accept empty results. The endpoint returns 404 when patient not found, but returns data structure with empty arrays when patient exists but has no drug data.
**Warning signs:** SQL errors if `drug_eras` table doesn't exist in test DB schema.

## Code Examples

### Creating an Authenticated User for Tests
```php
// Source: Existing tests/Feature/Api/PatientTest.php
beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

// Alternative: Factory-created user (faster, no seeder dependency)
beforeEach(function () {
    $this->user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
});
```

### Testing Case CRUD with Team Members
```php
// Create prerequisites
$patient = ClinicalPatient::factory()->create();
$user = User::factory()->create(['is_active' => true]);

// Create case
$response = $this->actingAs($user, 'sanctum')
    ->postJson('/api/cases', [
        'title' => 'Tumor Board Review',
        'specialty' => 'oncology',
        'case_type' => 'tumor_board',
        'patient_id' => $patient->id,
    ]);
$response->assertStatus(201);
$caseId = $response->json('data.id');

// Add team member
$reviewer = User::factory()->create(['is_active' => true]);
$response = $this->actingAs($user, 'sanctum')
    ->postJson("/api/cases/{$caseId}/team", [
        'user_id' => $reviewer->id,
        'role' => 'reviewer',
    ]);
$response->assertStatus(201);
```

### Testing Session Lifecycle
```php
// Create session
$response = $this->actingAs($user, 'sanctum')
    ->postJson('/api/sessions', [
        'title' => 'Weekly Tumor Board',
        'scheduled_at' => now()->addDay()->toIso8601String(),
        'session_type' => 'tumor_board',
    ]);
$response->assertStatus(201);
$sessionId = $response->json('data.id');

// Start session
$this->actingAs($user, 'sanctum')
    ->postJson("/api/sessions/{$sessionId}/start")
    ->assertStatus(200);

// End session
$this->actingAs($user, 'sanctum')
    ->postJson("/api/sessions/{$sessionId}/end")
    ->assertStatus(200);
```

### Testing Genomics Stats
```php
use App\Models\Clinical\GenomicVariant;

GenomicVariant::factory()->count(3)->create([
    'clinical_significance' => 'pathogenic',
]);
GenomicVariant::factory()->count(2)->create([
    'clinical_significance' => 'VUS',
]);

$response = $this->actingAs($user, 'sanctum')
    ->getJson('/api/genomics/stats');

$response->assertStatus(200)
    ->assertJsonPath('data.total_variants', 5)
    ->assertJsonPath('data.pathogenic_count', 3)
    ->assertJsonPath('data.vus_count', 2);
```

## Existing Test Coverage Analysis

### Already Complete (from previous phases)
| File | Tests | Covers |
|------|-------|--------|
| `Auth/AuthenticationTest.php` | 11 tests | Login (valid/invalid/inactive), register (new/existing email), change-password (valid/wrong), logout, user endpoint, health check, superuser model |
| `Api/PatientTest.php` | 14 tests | Store (valid/invalid/duplicate/unauth), profile (valid/404/unauth), search (name/MRN/missing-q/unauth), stats (domain counts/unauth) |

### Gaps to Fill
| Controller | Endpoints Missing Tests | Priority |
|------------|------------------------|----------|
| PatientController | `GET /patients` (index pagination), `GET /patients/{id}/notes` | LOW - minor gap |
| CaseController | All 7 endpoints (index, store, show, update, destroy, addTeamMember, removeTeamMember) | HIGH |
| SessionController | All 11 endpoints (CRUD + start/end + cases + join/leave) | HIGH |
| GenomicsController | stats, interactions, variants, uploads (stubs), criteria (stubs), clinvar endpoints | MEDIUM |
| DashboardController | `GET /dashboard/stats` | LOW - simple |
| RadiogenomicsController | `GET /radiogenomics/patients/{id}`, `GET /radiogenomics/variant-drug-interactions` | MEDIUM |

### Missing Factories Required
| Model | Table | Why Needed |
|-------|-------|------------|
| Session | `app.clinical_sessions` | SessionController CRUD tests |
| (inline creation sufficient) | `app.session_cases` | SessionCase is created via controller endpoints |
| (inline creation sufficient) | `app.session_participants` | SessionParticipant created via join endpoint |

**Note:** SessionController tests can create Sessions via `Session::create()` directly in test setup since the model is simple. A factory is optional but recommended for consistency.

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `Patient` model (dev schema) | `ClinicalPatient` (clinical schema) | Phase 3 refactor | CaseDiscussionTest still uses old Patient -- needs update |
| `RefreshDatabase` | `DatabaseTruncation` | Phase 3 | Faster tests with multi-schema PostgreSQL |
| Manual HTTP testing (curl) | Pest feature tests with `postJson`/`getJson` | Phase 3 | Automated, repeatable, CI-ready |

## Open Questions

1. **Coverage tooling (PCOV/Xdebug) availability**
   - What we know: PCOV Docker installation was deferred (out of scope per REQUIREMENTS.md). Tests run on host, not Docker.
   - What's unclear: Whether PCOV or Xdebug is installed on the host PHP 8.4 runtime.
   - Recommendation: Check `php -m | grep pcov` or `php -m | grep xdebug`. If neither available, coverage measurement may need `php -d pcov.enabled=1` or Xdebug installation. Coverage can be deferred if tooling is missing -- focus on test quality.

2. **Pre-existing Mockery conflict resolution**
   - What we know: EventTest and CaseDiscussionTest cause "Cannot redeclare" errors.
   - What's unclear: Whether fixing these is in scope for Phase 5 or should remain isolated.
   - Recommendation: Run new tests with `--filter` to avoid the conflict. Fix the legacy tests only if full-suite coverage measurement requires it.

3. **RadiogenomicsService dependency on `drug_eras` table**
   - What we know: Service queries `drug_eras` via DB facade, not Eloquent.
   - What's unclear: Whether `drug_eras` table is seeded in aurora_test.
   - Recommendation: Test the endpoint with a patient that has variants but no drug eras -- verify graceful handling. If table missing, the try/catch in DashboardController pattern may not apply here.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest v3.8.6 + PHPUnit v11.5.50 |
| Config file | `backend/phpunit.xml` + `backend/tests/Pest.php` |
| Quick run command | `cd backend && php vendor/bin/pest --filter=AuthenticationTest` |
| Full suite command | `cd backend && php vendor/bin/pest` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| BTEST-01 | Auth login/register/change-password/logout | feature | `php vendor/bin/pest --filter=AuthenticationTest` | Yes (11 tests) |
| BTEST-02 | Patient CRUD, notes, timeline | feature | `php vendor/bin/pest --filter=PatientTest` | Partial (14 tests, missing index/notes) |
| BTEST-03 | Case CRUD, team members | feature | `php vendor/bin/pest --filter=CaseControllerTest` | No -- Wave 0 |
| BTEST-04 | Session CRUD, start/end, cases | feature | `php vendor/bin/pest --filter=SessionControllerTest` | No -- Wave 0 |
| BTEST-05 | Genomics stats, interactions, variants | feature | `php vendor/bin/pest --filter=GenomicsControllerTest` | No -- Wave 0 |
| BTEST-06 | Dashboard stats | feature | `php vendor/bin/pest --filter=DashboardTest` | No -- Wave 0 |
| BTEST-07 | Radiogenomics panels, interactions | feature | `php vendor/bin/pest --filter=RadiogenomicsTest` | No -- Wave 0 |
| BTEST-13 | Coverage >= 80% | coverage | `php vendor/bin/pest --coverage --min=80` | N/A |

### Sampling Rate
- **Per task commit:** `cd backend && php vendor/bin/pest --filter={TestClassName}`
- **Per wave merge:** `cd backend && php vendor/bin/pest tests/Feature/`
- **Phase gate:** Full suite green + coverage check

### Wave 0 Gaps
- [ ] Fix `backend/.env.testing` DB_HOST from `host.docker.internal` to `localhost`
- [ ] `backend/tests/Feature/Api/CaseControllerTest.php` -- covers BTEST-03
- [ ] `backend/tests/Feature/Api/SessionControllerTest.php` -- covers BTEST-04
- [ ] `backend/tests/Feature/Api/GenomicsControllerTest.php` -- covers BTEST-05
- [ ] `backend/tests/Feature/Api/DashboardTest.php` -- covers BTEST-06
- [ ] `backend/tests/Feature/Api/RadiogenomicsTest.php` -- covers BTEST-07
- [ ] Optional: `backend/database/factories/SessionFactory.php` for cleaner test setup
- [ ] Verify coverage tooling: `php -m | grep -i pcov` or `php -m | grep -i xdebug`

## Sources

### Primary (HIGH confidence)
- Direct codebase inspection of all 7 controllers, 4 services, routes/api.php, existing test files
- Phase 3 summary (`03-01-SUMMARY.md`) confirming Pest + DatabaseTruncation + factory setup
- Live test execution showing `.env.testing` DB_HOST issue

### Secondary (MEDIUM confidence)
- Laravel Sanctum `actingAs()` testing pattern (standard Laravel documentation pattern)
- Pest test organization conventions (standard Pest project structure)

**Confidence breakdown:**
- Standard stack: HIGH - directly verified from codebase and Phase 3 summary
- Architecture: HIGH - patterns extracted from existing working tests
- Pitfalls: HIGH - DB_HOST issue verified by running tests; Mockery conflict documented in Phase 3 summary
- Coverage target: MEDIUM - depends on PCOV/Xdebug availability on host

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable -- testing infrastructure unlikely to change)
