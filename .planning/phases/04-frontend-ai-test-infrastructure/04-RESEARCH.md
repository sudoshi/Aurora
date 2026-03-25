# Phase 4: Frontend & AI Test Infrastructure - Research

**Researched:** 2026-03-25
**Domain:** Frontend (Vitest/MSW/RTL) + Python (pytest) + E2E (Playwright) test infrastructure
**Confidence:** HIGH

## Summary

Phase 4 configures three independent test runners (Vitest for frontend, pytest for AI, Playwright for E2E) with coverage reporting, API mocking, and shared test utilities. The project already has the core packages installed (Vitest 3.x, @testing-library/react 16.x, jsdom 25.x, pytest 8.3, Playwright 1.49) but lacks configuration, setup files, and supporting packages (@vitest/coverage-v8, MSW 2.x, @testing-library/user-event, pytest-cov, pytest-asyncio).

The existing codebase has zero frontend test files, one minimal Python test (test_health.py), and eight Playwright spec files with working helpers. The Vitest config block is missing from vite.config.ts, there is no pytest.ini, and the Playwright config is already functional but may need minor URL adjustments.

**Primary recommendation:** Add the `test` block to vite.config.ts, install missing packages (coverage-v8, MSW, user-event, pytest-cov, pytest-asyncio), create setup/utility files, write one smoke test per runner, and verify each `*test run` command produces coverage output.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| INFRA-03 | Configure Vitest with coverage in vite.config.ts (test block, jsdom/happy-dom) | Vite test block config, @vitest/coverage-v8 package, V8 provider setup |
| INFRA-04 | Set up MSW 2.x handlers mirroring real API responses | MSW node server pattern, handler composition, setup.ts lifecycle hooks |
| INFRA-05 | Create React test utilities (provider wrappers for QueryClient, Router, Zustand) | QueryClient wrapper with retry:false, MemoryRouter wrapper, Zustand store reset utility |
| INFRA-06 | Configure pytest with coverage and asyncio_mode = auto | pytest.ini with asyncio_mode=auto, pytest-cov addopts, coverage thresholds |
| INFRA-07 | Create FastAPI test client fixtures with mocked Ollama | conftest.py with TestClient fixture, mock_ollama fixture patching ollama_base_url |
| INFRA-08 | Update Playwright configuration for current app state | Config already functional; verify baseURL, ensure chromium installed, add skeleton test |
</phase_requirements>

## Standard Stack

### Core (Already Installed)
| Library | Version | Purpose | Status |
|---------|---------|---------|--------|
| vitest | ^3.0.0 | Frontend test runner | Installed, needs config |
| @testing-library/react | ^16.0.0 | Component test utilities | Installed |
| @testing-library/jest-dom | ^6.0.0 | DOM assertions | Installed |
| jsdom | ^25.0.0 | DOM environment | Installed |
| pytest | 8.3.0 | Python test runner | Installed |
| @playwright/test | ^1.49.0 | E2E test runner | Installed |

### Must Add
| Library | Version | Purpose | Install Location |
|---------|---------|---------|-----------------|
| @vitest/coverage-v8 | ^3.0.0 | V8 coverage provider for Vitest | frontend devDeps |
| @testing-library/user-event | ^14.6.0 | Realistic user interaction simulation | frontend devDeps |
| msw | ^2.7.0 | Network-level API mocking | frontend devDeps |
| pytest-cov | >=5.0.0 | Coverage plugin for pytest | ai/requirements.txt |
| pytest-asyncio | >=0.24.0 | Async test support for FastAPI | ai/requirements.txt |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| jsdom | happy-dom | happy-dom is faster but less complete; jsdom is already installed and more battle-tested |
| @vitest/coverage-v8 | @vitest/coverage-istanbul | Istanbul adds ~300% overhead vs V8's ~10%; V8 accuracy is sufficient since Vitest 3.2 AST remapping |
| MSW 2.x | vi.mock() on axios | MSW intercepts at network level, catches real integration issues; vi.mock only mocks imports |

**Installation:**
```bash
# Frontend
cd frontend && npm install -D @vitest/coverage-v8 @testing-library/user-event msw

# AI (add to requirements.txt)
cd ai && pip install pytest-cov pytest-asyncio
```

## Architecture Patterns

### File Structure to Create
```
frontend/
  src/
    test/
      setup.ts              # jest-dom import, MSW server lifecycle
      mocks/
        handlers.ts          # MSW request handlers for /api/* endpoints
        server.ts            # setupServer(...handlers)
      utils.tsx              # createWrapper(), renderWithProviders(), resetStores()
    stores/__tests__/        # (smoke test goes here)
      authStore.test.ts
  vite.config.ts             # Add test block

ai/
  pytest.ini                 # asyncio_mode=auto, coverage config
  tests/
    conftest.py              # TestClient fixture, mock_ollama fixture
    test_health.py           # Already exists

e2e/
  playwright.config.ts       # Already configured, verify baseURL
  tests/
    helpers.ts               # Already exists with loginAsAdmin
    smoke.spec.ts            # Skeleton smoke test
```

### Pattern 1: Vitest Test Block in vite.config.ts
**What:** Add `test` property to the existing Vite config. No separate vitest.config.ts needed.
**Why:** Vitest reuses Vite's plugin pipeline (SWC, Tailwind, path aliases) when configured inline.

```typescript
// frontend/vite.config.ts — add test block to existing defineConfig
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  // ...existing server/build config...
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.{test,spec}.{ts,tsx}'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'clover', 'json-summary'],
      include: ['src/**/*.{ts,tsx}'],
      exclude: [
        'src/test/**',
        'src/**/*.d.ts',
        'src/main.tsx',
        'src/vite-env.d.ts',
      ],
    },
  },
});
```

**Confidence:** HIGH -- verified from prior stack research and Vitest official patterns.

### Pattern 2: MSW 2.x Server Setup
**What:** Create MSW node server for intercepting Axios requests in test environment.
**Why:** The frontend uses `apiClient` (Axios with baseURL `/api`). MSW intercepts at network level, so the real Axios interceptors (token injection, 401 handling) still run.

```typescript
// src/test/mocks/handlers.ts
import { http, HttpResponse } from 'msw';

export const handlers = [
  // Auth endpoints
  http.post('/api/auth/login', async ({ request }) => {
    const body = await request.json() as Record<string, string>;
    if (body.email === 'admin@acumenus.net' && body.password === 'superuser') {
      return HttpResponse.json({
        access_token: 'fake-token-123',
        user: { id: 1, name: 'Admin', email: 'admin@acumenus.net', roles: ['super-admin'] },
      });
    }
    return HttpResponse.json({ message: 'Invalid credentials' }, { status: 401 });
  }),

  // Patient list
  http.get('/api/patients', () => {
    return HttpResponse.json({
      success: true,
      data: { data: [], total: 0, current_page: 1 },
    });
  }),

  // Dashboard
  http.get('/api/dashboard', () => {
    return HttpResponse.json({ success: true, data: { patient_count: 0 } });
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

**Confidence:** HIGH -- MSW 2.x `http` handler API is the current standard.

### Pattern 3: React Test Utilities with Provider Wrappers
**What:** Reusable wrappers for QueryClient, Router, and Zustand store reset.
**Why:** TanStack Query needs a provider with retry:false for tests. Router context needed for components using `useNavigate`/`useLocation`. Zustand persisted stores leak state between tests.

```typescript
// src/test/utils.tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, renderHook, type RenderOptions } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import type { ReactNode } from 'react';
import { useAuthStore } from '@/stores/authStore';

function createTestQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: { retry: false, gcTime: 0 },
      mutations: { retry: false },
    },
  });
}

interface WrapperOptions {
  initialRoute?: string;
}

export function createWrapper(options: WrapperOptions = {}) {
  const queryClient = createTestQueryClient();
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={[options.initialRoute ?? '/']}>
        {children}
      </MemoryRouter>
    </QueryClientProvider>
  );
}

export function renderWithProviders(
  ui: ReactNode,
  options: WrapperOptions & Omit<RenderOptions, 'wrapper'> = {},
) {
  const { initialRoute, ...renderOptions } = options;
  const Wrapper = createWrapper({ initialRoute });
  return render(ui, { wrapper: Wrapper, ...renderOptions });
}

export function renderHookWithProviders<T>(
  hook: () => T,
  options: WrapperOptions = {},
) {
  return renderHook(hook, { wrapper: createWrapper(options) });
}

/** Reset all Zustand stores to initial state between tests */
export function resetStores() {
  useAuthStore.setState({
    token: null,
    user: null,
    isAuthenticated: false,
  });
  // Add other stores as needed: useProfileStore, useUiStore, useAbbyStore
}
```

**Confidence:** HIGH -- standard pattern for TanStack Query + React Router test wrappers.

### Pattern 4: pytest Configuration with AsyncIO Auto Mode
**What:** Create pytest.ini with asyncio_mode=auto so async test functions run without `@pytest.mark.asyncio`.
**Why:** FastAPI endpoints are async. Auto mode removes boilerplate on every test.

```ini
# ai/pytest.ini
[pytest]
testpaths = tests
asyncio_mode = auto
addopts = --cov=app --cov-report=term-missing --cov-fail-under=0
```

Note: `--cov-fail-under=0` for infrastructure phase. Phase 8 (AI tests) will raise to 80.

**Confidence:** HIGH -- standard pytest-asyncio configuration.

### Pattern 5: FastAPI conftest.py with Mocked Ollama
**What:** Shared fixtures for TestClient and mocked external services.
**Why:** The AI service depends on Ollama (local LLM) and Claude API. Tests must not call real LLMs.

```python
# ai/tests/conftest.py
import pytest
from unittest.mock import AsyncMock, patch, MagicMock
from fastapi.testclient import TestClient

@pytest.fixture
def client():
    from app.main import app
    return TestClient(app)

@pytest.fixture
def mock_ollama():
    """Mock Ollama HTTP calls so tests don't need a running Ollama instance."""
    mock_response = MagicMock()
    mock_response.status_code = 200
    mock_response.json.return_value = {
        "model": "medgemma-q4:latest",
        "response": "Mock AI response for testing.",
    }
    with patch('httpx.AsyncClient.post', new_callable=AsyncMock, return_value=mock_response) as mock:
        yield mock

@pytest.fixture
def mock_anthropic():
    """Mock Anthropic Claude API calls."""
    with patch('app.config.settings.claude_api_key', 'test-key'):
        mock = MagicMock()
        mock.messages.create = AsyncMock(return_value=MagicMock(
            content=[MagicMock(text='Mock Claude response')]
        ))
        with patch('anthropic.AsyncAnthropic', return_value=mock):
            yield mock
```

**Confidence:** MEDIUM -- mock targets depend on actual import paths in services; may need adjustment during implementation.

### Anti-Patterns to Avoid
- **Over-configuring coverage thresholds now:** Phase 4 is infrastructure. Set thresholds to 0 (or omit). Phases 7-8 raise them to 80%.
- **Writing extensive MSW handlers:** Only include handlers for smoke tests. Phases 7+ will add per-feature handlers.
- **Mocking Zustand stores directly:** Let real stores run in tests. Reset state between tests with `store.setState()` instead of `vi.mock()`.
- **Using `vi.mock('axios')` instead of MSW:** MSW tests the real Axios interceptors (token injection, 401 redirect). `vi.mock` bypasses them.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| API mocking | Manual Axios mock with `vi.mock` | MSW 2.x `http` handlers | Network-level interception tests real request/response cycle |
| Coverage collection | Manual file instrumentation | @vitest/coverage-v8 | V8 engine collects coverage natively, zero config |
| DOM environment | Custom JSDOM setup | Vitest `environment: 'jsdom'` | Single config line, managed by Vitest |
| Test query client | Shared QueryClient instance | Fresh `new QueryClient()` per test via wrapper | Shared clients leak cache between tests |
| Async Python tests | Manual event loop management | `asyncio_mode = auto` in pytest.ini | pytest-asyncio handles loop creation/teardown |

## Common Pitfalls

### Pitfall 1: Zustand Persist Middleware Leaking Between Tests
**What goes wrong:** Tests use `useAuthStore` which is wrapped in `persist()`. LocalStorage state persists across tests, causing order-dependent failures.
**Why it happens:** jsdom's localStorage is shared across test files by default.
**How to avoid:** Call `resetStores()` in `afterEach`. Also add `localStorage.clear()` in setup.ts afterEach hook.
**Warning signs:** Tests pass individually but fail when run together.

### Pitfall 2: MSW `onUnhandledRequest: 'error'` Breaking Unrelated Tests
**What goes wrong:** Setting `onUnhandledRequest: 'error'` causes tests to fail when components make API calls not covered by handlers.
**Why it happens:** Components that mount with `useQuery` immediately fire requests.
**How to avoid:** Start with `onUnhandledRequest: 'warn'` during infrastructure phase. Switch to `'error'` once all handlers are in place (Phase 7).
**Warning signs:** Unrelated component tests fail with "unhandled request" errors.

### Pitfall 3: Vitest globals Type Errors
**What goes wrong:** TypeScript complains about `describe`, `it`, `expect` not being defined when `globals: true` is set.
**Why it happens:** tsconfig.json does not include Vitest's global types.
**How to avoid:** Add `"types": ["vitest/globals"]` to tsconfig.json compilerOptions, or create a `src/test/vitest.d.ts` with `/// <reference types="vitest/globals" />`.
**Warning signs:** Red squiggles on `describe`/`it` in test files.

### Pitfall 4: pytest-cov Import Errors When AI Dependencies Missing
**What goes wrong:** `pytest --cov` fails because the AI service imports heavy dependencies (anthropic, sqlalchemy, pgvector) that fail to initialize.
**Why it happens:** Coverage tries to import all app modules to measure them.
**How to avoid:** Ensure all AI dependencies are installed in the test environment. Use `--cov-fail-under=0` during infrastructure setup.
**Warning signs:** ImportError or ModuleNotFoundError during collection.

### Pitfall 5: Playwright baseURL Mismatch
**What goes wrong:** Playwright tests fail because `baseURL` points to `https://aurora.acumenus.net` which may not be running or accessible.
**Why it happens:** Current config uses production URL as default.
**How to avoid:** For local dev testing, ensure `BASE_URL` env var is set to `http://localhost:5173` or the correct local URL.
**Warning signs:** Navigation timeout on first page.goto().

## Code Examples

### Smoke Test: Vitest (INFRA-03 verification)
```typescript
// frontend/src/test/smoke.test.ts
import { describe, it, expect } from 'vitest';

describe('Vitest smoke test', () => {
  it('runs a basic assertion', () => {
    expect(1 + 1).toBe(2);
  });

  it('has access to jsdom environment', () => {
    const div = document.createElement('div');
    div.textContent = 'Aurora';
    expect(div.textContent).toBe('Aurora');
  });
});
```

### Smoke Test: MSW (INFRA-04 verification)
```typescript
// frontend/src/test/msw-smoke.test.ts
import { describe, it, expect } from 'vitest';
import { server } from './mocks/server';
import { http, HttpResponse } from 'msw';

describe('MSW smoke test', () => {
  it('intercepts a GET request', async () => {
    server.use(
      http.get('/api/test', () => {
        return HttpResponse.json({ message: 'mocked' });
      }),
    );

    const response = await fetch('/api/test');
    const data = await response.json();
    expect(data.message).toBe('mocked');
  });
});
```

### Smoke Test: Provider Wrapper (INFRA-05 verification)
```typescript
// frontend/src/stores/__tests__/authStore.test.ts
import { describe, it, expect, afterEach } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useAuthStore } from '@/stores/authStore';
import { resetStores } from '@/test/utils';

afterEach(() => resetStores());

describe('authStore', () => {
  it('sets auth state', () => {
    const { result } = renderHook(() => useAuthStore());
    act(() => {
      result.current.setAuth('token-123', {
        id: 1, name: 'Test', email: 'test@test.com',
        // ...minimal required fields
      } as any);
    });
    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.token).toBe('token-123');
  });
});
```

### Smoke Test: pytest (INFRA-06 + INFRA-07 verification)
```python
# ai/tests/test_smoke.py
def test_basic_assertion():
    """Verify pytest runs with coverage."""
    assert 1 + 1 == 2

def test_health_with_client(client):
    """Verify conftest.py client fixture works."""
    response = client.get("/api/ai/health")
    assert response.status_code == 200
    assert response.json()["status"] == "ok"
```

### Smoke Test: Playwright (INFRA-08 verification)
```typescript
// e2e/tests/smoke.spec.ts
import { test, expect } from '@playwright/test';

test('app loads login page', async ({ page }) => {
  await page.goto('/login');
  await expect(page.getByLabel(/email/i)).toBeVisible();
});
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Jest for React tests | Vitest 3.x (native Vite) | 2024 | Same API, 10x faster with Vite plugin reuse |
| MSW 1.x `rest.get()` | MSW 2.x `http.get()` | 2023 | Breaking API change; must use `http`/`HttpResponse` imports |
| `@vitest/coverage-istanbul` | `@vitest/coverage-v8` | 2025 | V8 AST remapping since Vitest 3.2 makes V8 accuracy match Istanbul |
| `@pytest.mark.asyncio` per test | `asyncio_mode = auto` | 2024 | Eliminates boilerplate marker on every async test |
| Playwright 1.49 | Playwright 1.49+ (current) | Stable | No urgent upgrade needed; 1.49 is functional |

## Open Questions

1. **MSW handler coverage for existing API endpoints**
   - What we know: The frontend has an Axios client at `src/lib/api-client.ts` hitting `/api/*` endpoints
   - What's unclear: Exactly which endpoints the existing frontend components call (need to scan features/)
   - Recommendation: Start with minimal handlers (auth, patients, dashboard). Phase 7 adds handlers per-component as tests are written.

2. **AI service import chain during pytest collection**
   - What we know: The AI service has heavy imports (anthropic, sqlalchemy, pgvector, numpy)
   - What's unclear: Whether `pytest --cov=app` will fail if database/Redis is not available during collection
   - Recommendation: Test with `--cov` locally first. If imports fail, add conditional initialization or environment checks in conftest.py.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework (Frontend) | Vitest 3.x + @testing-library/react 16.x |
| Framework (AI) | pytest 8.3 + pytest-cov + pytest-asyncio |
| Framework (E2E) | Playwright 1.49 |
| Config file (Frontend) | `frontend/vite.config.ts` (test block) -- Wave 0 |
| Config file (AI) | `ai/pytest.ini` -- Wave 0 |
| Config file (E2E) | `e2e/playwright.config.ts` -- exists |
| Quick run (Frontend) | `cd frontend && npx vitest run` |
| Quick run (AI) | `cd ai && pytest --cov` |
| Quick run (E2E) | `cd e2e && npx playwright test` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| INFRA-03 | Vitest runs with V8 coverage | smoke | `cd frontend && npx vitest run --coverage` | No -- Wave 0 |
| INFRA-04 | MSW intercepts API calls | smoke | `cd frontend && npx vitest run src/test/msw-smoke.test.ts` | No -- Wave 0 |
| INFRA-05 | Provider wrappers work | smoke | `cd frontend && npx vitest run src/stores/__tests__/authStore.test.ts` | No -- Wave 0 |
| INFRA-06 | pytest runs with coverage + asyncio auto | smoke | `cd ai && pytest --cov -x` | No -- Wave 0 |
| INFRA-07 | TestClient + mock fixtures work | smoke | `cd ai && pytest tests/test_smoke.py -x` | No -- Wave 0 |
| INFRA-08 | Playwright config correct, browser launches | smoke | `cd e2e && npx playwright test tests/smoke.spec.ts` | No -- Wave 0 |

### Sampling Rate
- **Per task commit:** Run the specific runner's smoke test
- **Per wave merge:** Run all three: `vitest run && pytest --cov && playwright test tests/smoke.spec.ts`
- **Phase gate:** All six INFRA requirements pass their automated commands

### Wave 0 Gaps
- [ ] `frontend/src/test/setup.ts` -- Vitest setup file (jest-dom, MSW lifecycle)
- [ ] `frontend/src/test/mocks/handlers.ts` -- MSW request handlers
- [ ] `frontend/src/test/mocks/server.ts` -- MSW server instance
- [ ] `frontend/src/test/utils.tsx` -- Provider wrappers and store reset
- [ ] `frontend/src/test/smoke.test.ts` -- Vitest smoke test
- [ ] `frontend/src/test/msw-smoke.test.ts` -- MSW smoke test
- [ ] `frontend/src/stores/__tests__/authStore.test.ts` -- Store smoke test
- [ ] `ai/pytest.ini` -- pytest configuration
- [ ] `ai/tests/conftest.py` -- Shared fixtures
- [ ] `ai/tests/test_smoke.py` -- pytest smoke test with fixture
- [ ] `e2e/tests/smoke.spec.ts` -- Playwright skeleton smoke test
- [ ] Add `@vitest/coverage-v8`, `@testing-library/user-event`, `msw` to frontend/package.json
- [ ] Add `pytest-cov`, `pytest-asyncio` to ai/requirements.txt
- [ ] Add `/// <reference types="vitest/globals" />` type reference for TypeScript

## Sources

### Primary (HIGH confidence)
- `frontend/vite.config.ts` -- current config, no test block present
- `frontend/package.json` -- vitest 3.x, RTL 16.x, jest-dom 6.x already installed; missing coverage-v8, user-event, MSW
- `ai/requirements.txt` -- pytest 8.3 installed; missing pytest-cov, pytest-asyncio
- `ai/tests/test_health.py` -- existing health test pattern (TestClient, no fixtures)
- `e2e/playwright.config.ts` -- functional config with chromium, baseURL from env
- `.planning/research/STACK.md` -- prior stack research with verified versions
- `.planning/research/ARCHITECTURE.md` -- prior architecture research with test patterns

### Secondary (MEDIUM confidence)
- Vitest coverage docs (V8 provider configuration)
- MSW 2.x docs (http/HttpResponse API)
- pytest-asyncio docs (asyncio_mode=auto)

### Tertiary (LOW confidence)
- Exact mock targets for Ollama/Claude in AI service (depends on import structure in individual service files)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - packages verified in package.json/requirements.txt, versions confirmed
- Architecture: HIGH - patterns from prior research validated against actual codebase structure
- Pitfalls: HIGH - Zustand persist, MSW unhandled request, and tsconfig globals are well-documented issues
- AI mock targets: MEDIUM - need to verify exact import paths during implementation

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable tooling, no fast-moving targets)
