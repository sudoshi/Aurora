# Architecture: Test Infrastructure for Monorepo

**Domain:** Multi-service monorepo test infrastructure
**Researched:** 2026-03-25

## Recommended Architecture

Three independent test suites (backend, frontend, AI) with a shared E2E suite that tests the integrated system. Each suite has its own runner, config, and coverage output. CI aggregates coverage.

```
Aurora/
  backend/
    tests/
      Unit/Services/          -- Service logic tests (mocked deps)
      Feature/Auth/           -- Auth endpoint integration tests
      Feature/Api/            -- API endpoint integration tests
    phpunit.xml               -- Pest/PHPUnit config
    coverage/                 -- Generated coverage reports
  frontend/
    src/
      test/setup.ts           -- Vitest setup (jest-dom, MSW server)
      test/mocks/handlers.ts  -- MSW request handlers
      test/mocks/server.ts    -- MSW server instance
      stores/__tests__/       -- Zustand store tests
      hooks/__tests__/        -- TanStack Query hook tests
      components/ui/__tests__/-- UI component tests
      features/**/__tests__/  -- Feature-specific tests
    vite.config.ts            -- Includes test config block
    coverage/                 -- Generated coverage reports
  ai/
    tests/
      test_health.py          -- Health endpoint tests
      test_genomic_briefing.py-- AI briefing endpoint tests
      test_therapy.py         -- Therapy matching tests
      conftest.py             -- Shared fixtures, TestClient
    pytest.ini                -- pytest config with coverage
    coverage/                 -- Generated coverage reports
  e2e/
    tests/
      auth.spec.ts            -- Login, password change, logout
      patient-profile.spec.ts -- Patient navigation, timeline
      genomics.spec.ts        -- Genomics tab flows
    fixtures/                 -- Reusable test data and helpers
    playwright.config.ts      -- Playwright config
```

### Component Boundaries

| Component | Responsibility | Test Strategy |
|-----------|---------------|---------------|
| Backend Unit Tests | Service method correctness | Mock Eloquent, mock external APIs (Resend, OncoKB), test pure logic |
| Backend Feature Tests | Full HTTP request/response cycle | Real test database (RefreshDatabase), real middleware, seed data |
| Frontend Store Tests | Zustand state transitions | renderHook(), test actions and selectors, no API calls |
| Frontend Hook Tests | TanStack Query data fetching | MSW to mock API, QueryClientProvider wrapper, waitFor() assertions |
| Frontend Component Tests | UI rendering and interaction | RTL render, userEvent for clicks/typing, MSW for data-driven components |
| AI Unit Tests | Service function correctness | Mock external APIs (Claude, Ollama), test data transformation |
| AI Endpoint Tests | FastAPI route handling | TestClient, mock services, test request validation and response shape |
| E2E Tests | Full user workflows | Real browser, real backend, test critical paths only |

### Data Flow

**Backend tests:**
```
Test -> postJson('/api/endpoint') -> Middleware -> Controller -> Service -> Database
                                                                            |
                                                            RefreshDatabase resets between tests
```

**Frontend tests:**
```
Test -> render(<Component />) -> Component mounts -> useQuery fires -> MSW intercepts -> Returns mock data
                                                                                          |
                                                                    server.resetHandlers() between tests
```

**E2E tests:**
```
Playwright -> Browser -> Frontend (real) -> API (real) -> Backend (real) -> Database (real)
                                                                            |
                                                            Test database, seeded before suite
```

## Patterns to Follow

### Pattern 1: MSW Handler Composition

**What:** Define API mock handlers in a central location, compose them per test.
**When:** Any frontend test that involves API calls.
**Why:** Prevents duplicating mock definitions across 50+ test files.

```typescript
// src/test/mocks/handlers.ts
import { http, HttpResponse } from 'msw';

export const handlers = [
  http.get('/api/patients', () => {
    return HttpResponse.json({
      success: true,
      data: [{ id: 1, name: 'Test Patient' }],
    });
  }),
  http.post('/api/auth/login', async ({ request }) => {
    const body = await request.json();
    if (body.password === 'wrong') {
      return HttpResponse.json({ message: 'Invalid credentials' }, { status: 401 });
    }
    return HttpResponse.json({ access_token: 'fake-token', user: { id: 1 } });
  }),
];

// src/test/mocks/server.ts
import { setupServer } from 'msw/node';
import { handlers } from './handlers';
export const server = setupServer(...handlers);

// src/test/setup.ts
import '@testing-library/jest-dom/vitest';
import { server } from './mocks/server';
import { beforeAll, afterAll, afterEach } from 'vitest';

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
```

### Pattern 2: Laravel Test Trait Composition in Pest

**What:** Use Pest's `uses()` to apply traits per directory, not per file.
**When:** All backend tests.
**Why:** DRY setup, consistent database refresh.

```php
// tests/Pest.php
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

uses(Tests\TestCase::class)
    ->in('Unit');
```

### Pattern 3: QueryClient Wrapper for Hook Tests

**What:** Wrap hook tests in a QueryClientProvider with test-safe defaults.
**When:** Testing any TanStack Query hook.
**Why:** Prevents retry loops, caching across tests, and timeout flakiness.

```typescript
// src/test/utils.tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, renderHook } from '@testing-library/react';
import { ReactNode } from 'react';

function createTestQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: { retry: false, gcTime: 0 },
      mutations: { retry: false },
    },
  });
}

export function createWrapper() {
  const queryClient = createTestQueryClient();
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
}

export function renderWithProviders(ui: ReactNode) {
  const queryClient = createTestQueryClient();
  return render(
    <QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>
  );
}
```

### Pattern 4: FastAPI conftest.py with TestClient

**What:** Shared TestClient fixture in conftest.py.
**When:** All AI service tests.
**Why:** Single app instance, consistent test client, mock injection point.

```python
# ai/tests/conftest.py
import pytest
from fastapi.testclient import TestClient
from unittest.mock import patch, MagicMock

@pytest.fixture
def client():
    from app.main import app
    return TestClient(app)

@pytest.fixture
def mock_anthropic():
    with patch('app.services.anthropic_client') as mock:
        mock.messages.create.return_value = MagicMock(
            content=[MagicMock(text='Mock AI response')]
        )
        yield mock
```

### Pattern 5: Playwright Page Object Model (Lite)

**What:** Encapsulate page interactions in helper functions, not full POM classes.
**When:** E2E tests with repeated interactions (login, navigation).
**Why:** Reduces duplication without over-engineering.

```typescript
// e2e/fixtures/auth.ts
import { Page } from '@playwright/test';

export async function login(page: Page, email: string, password: string) {
  await page.goto('/login');
  await page.getByLabel('Email').fill(email);
  await page.getByLabel('Password').fill(password);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.waitForURL('**/dashboard');
}
```

## Anti-Patterns to Avoid

### Anti-Pattern 1: Testing Implementation Details
**What:** Asserting on internal state, class method calls, or DOM structure instead of user-visible behavior.
**Why bad:** Tests break on refactors that don't change behavior. False failures erode trust in the test suite.
**Instead:** Assert on what the user sees (text, roles, aria labels) and what the API receives/returns.

### Anti-Pattern 2: Mocking Eloquent in Feature Tests
**What:** Using Mockery to mock Eloquent builders in Feature (integration) tests.
**Why bad:** Feature tests should test the real database interaction. Mocking Eloquent defeats the purpose.
**Instead:** Use `RefreshDatabase` trait and Laravel factories. Mock only external HTTP calls (Resend, OncoKB).

### Anti-Pattern 3: Shared Mutable Test State
**What:** Tests that depend on data created by a previous test.
**Why bad:** Tests become order-dependent, fail in parallel, produce non-deterministic results.
**Instead:** Each test creates its own data via factories/fixtures. `RefreshDatabase` for backend, `server.resetHandlers()` for frontend.

### Anti-Pattern 4: Over-Mocking in Frontend Tests
**What:** Mocking every import (axios, stores, hooks) to test a component in total isolation.
**Why bad:** Tests pass but component fails in production because the mocks don't match real behavior.
**Instead:** Use MSW for API layer, let real stores and hooks run, mock only browser APIs that jsdom doesn't support.

### Anti-Pattern 5: E2E Tests for Every Edge Case
**What:** Writing E2E tests for validation errors, empty states, error boundaries.
**Why bad:** E2E tests are slow (seconds per test). Edge cases should be unit/integration tested.
**Instead:** E2E tests cover happy paths and critical flows only. Unit tests cover edge cases.

## Scalability Considerations

| Concern | At 50 tests | At 200 tests | At 500+ tests |
|---------|-------------|--------------|---------------|
| Backend speed | No issue, <30s | Use ParaTest --parallel | Shard across CI matrix |
| Frontend speed | No issue, <15s | Vitest is fast, still fine | Vitest workspace sharding |
| AI speed | No issue, <10s | Still fine (small surface) | Split by endpoint module |
| E2E speed | 1-2 min | 3-5 min, parallelize workers | Shard across CI matrix, increase workers |
| Coverage reporting | Local text output | Add HTML reports | Codecov for trend tracking |

## Sources

- [Pest PHP Configuring Tests](https://pestphp.com/docs/configuring-tests) -- uses() trait composition
- [Vitest Coverage Guide](https://vitest.dev/guide/coverage) -- V8 provider config
- [MSW Integration Guide](https://mswjs.io/docs/integrations/node/) -- Node.js server setup
- [Testing Library Guiding Principles](https://testing-library.com/docs/guiding-principles) -- Test behavior, not implementation
- [FastAPI Testing Docs](https://fastapi.tiangolo.com/tutorial/testing/) -- TestClient patterns
- [Playwright Best Practices](https://playwright.dev/docs/best-practices) -- Page object lite, test isolation

---

*Architecture research: 2026-03-25*
