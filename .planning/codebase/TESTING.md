# Testing Patterns

**Analysis Date:** 2026-03-24

## Test Framework

**Runner (Backend):**
- **Pest** (v3.8) — Modern PHP testing framework (preferred over PHPUnit directly)
- **PHPUnit** (v11.0.1) — Underlying test engine for Pest
- **Config:** `backend/phpunit.xml`
- **Namespace:** `Tests\` (configured in `composer.json` autoload-dev)

**Run Commands:**
```bash
./vendor/bin/pest run                      # Run all tests
./vendor/bin/pest run --watch              # Watch mode (re-runs on file change)
./vendor/bin/pest run --coverage           # Coverage report
./vendor/bin/pest --testdox                # Test documentation output
```

**Runner (Frontend):**
- **Vitest** (v3.0) — Vite-native unit testing
- **Testing Library** (@testing-library/react v16.0, @testing-library/jest-dom v6.0) — Component testing
- **Config:** Configured in `vitest.config.ts` (no separate vitest config file, uses Vite)
- **jsdom:** v25.0 — DOM simulation for unit tests

**Run Commands:**
```bash
npm test                    # Run all tests (vitest run)
npm run test:watch         # Watch mode
npm run build              # Builds with tsc --noEmit (type checking)
```

**Runner (Python/AI Service):**
- **Pytest** (v8.3.0) — Testing framework
- **TestClient:** FastAPI's `TestClient` for endpoint testing
- **Config:** No `pytest.ini` — uses defaults, test discovery via `tests/test_*.py`

**Run Commands:**
```bash
pytest tests/              # Run all tests
pytest tests/ -v           # Verbose output
```

**Assertion Library:**
- **Backend:** Pest's `expect()` function (provides fluent assertions: `expect($value)->toBeTrue()`)
- **Frontend:** Testing Library with jest-dom assertions (e.g., `expect(element).toBeInTheDocument()`)
- **Python:** Pytest's standard assertions (`assert response.status_code == 200`)

## Test File Organization

**Location (Backend):**
- **Unit tests:** `backend/tests/Unit/` (non-HTTP logic, services, utilities)
- **Feature tests:** `backend/tests/Feature/` (API endpoints, HTTP requests, database)
- Test suites defined in `phpunit.xml`:
  ```xml
  <testsuite name="Unit">
      <directory>tests/Unit</directory>
  </testsuite>
  <testsuite name="Feature">
      <directory>tests/Feature</directory>
  </testsuite>
  ```

**Location (Frontend):**
- **No test files currently in codebase** — Vitest configured but no tests written yet
- **Expected location:** `src/**/*.test.ts` or `src/**/*.spec.ts` (Vitest discovers these)
- **Pattern:** Co-located with source code (next to component/utility being tested)

**Location (Python/AI):**
- **Tests:** `ai/tests/test_*.py`
- **Example:** `ai/tests/test_health.py` (tests health endpoint)

**Naming:**
- **Backend:** `*Test.php` suffix (e.g., `AuthenticationTest.php`, `EventServiceTest.php`)
- **Frontend:** `*.test.ts` or `*.spec.ts` suffix
- **Python:** `test_*.py` prefix

**Structure:**
```
backend/
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   │   ├── EventServiceTest.php
│   │   │   └── CaseDiscussionServiceTest.php
│   │   └── ExampleTest.php
│   ├── Feature/
│   │   ├── Auth/
│   │   │   └── AuthenticationTest.php
│   │   ├── Api/
│   │   │   ├── PatientTest.php
│   │   │   └── EventTest.php
│   │   └── ExampleTest.php
│   ├── TestCase.php         # Base class
│   └── Pest.php             # Configuration
```

## Test Structure

**Suite Organization (Backend — Pest):**
```php
// File: tests/Feature/Auth/AuthenticationTest.php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

// Global setup runs before all tests in this file
beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
});

// Group of related tests
describe('POST /api/auth/login', function () {
    // Individual test
    it('superuser can login', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@acumenus.net',
            'password' => 'superuser',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user'])
            ->assertJsonPath('user.email', 'admin@acumenus.net');
    });

    it('login with wrong password returns 401', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@acumenus.net',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    });
});
```

**Patterns:**
- **Setup:** `beforeEach(function () { ... })` runs before each test
- **Grouping:** `describe('feature', function () { ... })` groups related tests
- **Individual test:** `it('does something', function () { ... })`
- **Assertions:** Fluent assertions on response objects: `->assertStatus()`, `->assertJsonPath()`
- **Database:** `RefreshDatabase` trait in `Pest.php` resets DB between tests

**Suite Organization (Frontend — Vitest/RTL):**
```typescript
// Expected pattern (none currently exist)
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { Modal } from "@/components/ui/Modal";

describe("Modal component", () => {
  it("renders when open is true", () => {
    render(<Modal open={true} onClose={() => {}} title="Test" />);
    expect(screen.getByText("Test")).toBeInTheDocument();
  });

  it("calls onClose when Escape is pressed", async () => {
    const onClose = vi.fn();
    render(<Modal open={true} onClose={onClose} />);
    await userEvent.keyboard("{Escape}");
    expect(onClose).toHaveBeenCalled();
  });
});
```

**Suite Organization (Python — Pytest):**
```python
# File: ai/tests/test_health.py
from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def test_health_endpoint():
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"
    assert data["service"] == "aurora-ai"
```

## Mocking

**Framework (Backend):**
- **Mockery** (v1.6) — PHP mocking library
- Configured in Pest via trait: `uses()->group('mockery-alias')` enables `Mockery::` shorthand

**Patterns (Backend):**
```php
// Mock a model class
$mock = Mockery::mock('alias:'.Event::class);
$mock->shouldReceive('with')->with(['teamMembers', 'patients'])->andReturn($query);

// Mock Eloquent builder
$query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
$query->shouldReceive('orderBy')->with('time', 'desc')->andReturnSelf();
$query->shouldReceive('paginate')->with(15)->andReturn($paginator);

// Mock paginator
$paginator = Mockery::mock(LengthAwarePaginator::class);

// Service test with mocked dependencies
beforeEach(function () {
    $this->service = new EventService;
});

it('returns paginated results', function () {
    // Setup mocks
    $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
    $query->shouldReceive('orderBy')->andReturnSelf();

    // Call service
    $result = $this->service->list();

    // Assert
    expect($result)->toBe($paginator);
});
```

**HTTP Mocking (Backend):**
```php
// Mock HTTP responses (e.g., for Resend API)
Http::fake([
    'api.resend.com/*' => Http::response(['id' => 'fake-id'], 200),
]);

// Now any HTTP call to api.resend.com/* returns the mocked response
```

**Framework (Frontend):**
- **Vitest:** Built-in `vi.fn()` for function mocking
- **Testing Library:** Uses `@testing-library/jest-dom` for DOM assertions

**Example (Frontend — expected pattern):**
```typescript
import { vi } from 'vitest';

it('calls onClose when Escape is pressed', async () => {
    const onClose = vi.fn();
    render(<Modal open={true} onClose={onClose} />);
    await userEvent.keyboard("{Escape}");
    expect(onClose).toHaveBeenCalled();
});
```

**What to Mock:**
- External API calls (HTTP requests, email service)
- Database queries in unit tests (inject mocked repositories)
- Time/dates via Vitest's `vi.useFakeTimers()`
- Window/DOM APIs in unit tests

**What NOT to Mock:**
- Core business logic (test real behavior, not implementation)
- Internal service methods (integrate with real units)
- Validation logic (test with real data)
- Eloquent builders in Feature tests (use real test database)

## Fixtures and Factories

**Test Data (Backend):**
```php
// Laravel factories generate test data
$user = User::factory()->create([
    'email' => 'newdoc@acumenus.net',
    'password' => Hash::make('TempPass123!'),
    'must_change_password' => true,
    'is_active' => true,
]);

// Seeders set up initial state
beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
});
```

**Location:**
- Factories: `backend/database/factories/`
- Seeders: `backend/database/seeders/`
- Test setup: In test file via `beforeEach()`

**Patterns:**
- Factories use `User::factory()->create()` to persist, `make()` to create in-memory only
- Override defaults: `User::factory()->create(['is_active' => false])`
- Test seeds run `SuperuserSeeder` for consistent admin account

## Coverage

**Requirements:**
- **Target:** 80%+ coverage (enforced in CI)
- **Tools:**
  - Backend: `--coverage` flag in Pest (uses Xdebug/PCOV)
  - Frontend: Vitest coverage (requires configuration)
  - Python: Pytest coverage plugin

**View Coverage:**
```bash
# Backend
./vendor/bin/pest run --coverage

# Frontend (when configured)
npm test -- --coverage

# Python
pytest tests/ --cov=app
```

**Gaps (Current):**
- Frontend has no tests yet (Vitest not configured for test files)
- Python tests minimal (health endpoint only)
- Backend tests cover auth, services, but not all endpoints

## Test Types

**Unit Tests:**
- **Scope:** Individual functions, service methods, utilities
- **Approach:** Mocked dependencies, fast execution
- **Location:** `backend/tests/Unit/Services/`, isolated logic
- **Example:** `EventServiceTest.php` — test service methods with mocked Eloquent queries

**Integration Tests:**
- **Scope:** API endpoints, database operations, multiple layers
- **Approach:** Real database (test DB), real HTTP requests via `postJson()`
- **Location:** `backend/tests/Feature/Auth/`, `backend/tests/Feature/Api/`
- **Example:** `AuthenticationTest.php` — test full auth flow (registration, login, password change)
- **Database:** Uses `RefreshDatabase` trait to reset between tests

**E2E Tests:**
- **Framework:** Playwright (configured in `e2e/` directory but not heavily documented)
- **Status:** Not actively used in current test suite
- **Expected:** Critical user flows (login → patient profile → decision making)

**API Tests:**
- **Framework:** Pest (Feature tests with HTTP assertions)
- **Pattern:** `$this->postJson('/api/auth/login', [...])->assertStatus(200)`
- **Assertions:** Status codes, JSON structure, JSON paths, database state

## Common Patterns

**Async Testing (Backend - synchronous by nature):**
- Not needed — PHP/Laravel tests run synchronously
- HTTP calls mocked via `Http::fake()` (non-blocking)

**Async Testing (Frontend — expected pattern):**
```typescript
import { render, screen, waitFor } from "@testing-library/react";

it("loads data on mount", async () => {
    render(<MyComponent />);

    // Wait for API call to complete
    await waitFor(() => {
        expect(screen.getByText("Loaded")).toBeInTheDocument();
    });
});
```

**Error Testing (Backend):**
```php
it('login with wrong password returns 401', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'admin@acumenus.net',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

it('change password rejects wrong current password', function () {
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/auth/change-password', [
            'current_password' => 'WrongOldPass!',
            'password' => 'NewSecurePass456!',
            'password_confirmation' => 'NewSecurePass456!',
        ]);

    $response->assertStatus(422);
});
```

**Authentication Testing (Backend):**
```php
// Use real token via actingAs()
$user = User::factory()->create(['is_active' => true]);

$response = $this->actingAs($user, 'sanctum')
    ->getJson('/api/auth/user');

$response->assertStatus(200);
```

**Database Assertions (Backend):**
```php
// Assert data was persisted
$this->assertDatabaseHas('app.users', [
    'email' => 'newuser@acumenus.net',
    'must_change_password' => true,
]);

// Assert relationships loaded
$response->assertJsonStructure(['access_token', 'user']);
```

**State Testing (Frontend — expected pattern):**
```typescript
it("updates user in store when authenticated", () => {
    const user: User = { id: 1, name: "Test", email: "test@test.com", ...otherFields };
    const { result } = renderHook(() => useAuthStore());

    act(() => {
        result.current.setAuth("token123", user);
    });

    expect(result.current.user).toEqual(user);
    expect(result.current.isAuthenticated).toBe(true);
});
```

## Test Environment Configuration

**Backend:**
- Environment: `APP_ENV=testing` (set in `phpunit.xml`)
- Database: PostgreSQL (real test DB, refreshed per test)
- Cache: Array driver (in-memory, fast)
- Queue: Sync driver (inline execution)
- Mail: Array driver (no actual emails sent)
- Bcrypt rounds: 4 (fast for testing)

**Frontend:**
- Environment: Node.js with jsdom
- API calls: Mocked via Vitest or Testing Library
- LocalStorage: Simulated by jsdom

**Python/AI:**
- Environment: Development (test database connection)
- FastAPI TestClient: In-process, no actual HTTP

## Continuous Integration

**Backend:**
- Tests run via GitHub Actions on every commit
- Pest framework configured in phpunit.xml
- Coverage required for main branch

**Frontend:**
- Build step includes `tsc --noEmit` (type checking)
- Test step runs via npm

**Python/AI:**
- Tests in `tests/` directory
- Pytest discovery automatic

---

*Testing analysis: 2026-03-24*
