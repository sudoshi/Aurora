# Phase 1: Fix Critical Blocker & Verify Core Endpoints - Research

**Researched:** 2026-03-25
**Domain:** Laravel database configuration, API endpoint verification, PostgreSQL multi-schema
**Confidence:** HIGH

## Summary

The critical blocker is a missing `clinical` database connection alias in `backend/config/database.php`. Laravel's `exists:clinical.patients,id` validation rule interprets the `clinical.patients` syntax as `connection_name.table_name`, meaning it looks for a database connection named `clinical` -- which does not exist. The fix is to add a `clinical` connection entry that points to the same PostgreSQL instance but with the `clinical` schema in its `search_path`.

Once the database connection alias is added, all seven BUG requirements can be verified sequentially: auth endpoints (login, register, change-password), dashboard stats, patient CRUD, and case CRUD. The auth endpoints do not reference the `clinical` connection directly, but the CONCERNS.md reports they also 500 -- this may be caused by middleware or other initialization errors, or may have been a transient observation. The auth code itself is clean and should work once the database connection is properly configured.

**Primary recommendation:** Add a `clinical` connection alias to `config/database.php` pointing to the same PostgreSQL credentials with `search_path` set to `clinical,public`, then verify each endpoint via curl/artisan tinker.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BUG-01 | Add `clinical` database connection alias to `config/database.php` so `exists:clinical.patients,id` validation resolves | Core fix: add connection entry to database.php. See Architecture Patterns section for exact configuration. |
| BUG-02 | Verify `/api/login` returns 200 with valid credentials after DB fix | AuthController.login() delegates to AuthService.login(). Code is clean. Verify with curl after BUG-01 fix. |
| BUG-03 | Verify `/api/register` returns success response for new email | AuthController.register() delegates to AuthService.register(). Code is clean. Needs RESEND_API_KEY in .env (non-fatal if missing). |
| BUG-04 | Verify `/api/change-password` works under auth | AuthController.changePassword() requires auth:sanctum token. Must login first (BUG-02), then test with token. |
| BUG-05 | Verify `/api/dashboard` returns patient counts without error | DashboardController.stats() uses raw `DB::table('clinical.patients')` -- this uses the default pgsql connection with search_path including clinical. Should work without the alias. Verify. |
| BUG-06 | Verify `/api/patients` CRUD endpoints respond correctly | PatientController.index() queries ClinicalPatient model (table `patients` in clinical schema via search_path). PatientController.store() has `unique:patients,mrn` -- resolves via search_path. Verify both. |
| BUG-07 | Verify `/api/cases` CRUD endpoints respond correctly -- the validation fix target | CaseController.store() and update() use `exists:clinical.patients,id` -- this is THE validation that triggers the 500. Fixed by BUG-01. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel | 11.31 | Backend framework | Project's existing framework |
| Laravel Sanctum | 4.0 | Token-based API auth | Already configured and in use |
| PostgreSQL | 16 | Database with multi-schema | Already running with app, clinical, commons schemas |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Pest | 3.8 | PHP testing | Smoke tests to verify endpoints post-fix |
| curl / Artisan tinker | N/A | Manual verification | Quick endpoint testing during development |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Adding `clinical` connection alias | Changing validation to `exists:pgsql.clinical.patients,id` | Would not work -- Laravel validation `exists` rule only supports `connection.table` not `connection.schema.table` |
| Adding `clinical` connection alias | Removing `exists` validation entirely | Loses referential integrity check at validation layer |
| Adding `clinical` connection alias | Using Rule::exists() with explicit connection | More code change, less standard |

## Architecture Patterns

### The Fix: `clinical` Database Connection Alias

**What:** Add a `clinical` key to `config/database.php` `connections` array that mirrors the `pgsql` connection but sets `search_path` to `clinical,public`.

**Why:** Laravel's `exists:clinical.patients,id` validation rule parses `clinical` as a database connection name and `patients` as the table. Without a connection named `clinical`, Laravel throws `InvalidArgumentException: Database [clinical] not configured`.

**Exact configuration to add:**

```php
// In config/database.php, inside 'connections' array, after 'pgsql':
'clinical' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'clinical,public',
    'sslmode' => 'prefer',
],
```

**Key insight:** The `pgsql` connection already has `search_path` set to `app,clinical,public` (line 96 of database.php). This means queries like `DB::table('clinical.patients')` in DashboardController work fine because PostgreSQL resolves `clinical.patients` as schema-qualified. But Laravel's validation `exists` rule treats the dot as a connection/table separator, not schema/table.

### Route Mapping for Verification

| Route | Method | Controller | Auth Required | Depends On |
|-------|--------|------------|---------------|------------|
| `/api/auth/login` | POST | AuthController@login | No (throttled) | DB connection only |
| `/api/auth/register` | POST | AuthController@register | No (throttled) | DB + Resend API (non-fatal) |
| `/api/auth/change-password` | POST | AuthController@changePassword | Yes (sanctum) | Valid token from login |
| `/api/dashboard/stats` | GET | DashboardController@stats | Yes (sanctum) | clinical.patients table, app.cases table |
| `/api/patients` | GET | PatientController@index | Yes (sanctum) | ClinicalPatient model (clinical schema) |
| `/api/patients` | POST | PatientController@store | Yes (sanctum) | `unique:patients,mrn` validation |
| `/api/cases` | GET | CaseController@index | Yes (sanctum) | CaseService, app.cases table |
| `/api/cases` | POST | CaseController@store | Yes (sanctum) | `exists:clinical.patients,id` -- THE blocker |
| `/api/cases/{id}` | PUT | CaseController@update | Yes (sanctum) | `exists:clinical.patients,id` -- THE blocker |
| `/api/cases/{id}` | DELETE | CaseController@destroy | Yes (sanctum) | Soft delete, no clinical ref |

### Verification Order (dependency chain)

1. **BUG-01**: Add `clinical` connection alias -- config change only
2. **BUG-02**: POST `/api/auth/login` with admin@acumenus.net / superuser -- must return 200 + token
3. **BUG-03**: POST `/api/auth/register` with new email -- must return success message
4. **BUG-04**: POST `/api/auth/change-password` with token from step 2 -- must return 200 + new token
5. **BUG-05**: GET `/api/dashboard/stats` with token -- must return patient counts
6. **BUG-06**: GET `/api/patients` with token -- must return patient list; POST `/api/patients` must create
7. **BUG-07**: POST `/api/cases` with token and `patient_id` -- must succeed without 500

### Anti-Patterns to Avoid
- **Do NOT change the `pgsql` search_path:** It already includes `clinical` and other code depends on this.
- **Do NOT use schema-qualified table names in validation rules:** Laravel validation does not support `connection.schema.table` -- only `connection.table`.
- **Do NOT remove the `exists` validation:** It provides referential integrity at the validation layer.
- **Do NOT use `DB::connection('clinical')` in controllers:** Keep using models with the default `pgsql` connection and its search_path. The `clinical` connection is ONLY for validation rules.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Database connection aliasing | Custom validation rule for cross-schema exists | Laravel's built-in connection alias in database.php | Standard Laravel pattern, zero code changes beyond config |
| API endpoint testing | Manual browser testing | curl commands or Pest feature tests | Reproducible, scriptable verification |
| Auth token management | Custom token logic | Laravel Sanctum (already configured) | Already working, battle-tested |

## Common Pitfalls

### Pitfall 1: Config Cache Stale After database.php Change
**What goes wrong:** After adding the `clinical` connection, `php artisan config:cache` is not re-run, so the app uses stale cached config.
**Why it happens:** Laravel caches config in `bootstrap/cache/config.php`. Docker containers or production deployments may have stale cache.
**How to avoid:** Run `php artisan config:clear` (or `config:cache`) after modifying database.php. In Docker: `docker compose exec php php artisan config:clear`.
**Warning signs:** Still getting "Database [clinical] not configured" after adding the connection.

### Pitfall 2: PatientController.store() `unique:patients,mrn` May Need Connection Prefix
**What goes wrong:** The `unique:patients,mrn` validation on PatientController line 112 does not specify a connection. It resolves against the default `pgsql` connection.
**Why it happens:** The default `pgsql` connection has `search_path: app,clinical,public`. PostgreSQL will search schemas in order and find `clinical.patients` first.
**How to avoid:** Test that `POST /api/patients` with a duplicate MRN returns 422 (not 500). If PostgreSQL resolves the table correctly via search_path, no change needed.
**Warning signs:** 500 error on patient creation with unique constraint violation details.

### Pitfall 3: DashboardController Uses Schema-Qualified Table Names
**What goes wrong:** `DB::table('clinical.patients')` works differently from `exists:clinical.patients` in validation.
**Why it happens:** In raw DB queries, `clinical.patients` is PostgreSQL schema-qualified (schema.table). In validation rules, Laravel parses it as `connection.table`. These are completely different resolution paths.
**How to avoid:** Understand that the DashboardController does NOT need the `clinical` connection alias. Only validation rules with `exists:` or `unique:` syntax do.
**Warning signs:** Confusion about why DashboardController works but CaseController doesn't.

### Pitfall 4: Auth Endpoints May Appear Broken Due to Other Issues
**What goes wrong:** CONCERNS.md states "even /api/login fails" but AuthController code does not reference the clinical connection.
**Why it happens:** Possibly a transient observation during the analysis, or middleware/service-provider initialization that touches the clinical connection on bootstrap.
**How to avoid:** Fix BUG-01 first, then test auth endpoints independently. If auth still fails, check Laravel logs at `storage/logs/laravel.log` for the actual exception.
**Warning signs:** 500 on login even after adding the clinical connection.

### Pitfall 5: Superuser Password Change Would Lock Out Testing
**What goes wrong:** If BUG-04 (change-password) is tested with the admin account, the superuser password changes and subsequent tests fail.
**Why it happens:** changePassword() revokes all tokens and sets a new password.
**How to avoid:** Use a test user (registered via BUG-03) for password change verification, NOT the admin@acumenus.net superuser. Or test with admin but use the new password/token for subsequent steps.
**Warning signs:** "The provided credentials do not match our records" on login after testing change-password.

## Code Examples

### Adding the Clinical Connection (BUG-01 Fix)

```php
// backend/config/database.php - Add after the 'pgsql' connection block (line 98)
'clinical' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'clinical,public',
    'sslmode' => 'prefer',
],
```

### Verification curl Commands

```bash
# BUG-02: Login
curl -s -X POST http://localhost:8085/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@acumenus.net","password":"superuser"}' | jq .

# BUG-03: Register (new user)
curl -s -X POST http://localhost:8085/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test-verify@example.com"}' | jq .

# BUG-04: Change password (use token from login)
TOKEN="<token_from_login>"
curl -s -X POST http://localhost:8085/api/auth/change-password \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"current_password":"superuser","password":"NewPass123!","password_confirmation":"NewPass123!"}' | jq .

# BUG-05: Dashboard stats
curl -s http://localhost:8085/api/dashboard/stats \
  -H "Authorization: Bearer $TOKEN" | jq .

# BUG-06: Patient list
curl -s http://localhost:8085/api/patients \
  -H "Authorization: Bearer $TOKEN" | jq .

# BUG-06: Patient create
curl -s -X POST http://localhost:8085/api/patients \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"mrn":"TEST-001","first_name":"Test","last_name":"Patient"}' | jq .

# BUG-07: Case create (with patient_id to trigger the fixed validation)
curl -s -X POST http://localhost:8085/api/cases \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"title":"Test Case","specialty":"oncology","case_type":"tumor_board","patient_id":1}' | jq .
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Schema-qualified tables in validation | Connection aliases in database.php | Laravel convention (long-standing) | Must use connection.table syntax in validation, not schema.table |
| Single search_path for all queries | Multiple connection configs per schema | Project-specific pattern | Allows validation rules to target specific schemas |

## Open Questions

1. **Why does CONCERNS.md state login also fails?**
   - What we know: AuthController code does not reference the `clinical` connection. Login should work independently.
   - What's unclear: Whether there's a service provider or middleware that initializes clinical models on every request.
   - Recommendation: Fix BUG-01 first, test login. If login still fails, check `storage/logs/laravel.log` for the actual stack trace.

2. **Does `unique:patients,mrn` resolve correctly via search_path?**
   - What we know: The default `pgsql` connection has `search_path: app,clinical,public`. PostgreSQL searches schemas in order.
   - What's unclear: Whether Laravel's validation sends a bare `SELECT * FROM patients WHERE mrn = ?` (which PostgreSQL resolves via search_path) or qualifies it.
   - Recommendation: Test patient creation after BUG-01 fix. If it 500s, add `unique:clinical.patients,mrn` using the new connection alias.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 3.8 (atop PHPUnit 11.0) |
| Config file | `backend/phpunit.xml` (assumed standard Laravel) |
| Quick run command | `cd backend && php artisan test --filter=AuthenticationTest` |
| Full suite command | `cd backend && php artisan test` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| BUG-01 | Clinical connection resolves in validation | smoke | `curl -s -X POST localhost:8085/api/cases -H "Authorization: Bearer $TOKEN" -d '{"title":"t","specialty":"oncology","case_type":"tumor_board","patient_id":1}'` | No (manual verification) |
| BUG-02 | Login returns 200 + token | smoke | `curl -s -X POST localhost:8085/api/auth/login -d '{"email":"admin@acumenus.net","password":"superuser"}'` | Partial: `tests/Feature/Auth/AuthenticationTest.php` exists |
| BUG-03 | Register returns success | smoke | `curl -s -X POST localhost:8085/api/auth/register -d '{"name":"Test","email":"new@test.com"}'` | Partial: `tests/Feature/Auth/AuthenticationTest.php` exists |
| BUG-04 | Change password returns 200 + new token | smoke | Manual with token | Partial: `tests/Feature/Auth/AuthenticationTest.php` exists |
| BUG-05 | Dashboard stats returns counts | smoke | `curl -s localhost:8085/api/dashboard/stats -H "Authorization: Bearer $TOKEN"` | No |
| BUG-06 | Patient CRUD works | smoke | `curl` GET + POST /api/patients | Partial: `tests/Feature/Api/PatientTest.php` exists |
| BUG-07 | Case CRUD works without 500 | smoke | `curl` POST /api/cases with patient_id | No |

### Sampling Rate
- **Per task commit:** Run curl verification commands for affected endpoint
- **Per wave merge:** Full `php artisan test` suite
- **Phase gate:** All 7 curl verification commands return expected status codes

### Wave 0 Gaps
- None for this phase -- this is a config fix + manual verification phase. Automated tests are Phase 3-5 scope.

## Sources

### Primary (HIGH confidence)
- `backend/config/database.php` -- Direct inspection, confirmed missing `clinical` connection
- `backend/app/Http/Controllers/CaseController.php` lines 50, 104 -- Confirmed `exists:clinical.patients,id` validation rules
- `backend/app/Http/Controllers/AuthController.php` -- Confirmed no clinical connection reference
- `backend/app/Http/Controllers/DashboardController.php` -- Confirmed raw DB::table('clinical.patients') usage
- `backend/app/Http/Controllers/PatientController.php` -- Confirmed `unique:patients,mrn` without connection prefix
- Laravel documentation: validation `exists` rule uses `connection.table` syntax (well-known Laravel convention)

### Secondary (MEDIUM confidence)
- `.planning/codebase/CONCERNS.md` -- Reports all endpoints return 500, but auth code analysis contradicts this for auth-only endpoints

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Direct code inspection, no external dependencies to verify
- Architecture: HIGH - Laravel database connection aliasing is a well-documented pattern
- Pitfalls: HIGH - Based on direct code analysis and known Laravel behaviors

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable -- config fix, not library-dependent)
