# Phase 6: Backend Unit Tests - Research

**Researched:** 2026-03-25
**Domain:** Pest PHP unit testing with Mockery for Laravel service classes
**Confidence:** HIGH

## Summary

Phase 6 requires unit tests for five service classes: AuthService, PatientService, CaseService, RadiogenomicsService, and OncoKbService. The project already has established unit test patterns from Phase 3 (ManualAdapterTest uses real DB via RefreshDatabase) and pre-existing tests (EventServiceTest, CaseDiscussionServiceTest use Mockery alias mocks without DB). The existing Pest.php config binds Unit tests to `Tests\TestCase` (Laravel base) but does NOT use `DatabaseTruncation` -- only Feature tests do.

Two testing strategies are in play: (1) pure mock-based tests using `Mockery::mock('alias:...')` for services with heavy Eloquent static calls (EventService, CaseDiscussionService pattern), and (2) integration-style unit tests using `RefreshDatabase` for services that are thin wrappers over Eloquent (ManualAdapter pattern). For this phase, services like AuthService and CaseService interact heavily with Eloquent models (User::create, Hash::check, ClinicalCase::create, CaseTeamMember::create), making them candidates for the DB-backed approach. PatientService.getStats() does 9 count queries so also benefits from real DB. RadiogenomicsService has complex query logic (DB::table joins, collection transforms) that is best tested with real data. OncoKbService is the exception -- it wraps an HTTP client call and can be tested purely with Http::fake().

**Primary recommendation:** Use database-backed unit tests (RefreshDatabase trait) for AuthService, PatientService, CaseService, and RadiogenomicsService since their logic is tightly coupled to Eloquent. Use Http::fake() for OncoKbService. This aligns with ManualAdapterTest precedent and avoids brittle Mockery alias chains.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BTEST-08 | Unit tests for AuthService (login, register, password change logic) | AuthService has 4 public methods + generateTempPassword + formatUser. Login checks credentials + is_active. Register has enumeration prevention. Password change validates current, ensures different, revokes tokens. All testable with DB + Http::fake for Resend. |
| BTEST-09 | Unit tests for PatientService (domain count aggregation, patient retrieval) | PatientService delegates to ClinicalDataAdapter. getStats() does 9 domain counts. createPatient() is thin wrapper. Test getStats with seeded clinical data, test adapter injection. |
| BTEST-10 | Unit tests for CaseService (create, update, archive, team management) | CaseService has 6 methods. createCase auto-adds coordinator. archiveCase sets status+closed_at. addTeamMember prevents duplicates. removeTeamMember protects creator. getCasesForUser has 4 filters. All testable with DB. |
| BTEST-11 | Unit tests for RadiogenomicsService (variant classification, panel generation) | RadiogenomicsService.getPatientPanel builds complex panel with variant classification (actionable vs VUS), drug exposure timeline from drug_eras table, correlations via GeneDrugInteraction lookup, and recommendations. Requires seeded patients, variants, imaging, drug_eras, and interactions. |
| BTEST-12 | Unit tests for OncoKbService (connectivity check, response parsing) | OncoKbService.syncInteractions iterates genes, calls OncoKB API, updates sync timestamp. Test with Http::fake for success/failure/mixed scenarios. Test no-token skip path. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Pest | 3.x | Test framework | Already configured in project, Pest.php binds Unit to Tests\TestCase |
| Mockery | 1.x | Mock library | Already used in EventServiceTest, CaseDiscussionServiceTest |
| Laravel Http::fake | built-in | HTTP mocking | Standard for testing external API calls (Resend, OncoKB) |
| Laravel Hash facade | built-in | Password hashing | Used by AuthService, testable via facade |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| RefreshDatabase trait | built-in | DB reset per test | Services with Eloquent dependencies (Auth, Patient, Case, Radiogenomics) |
| Factories (User, ClinicalPatient, GenomicVariant, GeneDrugInteraction, ClinicalCase) | existing | Test data | Already defined in database/factories/ |

## Architecture Patterns

### Recommended Test File Structure
```
backend/tests/Unit/Services/
  AuthServiceTest.php           # BTEST-08 (new)
  PatientServiceTest.php        # BTEST-09 (new)
  CaseServiceTest.php           # BTEST-10 (new)
  RadiogenomicsServiceTest.php  # BTEST-11 (new)
  OncoKbServiceTest.php         # BTEST-12 (new)
  CaseDiscussionServiceTest.php # existing
  EventServiceTest.php          # existing
  ManualAdapterTest.php         # existing
```

### Pattern 1: DB-Backed Unit Tests (AuthService, PatientService, CaseService, RadiogenomicsService)
**What:** Use `RefreshDatabase` trait so tests hit real PostgreSQL with factory-seeded data
**When to use:** Service methods tightly coupled to Eloquent (create, update, query, delete)
**Example:**
```php
<?php
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AuthService;
});

describe('AuthService::login', function () {
    it('returns token and user for valid credentials', function () {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $result = $this->service->login([
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        expect($result)->toHaveKeys(['access_token', 'user']);
        expect($result['user']['email'])->toBe($user->email);
    });

    it('throws for invalid credentials', function () {
        User::factory()->create(['password' => Hash::make('secret123')]);

        $this->service->login([
            'email' => 'wrong@example.com',
            'password' => 'wrong',
        ]);
    })->throws(\RuntimeException::class, 'credentials do not match');
});
```

### Pattern 2: Http::fake for External APIs (OncoKbService)
**What:** Use Laravel's Http::fake to simulate OncoKB API responses
**When to use:** Services calling external HTTP endpoints
**Example:**
```php
<?php
use App\Services\Genomics\OncoKbService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('OncoKbService::syncInteractions', function () {
    it('skips sync when no token configured', function () {
        config(['services.oncokb.token' => null]);
        $service = new OncoKbService;

        $result = $service->syncInteractions();

        expect($result['skipped'])->toBe('no_token');
    });

    it('syncs genes and updates timestamps on success', function () {
        config(['services.oncokb.token' => 'test-token']);
        Http::fake(['oncokb.org/*' => Http::response([/* variant data */], 200)]);
        // Seed GeneDrugInteraction records...
        $service = new OncoKbService;

        $result = $service->syncInteractions();

        expect($result['synced'])->toBeGreaterThan(0);
        expect($result['errors'])->toBe(0);
    });
});
```

### Pattern 3: AuthService Register with Http::fake for Resend
**What:** Mock Resend API to verify email sending without real HTTP calls
**Example:**
```php
describe('AuthService::register', function () {
    it('creates user and sends temp password email', function () {
        config(['services.resend.api_key' => 'test-key']);
        Http::fake(['api.resend.com/*' => Http::response(['id' => 'msg_123'], 200)]);

        $result = $this->service->register([
            'name' => 'Test User',
            'email' => 'new@example.com',
        ]);

        expect($result['message'])->toContain('credentials shortly');
        expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
        Http::assertSent(fn ($request) => $request->url() === 'https://api.resend.com/emails');
    });

    it('returns same message for existing email (enumeration prevention)', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $result = $this->service->register([
            'name' => 'Test',
            'email' => 'existing@example.com',
        ]);

        expect($result['message'])->toContain('credentials shortly');
        // Should NOT create a duplicate
        expect(User::where('email', 'existing@example.com')->count())->toBe(1);
    });
});
```

### Anti-Patterns to Avoid
- **Over-mocking Eloquent with alias mocks:** The existing EventServiceTest/CaseDiscussionServiceTest pattern uses `Mockery::mock('alias:...')` which creates brittle tests tightly coupled to implementation. For services that ARE the business logic (not thin wrappers), prefer DB-backed tests.
- **Testing private methods directly:** RadiogenomicsService has private buildCorrelations/buildRecommendations. Test them indirectly through getPatientPanel.
- **Hardcoding IDs:** Use factory-created models; never assume specific IDs.
- **Forgetting Http::fake:** AuthService::register calls Resend API. Without Http::fake, tests will hit real API or fail with network errors.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Test data | Manual DB::insert | Factories (User::factory, ClinicalPatient::factory, etc.) | Consistent, maintainable, supports states |
| HTTP mocking | cURL interception | Http::fake() | Laravel-native, assertion helpers built in |
| Password hashing for tests | Raw bcrypt calls | Hash::make() / Hash::check() | Respects config (BCRYPT_ROUNDS=4 in testing) |
| Time freezing | Manual Carbon::setTestNow | $this->travel() or Carbon::setTestNow in beforeEach | For archiveCase closed_at assertions |

## Common Pitfalls

### Pitfall 1: Multi-Schema Tables
**What goes wrong:** Tests fail because models reference `app.users`, `app.cases`, `clinical.patients` etc.
**Why it happens:** PostgreSQL multi-schema setup requires correct search_path in .env.testing
**How to avoid:** .env.testing already has DB_HOST=localhost and the clinical/app connection aliases from Phase 1/5 decisions. RefreshDatabase will work with these schemas.
**Warning signs:** "relation does not exist" errors in test output

### Pitfall 2: OncoKbService Constructor Reads Config
**What goes wrong:** OncoKbService reads `config('services.oncokb.token')` in constructor, not at call time
**Why it happens:** The token is set once in __construct()
**How to avoid:** Set config BEFORE instantiating the service: `config(['services.oncokb.token' => 'test']); $service = new OncoKbService;`
**Warning signs:** Token always null despite config() calls

### Pitfall 3: RadiogenomicsService Uses DB::table('drug_eras')
**What goes wrong:** drug_eras table may be in clinical schema, direct DB::table without schema prefix
**Why it happens:** RadiogenomicsService line 40 uses `DB::table('drug_eras')` without explicit schema
**How to avoid:** Ensure drug_eras table exists in the default search_path, or seed data in the correct schema. Check which connection the service uses.
**Warning signs:** "relation drug_eras does not exist" in tests

### Pitfall 4: AuthService Token Creation Requires Sanctum Setup
**What goes wrong:** `$user->createToken('auth_token')` fails without personal_access_tokens table
**Why it happens:** Sanctum tokens are stored in DB
**How to avoid:** RefreshDatabase runs migrations which include Sanctum's migration. Ensure the personal_access_tokens table is in the correct schema.
**Warning signs:** "Table personal_access_tokens does not exist"

### Pitfall 5: CaseService::getCasesForUser Uses Scopes
**What goes wrong:** Tests for getCasesForUser need cases with team members to test the forUser scope
**Why it happens:** forUser scope queries both created_by and teamMembers relationship
**How to avoid:** Seed cases with CaseTeamMember records to test both paths
**Warning signs:** Only testing cases where user is creator, missing team member path

## Code Examples

### AuthService::generateTempPassword (Pure Logic Test)
```php
describe('AuthService::generateTempPassword', function () {
    it('generates password of specified length', function () {
        $service = new AuthService;
        $password = $service->generateTempPassword(12);
        expect(strlen($password))->toBe(12);
    });

    it('excludes ambiguous characters', function () {
        $service = new AuthService;
        $ambiguous = ['I', 'l', 'O', '0', '1'];
        // Generate many passwords and check none contain ambiguous chars
        for ($i = 0; $i < 50; $i++) {
            $password = $service->generateTempPassword(20);
            foreach ($ambiguous as $char) {
                expect($password)->not->toContain($char);
            }
        }
    });
});
```

### CaseService::addTeamMember Duplicate Prevention
```php
describe('CaseService::addTeamMember', function () {
    it('throws when user is already a team member', function () {
        $user = User::factory()->create();
        $case = ClinicalCase::factory()->create(['created_by' => $user->id]);
        CaseTeamMember::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'role' => 'coordinator',
            'invited_at' => now(),
        ]);

        $service = new CaseService;
        $service->addTeamMember($case, $user->id, 'reviewer');
    })->throws(\InvalidArgumentException::class, 'already a team member');
});
```

### RadiogenomicsService::getPatientPanel (Complex Integration)
```php
describe('RadiogenomicsService::getPatientPanel', function () {
    it('returns empty array for non-existent patient', function () {
        $service = new RadiogenomicsService;
        expect($service->getPatientPanel(99999))->toBeEmpty();
    });

    it('classifies variants as actionable vs VUS', function () {
        $patient = ClinicalPatient::factory()->create();
        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'BRAF',
            'clinical_significance' => 'pathogenic',
        ]);
        GenomicVariant::factory()->create([
            'patient_id' => $patient->id,
            'gene' => 'TP53',
            'clinical_significance' => 'VUS',
        ]);

        $service = new RadiogenomicsService;
        $panel = $service->getPatientPanel($patient->id);

        expect($panel['variants']['pathogenic_count'])->toBe(1);
        expect($panel['variants']['vus_count'])->toBe(1);
    });
});
```

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 3.x (PHPUnit 11 backend) |
| Config file | backend/phpunit.xml + backend/tests/Pest.php |
| Quick run command | `cd backend && php artisan test --testsuite=Unit --filter=ServiceName` |
| Full suite command | `cd backend && php artisan test` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| BTEST-08 | AuthService login, register, password change, temp password gen | unit | `cd backend && php artisan test tests/Unit/Services/AuthServiceTest.php -x` | Wave 0 |
| BTEST-09 | PatientService domain counts, adapter delegation | unit | `cd backend && php artisan test tests/Unit/Services/PatientServiceTest.php -x` | Wave 0 |
| BTEST-10 | CaseService CRUD, archive, team management | unit | `cd backend && php artisan test tests/Unit/Services/CaseServiceTest.php -x` | Wave 0 |
| BTEST-11 | RadiogenomicsService variant classification, panel, correlations | unit | `cd backend && php artisan test tests/Unit/Services/RadiogenomicsServiceTest.php -x` | Wave 0 |
| BTEST-12 | OncoKbService sync with/without token, HTTP success/failure | unit | `cd backend && php artisan test tests/Unit/Services/OncoKbServiceTest.php -x` | Wave 0 |

### Sampling Rate
- **Per task commit:** `cd backend && php artisan test tests/Unit/Services/ -x`
- **Per wave merge:** `cd backend && php artisan test`
- **Phase gate:** Full suite green (101 existing + new unit tests)

### Wave 0 Gaps
- [ ] `backend/tests/Unit/Services/AuthServiceTest.php` -- covers BTEST-08
- [ ] `backend/tests/Unit/Services/PatientServiceTest.php` -- covers BTEST-09
- [ ] `backend/tests/Unit/Services/CaseServiceTest.php` -- covers BTEST-10
- [ ] `backend/tests/Unit/Services/RadiogenomicsServiceTest.php` -- covers BTEST-11
- [ ] `backend/tests/Unit/Services/OncoKbServiceTest.php` -- covers BTEST-12

No framework install needed -- Pest, Mockery, factories all exist.

## Test Count Estimates

| Service | Estimated Tests | Key Test Areas |
|---------|----------------|----------------|
| AuthService | ~12-15 | login (valid, invalid, inactive), register (new, existing, email send), changePassword (valid, wrong current, same password), logout, generateTempPassword (length, chars), formatUser |
| PatientService | ~5-7 | getStats (with data, empty), getProfile (delegates to adapter), searchPatients, createPatient |
| CaseService | ~12-15 | createCase (creates + auto-coordinator), updateCase, archiveCase (status + closed_at), addTeamMember (success, duplicate), removeTeamMember (success, creator protection, not found), getCasesForUser (filters: status, specialty, urgency, search) |
| RadiogenomicsService | ~8-10 | getPatientPanel (not found, with variants, classification, drug exposures, correlations, recommendations, imaging), edge cases (no interactions, no drug_eras) |
| OncoKbService | ~5-6 | syncInteractions (no token, success, failure, mixed, empty genes) |
| **Total** | **~42-53** | |

## Sources

### Primary (HIGH confidence)
- Direct code inspection of all 5 service files in backend/app/Services/
- Existing test patterns in backend/tests/Unit/Services/ (ManualAdapterTest, EventServiceTest, CaseDiscussionServiceTest)
- Pest.php configuration showing Unit suite uses Tests\TestCase without DatabaseTruncation
- phpunit.xml configuration with testing environment variables
- Factory definitions for User, ClinicalPatient, GenomicVariant, GeneDrugInteraction, ClinicalCase

### Secondary (MEDIUM confidence)
- Phase 5 SUMMARY files documenting test patterns and decisions (assertion paths, response shapes)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all tools already installed and configured in project
- Architecture: HIGH - existing unit test patterns provide clear precedent
- Pitfalls: HIGH - identified from direct code reading (multi-schema, constructor config, Sanctum tokens)
- Test count estimates: MEDIUM - based on method analysis, actual count depends on edge case granularity

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable -- testing infrastructure unlikely to change)
