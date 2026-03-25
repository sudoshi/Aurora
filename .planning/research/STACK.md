# Technology Stack: Testing & Coverage

**Project:** Aurora Stabilization & Verification
**Researched:** 2026-03-25
**Focus:** Testing stack for Laravel 11 + React 19 + FastAPI monorepo

## Recommended Stack

### Backend Testing (Laravel/PHP)

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Pest | 3.8+ | Test framework | Already installed. Fluent syntax, first-class Laravel support, describe/it blocks. The standard for Laravel 11+. |
| PHPUnit | 11.x | Underlying engine | Required by Pest 3.x. No direct interaction needed. |
| PCOV | latest | Coverage driver | 5x faster than Xdebug for coverage. Line-level coverage is sufficient for 80% target. Install via `pecl install pcov` in Docker. |
| Mockery | 1.6+ | Mocking | Already installed. Standard Laravel mocking library, integrates with Pest via `uses()`. |
| ParaTest | 7.x | Parallel execution | Run tests across multiple processes. Install via `composer require --dev brianium/paratest`. Use `--parallel` flag with Pest. |

**Confidence:** HIGH -- Pest 3 + PCOV is the standard Laravel testing stack in 2025-2026, verified via official Pest docs and Laravel docs.

**Coverage command:**
```bash
# Requires PCOV extension in PHP container
./vendor/bin/pest --coverage --min=80
# With parallel execution
./vendor/bin/pest --parallel --coverage --min=80
```

**phpunit.xml additions needed:**
```xml
<coverage>
    <report>
        <clover outputFile="coverage/clover.xml"/>
        <html outputDirectory="coverage/html"/>
        <text outputFile="coverage/coverage.txt"/>
    </report>
</coverage>
```

### Frontend Testing (React/TypeScript)

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Vitest | 3.x | Test runner | Already installed. Native Vite integration, same config/plugins, fast HMR-based watch mode. |
| @vitest/coverage-v8 | 3.x | Coverage provider | V8 is faster than Istanbul with near-identical accuracy since Vitest 3.2 AST remapping. Zero-config with Vitest. |
| @testing-library/react | 16.x | Component testing | Already installed. Tests components the way users interact with them. Standard for React 19. |
| @testing-library/jest-dom | 6.x | DOM assertions | Already installed. `toBeInTheDocument()`, `toHaveTextContent()`, etc. |
| @testing-library/user-event | 14.x | User interaction simulation | Simulates real user events (click, type, keyboard). More realistic than `fireEvent`. Must add. |
| MSW | 2.x | API mocking | Intercepts network requests at the service worker level. Reusable handlers across tests. Replaces manual `vi.mock()` for API calls. Must add. |
| jsdom | 25.x | DOM environment | Already installed. Simulates browser DOM for unit tests. |

**Confidence:** HIGH -- Vitest 3 + RTL + MSW is the dominant React testing stack in 2025-2026, verified via Vitest official docs and community consensus.

**Missing packages to install:**
```bash
cd frontend
npm install -D @vitest/coverage-v8 @testing-library/user-event msw
```

**vite.config.ts test configuration to add:**
```typescript
export default defineConfig({
  // ...existing config...
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
      thresholds: {
        statements: 80,
        branches: 80,
        functions: 80,
        lines: 80,
      },
    },
  },
});
```

**Setup file (src/test/setup.ts):**
```typescript
import '@testing-library/jest-dom/vitest';
// MSW server setup imported here
```

### AI Service Testing (Python/FastAPI)

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| pytest | 8.3+ | Test framework | Already installed. Standard Python testing. |
| pytest-cov | 7.1+ | Coverage plugin | Wraps coverage.py with pytest integration. Threshold enforcement in CI. Must add. |
| pytest-asyncio | 0.24+ | Async test support | Required for testing async FastAPI endpoints and services. Must add. |
| httpx | 0.28+ | Async test client | Already installed. FastAPI recommends httpx TestClient over requests for async. |
| coverage | 7.13+ | Coverage engine | Underlying engine for pytest-cov. Branch coverage support. Installed as dependency of pytest-cov. |

**Confidence:** HIGH -- pytest + pytest-cov is the universal Python testing stack, verified via PyPI and FastAPI official docs.

**Missing packages to add to requirements.txt:**
```
pytest-cov==7.1.0
pytest-asyncio==0.24.0
```

**pytest.ini (create in ai/ directory):**
```ini
[pytest]
testpaths = tests
asyncio_mode = auto
addopts = --cov=app --cov-report=term-missing --cov-report=html:coverage/html --cov-report=xml:coverage/coverage.xml --cov-fail-under=80
```

### E2E Testing

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Playwright | 1.58+ | E2E testing | Currently at 1.49. Upgrade to 1.58 for timeline reports and improved trace viewer. Cross-browser, auto-waiting locators. |

**Confidence:** HIGH -- Playwright is the clear E2E standard in 2026, verified via official releases.

**Upgrade:**
```bash
cd e2e
npm install -D @playwright/test@latest
npx playwright install chromium
```

### Unified Coverage Reporting

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Codecov | SaaS | Unified coverage dashboard | Merges PHP/JS/Python coverage via flags. Free for open source. Supports monorepo components. |

**Confidence:** MEDIUM -- Codecov is the standard for multi-language monorepos. Alternative: SonarQube (self-hosted, heavier). Codecov is simpler for this project size.

**How it works:**
Each service generates coverage in a standard format (Clover XML for PHP, LCOV/Clover for JS, XML for Python). CI uploads all three with flags:
```yaml
# In GitHub Actions
- uses: codecov/codecov-action@v4
  with:
    flags: backend
    files: backend/coverage/clover.xml
- uses: codecov/codecov-action@v4
  with:
    flags: frontend
    files: frontend/coverage/clover.xml
- uses: codecov/codecov-action@v4
  with:
    flags: ai
    files: ai/coverage/coverage.xml
```

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| PHP Coverage Driver | PCOV | Xdebug | Xdebug is 5x slower for coverage. Only needed for step debugging, not coverage collection. |
| JS Coverage Provider | @vitest/coverage-v8 | @vitest/coverage-istanbul | Istanbul adds 300% overhead vs V8's 10%. Since Vitest 3.2, V8 accuracy matches Istanbul via AST remapping. |
| JS API Mocking | MSW 2.x | vi.mock() on axios | MSW intercepts at the network level, tests real request/response cycles. vi.mock() only mocks imports, misses integration issues. MSW handlers are reusable across tests and Playwright. |
| E2E Framework | Playwright | Cypress | Playwright has native multi-browser, faster execution, better trace viewer. Cypress is single-tab only and slower for complex flows. |
| Python Coverage | pytest-cov 7.1 | coverage run | pytest-cov integrates coverage into pytest invocation. No separate `coverage run` step needed. |
| PHP Test Runner | Pest 3.x | PHPUnit directly | Pest wraps PHPUnit with cleaner syntax. Laravel 11 ships with Pest support. No reason to use raw PHPUnit. |
| Unified Coverage | Codecov | SonarQube | SonarQube requires self-hosting and is overkill for this project. Codecov is SaaS, free tier sufficient, native monorepo flags. |

## What NOT to Use

| Tool | Why Avoid |
|------|-----------|
| Jest | Vitest replaces Jest entirely for Vite projects. Jest requires separate config, no Vite plugin reuse, slower. |
| Enzyme | Dead project. React Testing Library is the standard for React 19. Enzyme does not support React 18+. |
| Selenium | Playwright supersedes Selenium with better DX, auto-waiting, and modern browser APIs. |
| php-code-coverage with Xdebug | PCOV is purpose-built for coverage and 5x faster. Xdebug should only be used for debugging. |
| Snapshot testing | Brittle, generates noise on every UI change, provides false confidence. Use explicit assertions. |

## Installation Commands

### Backend
```bash
cd backend
composer require --dev brianium/paratest
# PCOV must be installed in the PHP Docker container:
# In docker/php/Dockerfile: RUN pecl install pcov && docker-php-ext-enable pcov
```

### Frontend
```bash
cd frontend
npm install -D @vitest/coverage-v8 @testing-library/user-event msw
```

### AI Service
```bash
cd ai
pip install pytest-cov==7.1.0 pytest-asyncio==0.24.0
# Update requirements.txt accordingly
```

### E2E
```bash
cd e2e
npm install -D @playwright/test@latest
npx playwright install chromium
```

## Coverage Report Formats

| Service | Format | Output Path | CI Upload |
|---------|--------|-------------|-----------|
| Backend (Pest/PCOV) | Clover XML | backend/coverage/clover.xml | Codecov flag: backend |
| Frontend (Vitest/V8) | Clover XML | frontend/coverage/clover.xml | Codecov flag: frontend |
| AI (pytest-cov) | Cobertura XML | ai/coverage/coverage.xml | Codecov flag: ai |
| E2E (Playwright) | HTML report | e2e/playwright-report/ | Not uploaded (visual only) |

## Sources

- [Pest PHP Test Coverage Docs](https://pestphp.com/docs/test-coverage) -- PCOV vs Xdebug guidance
- [Pest v3 Release Notes](https://pestphp.com/docs/pest3-now-available) -- PHPUnit 11 base, type coverage
- [Laravel 11 Testing Docs](https://laravel.com/docs/11.x/testing) -- Official Laravel testing guide
- [Vitest Coverage Guide](https://vitest.dev/guide/coverage) -- V8 vs Istanbul provider comparison
- [Vitest 3 + Vite 6 + React 19 Upgrade](https://www.thecandidstartup.org/2025/03/31/vitest-3-vite-6-react-19.html) -- Version compatibility
- [V8 vs Istanbul Discussion](https://github.com/vitest-dev/vitest/discussions/7587) -- AST remapping since v3.2
- [FastAPI Official Testing Docs](https://fastapi.tiangolo.com/tutorial/testing/) -- TestClient patterns
- [pytest-cov 7.1.0 on PyPI](https://pypi.org/project/pytest-cov/) -- Latest version, March 2026
- [coverage.py 7.13.5 Docs](https://coverage.readthedocs.io/) -- Branch coverage, threshold enforcement
- [Playwright Release Notes](https://playwright.dev/docs/release-notes) -- v1.58 timeline, trace improvements
- [MSW Quick Start](https://mswjs.io/docs/quick-start/) -- Network-level API mocking
- [Codecov Monorepo Flags](https://docs.codecov.com/docs/flags) -- Multi-language coverage merging
- [PCOV GitHub](https://github.com/krakjoe/pcov) -- Lightweight coverage driver

---

*Stack research: 2026-03-25*
