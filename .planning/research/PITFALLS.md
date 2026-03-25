# Domain Pitfalls: Testing a Multi-Service Clinical Platform

**Domain:** Brownfield clinical platform stabilization and testing
**Researched:** 2026-03-25

## Critical Pitfalls

Mistakes that cause rewrites or major delays in achieving the 80% coverage target.

### Pitfall 1: PostgreSQL Schema-Qualified Tables Break Test Database

**What goes wrong:** Aurora uses `search_path = app,clinical,public` on a single PostgreSQL connection. Tests using `RefreshDatabase` may fail because migrations create tables across multiple schemas, and the test database needs identical schema setup.
**Why it happens:** Laravel's `RefreshDatabase` runs migrations but does not automatically create custom schemas (`app`, `clinical`). The `pgsql` connection config has `search_path` set, but the schemas must exist first.
**Consequences:** All Feature tests fail with "relation does not exist" errors. Developers waste hours debugging what looks like a migration issue.
**Prevention:** Ensure test migrations include `CREATE SCHEMA IF NOT EXISTS app; CREATE SCHEMA IF NOT EXISTS clinical;` before table creation. Add a `before_migrate` step or a test bootstrap that creates schemas. Verify in CI.
**Detection:** First Feature test run fails with PostgreSQL schema errors.

### Pitfall 2: The `clinical` Connection Alias Problem

**What goes wrong:** CaseController uses `exists:clinical.patients,id` validation, which Laravel interprets as "use database connection named `clinical`" rather than "use schema `clinical`". This is the known 500 error documented in PROJECT.md.
**Why it happens:** Laravel's `exists` rule syntax is `exists:connection.table,column`. The dot is a connection separator, not a schema separator. PostgreSQL schema-qualified names use the same dot syntax.
**Consequences:** Every Case-related endpoint returns 500. Feature tests for cases all fail.
**Prevention:** Either add a `clinical` connection alias in `config/database.php` pointing to the same database with `search_path = clinical,app,public`, or rewrite validation rules to use `exists:patients,id` (relying on `search_path`).
**Detection:** `POST /api/cases` returns 500 with "could not find driver" or "connection clinical not configured".

### Pitfall 3: Frontend Tests Without MSW Get Entangled with Backend State

**What goes wrong:** Frontend component tests that import hooks making real API calls (via TanStack Query) fail unpredictably because there is no running backend during `vitest run`.
**Why it happens:** Without MSW or similar network-level mocking, axios calls to `/api/*` hit `localhost` which either times out or returns unexpected responses. Tests become non-deterministic.
**Consequences:** Flaky tests. Developers start adding `vi.mock('axios')` everywhere, creating brittle mocks that don't match the real API contract.
**Prevention:** Set up MSW from day one. Create the `src/test/mocks/handlers.ts` file with default handlers for all endpoints before writing any component tests.
**Detection:** Frontend tests pass locally sometimes but fail in CI, or pass when backend is running but fail when it is not.

### Pitfall 4: Mixing Unit and Feature Test Expectations

**What goes wrong:** Writing tests that mock Eloquent in `tests/Feature/` (where real database should be used) or writing tests that hit the database in `tests/Unit/` (where mocking should be used).
**Why it happens:** Unclear boundary between Unit and Feature test directories. Developers put endpoint tests in Unit or mock everything in Feature tests.
**Consequences:** Unit tests are slow (hitting DB), Feature tests are brittle (mocks don't match reality), coverage numbers are misleading.
**Prevention:** Enforce via `Pest.php`: Feature tests get `RefreshDatabase`, Unit tests do not. Document the rule: "If it uses `$this->getJson()`, it is a Feature test."
**Detection:** Unit test suite takes >30s (should be <5s). Feature tests pass but endpoints fail in production.

## Moderate Pitfalls

### Pitfall 5: PCOV Not Installed in Docker Container

**What goes wrong:** Running `pest --coverage` outputs "No code coverage driver available" or silently produces 0% coverage.
**Prevention:** Add `RUN pecl install pcov && docker-php-ext-enable pcov` to `docker/php/Dockerfile`. Verify with `php -m | grep pcov` inside the container. Do NOT install both Xdebug and PCOV (they conflict).

### Pitfall 6: Vitest Config Not in vite.config.ts

**What goes wrong:** Vitest runs but cannot resolve `@/` path aliases, does not find test files, or does not load Tailwind/React plugins.
**Prevention:** Add the `test` block directly in `vite.config.ts` (not a separate `vitest.config.ts`) so Vitest inherits all Vite plugins and path aliases. The existing `vite.config.ts` already has the `@` alias and `react()` plugin.

### Pitfall 7: TanStack Query Retry Loops in Tests

**What goes wrong:** Tests hang or timeout because TanStack Query retries failed requests 3 times with exponential backoff (default behavior).
**Prevention:** Create a test-specific QueryClient with `retry: false` and `gcTime: 0`. Use this in every hook and component test via a wrapper function.

### Pitfall 8: Playwright Tests Against Production URL

**What goes wrong:** The existing `playwright.config.ts` defaults `baseURL` to `https://aurora.acumenus.net`. Running E2E tests modifies production data.
**Prevention:** Change default to `http://localhost:8085` (the Docker dev URL). Only point to production via explicit `BASE_URL` env var for smoke tests. Add a safeguard: E2E tests should only write to test accounts.

### Pitfall 9: pytest-asyncio Mode Not Set

**What goes wrong:** Async test functions silently skip or fail with "coroutine was never awaited" warnings.
**Prevention:** Set `asyncio_mode = auto` in `pytest.ini`. This makes all async functions automatically recognized as async tests without needing the `@pytest.mark.asyncio` decorator on each one.

### Pitfall 10: Coverage Reports Not Gitignored

**What goes wrong:** Generated HTML coverage reports, XML files, and `.nyc_output` directories get committed, creating large diffs and merge conflicts.
**Prevention:** Add to `.gitignore`: `coverage/`, `playwright-report/`, `.nyc_output/`, `htmlcov/`.

## Minor Pitfalls

### Pitfall 11: Sanctum Token Testing Requires actingAs

**What goes wrong:** Tests try to create tokens via `POST /api/auth/login` for every test, making tests slow and dependent on the auth endpoint working.
**Prevention:** Use `$this->actingAs($user, 'sanctum')` for tests that are not specifically testing auth. Reserve login flow testing for auth-specific tests.

### Pitfall 12: Missing UserFactory Defaults

**What goes wrong:** `User::factory()->create()` creates users with random data that may not satisfy custom validation (e.g., `must_change_password`, `is_active`, `role`).
**Prevention:** Verify `UserFactory` includes sensible defaults for all custom fields: `must_change_password: false`, `is_active: true`, `role: 'user'`.

### Pitfall 13: MSW Unhandled Request Mode

**What goes wrong:** Requests to unhandled URLs silently pass through, making tests pass even when API calls are wrong.
**Prevention:** Set `server.listen({ onUnhandledRequest: 'error' })` in the setup file. This forces every API call in tests to have a matching handler, catching typos and missing endpoints.

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Test infrastructure setup | PCOV not in Docker, Vitest config missing | Install PCOV first, add test block to vite.config.ts |
| Backend feature tests | Schema/connection issues with `clinical` | Fix the connection alias BEFORE writing tests |
| Frontend component tests | No MSW, axios calls fail | Set up MSW handlers before writing any component tests |
| Frontend hook tests | QueryClient retry loops | Create test wrapper with retry:false from day one |
| AI service tests | Missing pytest.ini, no async mode | Create pytest.ini with asyncio_mode=auto |
| E2E tests | Tests against production URL | Change Playwright default to localhost |
| CI coverage gates | Coverage reports not generated in CI | Verify PCOV, V8, pytest-cov all produce XML output |
| Coverage merging | Different report formats across services | Standardize on Clover XML for PHP/JS, Cobertura for Python |

## Sources

- Aurora codebase analysis (`.planning/codebase/TESTING.md`, `phpunit.xml`, `vite.config.ts`, `playwright.config.ts`)
- [Laravel Testing RefreshDatabase](https://laravel.com/docs/11.x/testing#resetting-the-database-after-each-test) -- Schema refresh behavior
- [Pest PHP Configuration](https://pestphp.com/docs/configuring-tests) -- uses() trait binding
- [Vitest Configuration](https://vitest.dev/config/) -- Test block in vite.config
- [MSW Node Integration](https://mswjs.io/docs/integrations/node/) -- onUnhandledRequest setting
- [TanStack Query Testing](https://tanstack.com/query/latest/docs/framework/react/guides/testing) -- Test QueryClient configuration

---

*Pitfalls research: 2026-03-25*
