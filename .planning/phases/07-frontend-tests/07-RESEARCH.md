# Phase 7: Frontend Tests - Research

**Researched:** 2026-03-25
**Domain:** React/TypeScript frontend testing with Vitest, MSW, React Testing Library
**Confidence:** HIGH

## Summary

Phase 7 writes tests for four categories of frontend code: Zustand stores (authStore, profileStore), TanStack Query hooks (useGenomics.ts with 17 exported hooks), genomics components (7 components), and auth pages (LoginPage, RegisterPage). The test infrastructure from Phase 4 is fully operational -- Vitest with jsdom, MSW 2.x mock server, React Testing Library with provider wrappers, and V8 coverage are all configured and verified with 8 passing tests.

The existing authStore test file (`stores/__tests__/authStore.test.ts`) already covers 3 basic state transitions (initial state, setAuth, logout). Phase 7 needs to extend this with updateUser, hasRole, hasPermission, isAdmin, isSuperAdmin tests (FTEST-01), add profileStore tests (FTEST-02), then build out hook tests (FTEST-03), component tests (FTEST-04 through FTEST-08), auth page tests (FTEST-09), and hit 80% coverage (FTEST-10).

**Primary recommendation:** Use the established Phase 4 patterns -- colocate test files in `__tests__` directories, use `renderHookWithProviders` for hook tests, `renderWithProviders` for component tests, extend MSW handlers for each test file's needs via `server.use()`, and `resetStores()` in afterEach for isolation.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| FTEST-01 | Store tests for authStore (login, logout, token management) | Existing test covers 3/8 behaviors; need updateUser, hasRole, hasPermission, isAdmin, isSuperAdmin |
| FTEST-02 | Store tests for profileStore (profile loading, updates) | profileStore has addRecentProfile (dedup, MAX_RECENT=15, timestamp), clearRecentProfiles |
| FTEST-03 | Hook tests for useGenomics hooks (useInteractions, useBriefing, useVariants, useRadiogenomics) | 4 hooks need MSW handlers; useGeneDrugInteractions, useGenomicBriefing, useGenomicVariants, useRadiogenomicsPanel |
| FTEST-04 | Component tests for GenomicBriefing (renders briefing, handles loading/error) | Component uses mutation, auto-fires on mount if variants exist, shows loading/error/success/empty states |
| FTEST-05 | Component tests for ActionableVariantsPanel (renders variants, VUS accordion) | Filters by clinvar_significance, renders ActionableVariantCard for pathogenic, VUS accordion toggle |
| FTEST-06 | Component tests for GenomicVariantTable (filtering, sorting, search, expansion) | Uses useGenomicVariants hook, has significance filter, gene search, pagination, row expansion |
| FTEST-07 | Component tests for TreatmentTimeline (renders drug exposures proportionally) | Accordion with proportional bars, correlates with VariantDrugCorrelation for color coding |
| FTEST-08 | Component tests for EvidenceBadge (renders correct badge for evidence level) | Pure presentational: maps evidence level to color, shows source, stale warning at >30 days |
| FTEST-09 | Component tests for LoginForm and RegisterPage (form submission, validation) | LoginPage uses authApi.login + authStore.setAuth; RegisterPage uses authApi.register, shows success message |
| FTEST-10 | Frontend test coverage reaches 80%+ | V8 coverage configured, run `npx vitest run --coverage` |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| vitest | ^3.0.0 | Test runner | Already configured in Phase 4, globals enabled |
| @testing-library/react | ^16.0.0 | Component rendering | Standard React testing, already installed |
| @testing-library/user-event | ^14.6.1 | User interaction simulation | Already installed, preferred over fireEvent |
| @testing-library/jest-dom | ^6.0.0 | DOM matchers | Already configured in setup.ts |
| msw | ^2.12.14 | API mocking | Already configured with handlers and server |
| @vitest/coverage-v8 | ^3.2.4 | Coverage reporting | Already configured, V8 provider |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| jsdom | ^25.0.0 | DOM environment | Configured as Vitest environment |

### No Additional Packages Needed

All test dependencies are already installed from Phase 4.

**Run tests:**
```bash
cd frontend && npx vitest run
cd frontend && npx vitest run --coverage
```

## Architecture Patterns

### Test File Organization
```
frontend/src/
  stores/
    __tests__/
      authStore.test.ts         # EXISTS (3 tests) -- extend
      profileStore.test.ts      # NEW
  features/
    genomics/
      hooks/
        __tests__/
          useGenomics.test.ts   # NEW
      components/
        __tests__/
          GenomicBriefing.test.tsx          # NEW
          ActionableVariantsPanel.test.tsx  # NEW
          GenomicVariantTable.test.tsx      # NEW
          TreatmentTimeline.test.tsx        # NEW
          EvidenceBadge.test.tsx            # NEW
    auth/
      pages/
        __tests__/
          LoginPage.test.tsx    # NEW
          RegisterPage.test.tsx # NEW
  test/
    setup.ts        # EXISTS -- MSW lifecycle, jest-dom
    utils.tsx        # EXISTS -- createWrapper, renderWithProviders, renderHookWithProviders, resetStores
    mocks/
      handlers.ts   # EXISTS -- base handlers for login, patients, dashboard, genomics/interactions
      server.ts     # EXISTS -- setupServer
```

### Pattern 1: Zustand Store Testing
**What:** Test store state transitions using renderHook + act
**When to use:** authStore, profileStore tests
**Example:**
```typescript
// Source: existing authStore.test.ts pattern
import { renderHook, act } from "@testing-library/react";
import { useAuthStore } from "@/stores/authStore";
import { resetStores } from "@/test/utils";

afterEach(() => resetStores());

it("checks role membership", () => {
  const { result } = renderHook(() => useAuthStore());
  act(() => { result.current.setAuth("tok", mockUser); });
  expect(result.current.hasRole("physician")).toBe(true);
  expect(result.current.hasRole("admin")).toBe(false);
});
```

### Pattern 2: TanStack Query Hook Testing
**What:** Test hooks using renderHookWithProviders + MSW + waitFor
**When to use:** useGenomics hook tests
**Example:**
```typescript
import { renderHookWithProviders, resetStores } from "@/test/utils";
import { server } from "@/test/mocks/server";
import { http, HttpResponse } from "msw";
import { waitFor } from "@testing-library/react";

afterEach(() => resetStores());

it("fetches gene-drug interactions", async () => {
  server.use(
    http.get("/api/genomics/interactions", () =>
      HttpResponse.json({ success: true, data: [{ id: 1, gene: "BRCA1", drug: "Olaparib" }] }),
    ),
  );
  const { result } = renderHookWithProviders(() => useGeneDrugInteractions("BRCA1"));
  await waitFor(() => expect(result.current.isSuccess).toBe(true));
  expect(result.current.data).toHaveLength(1);
});
```

### Pattern 3: Component Testing with MSW
**What:** Render components that use hooks/API calls with MSW providing responses
**When to use:** GenomicBriefing, GenomicVariantTable, ActionableVariantCard, VariantExpandedRow
**Example:**
```typescript
import { renderWithProviders, resetStores } from "@/test/utils";
import { screen, waitFor } from "@testing-library/react";
import { server } from "@/test/mocks/server";
import { http, HttpResponse } from "msw";

afterEach(() => resetStores());

it("renders briefing text on success", async () => {
  server.use(
    http.post("http://localhost:8100/api/decision-support/genomic-briefing", () =>
      HttpResponse.json({ briefing: "Patient has BRCA1...", generated_at: "2026-03-25", variant_count: 3, actionable_count: 1 }),
    ),
  );
  renderWithProviders(<GenomicBriefing briefingData={mockBriefingRequest} />);
  await waitFor(() => expect(screen.getByText(/Patient has BRCA1/)).toBeInTheDocument());
});
```

### Pattern 4: Pure Component Testing (No API)
**What:** Render presentational components with props, assert rendered output
**When to use:** EvidenceBadge, TreatmentTimeline, ActionableVariantsPanel
**Example:**
```typescript
it("renders Level 1A badge with correct text", () => {
  renderWithProviders(<EvidenceBadge evidenceLevel="1A" source="oncokb" />);
  expect(screen.getByText("Level 1A")).toBeInTheDocument();
  expect(screen.getByText("oncokb")).toBeInTheDocument();
});
```

### Pattern 5: Form Testing with user-event
**What:** Simulate user typing and form submission using userEvent
**When to use:** LoginPage, RegisterPage tests
**Example:**
```typescript
import userEvent from "@testing-library/user-event";

it("submits login form and calls setAuth", async () => {
  const user = userEvent.setup();
  server.use(
    http.post("/api/auth/login", () =>
      HttpResponse.json({ data: { access_token: "tok", user: mockUser } }),
    ),
  );
  renderWithProviders(<LoginPage />);
  await user.type(screen.getByLabelText(/email/i), "admin@acumenus.net");
  await user.type(screen.getByLabelText(/password/i), "superuser");
  await user.click(screen.getByRole("button", { name: /sign in/i }));
  await waitFor(() => expect(useAuthStore.getState().isAuthenticated).toBe(true));
});
```

### Anti-Patterns to Avoid
- **Testing implementation details:** Test behavior (what the user sees), not internal state changes. Exception: Zustand store tests where state IS the behavior.
- **Not resetting stores between tests:** Always call `resetStores()` in afterEach. Phase 4 established this pattern.
- **Using fireEvent instead of userEvent:** userEvent simulates real user interactions more accurately (focus, keypress sequences).
- **Hardcoding MSW handlers in global handlers.ts for test-specific responses:** Use `server.use()` to override per-test.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Provider wrappers | Custom wrapper per test file | `renderWithProviders` / `renderHookWithProviders` from `@/test/utils` | Already built in Phase 4, handles QueryClient + Router |
| Store cleanup | Manual setState calls | `resetStores()` from `@/test/utils` | Covers all 4 stores consistently |
| API response mocking | Manual fetch mocks | MSW `server.use()` with `http.*` handlers | Network-level interception, already configured |
| User interactions | `fireEvent.click` / `fireEvent.change` | `userEvent.setup()` + `user.type()` / `user.click()` | More realistic, handles focus/blur/keydown chain |
| Waiting for async | `setTimeout` / manual loops | `waitFor(() => expect(...))` from RTL | Proper async waiting with automatic retries |

## Common Pitfalls

### Pitfall 1: API Client baseURL vs MSW Interception
**What goes wrong:** `apiClient` has `baseURL: "/api"`, so requests go to `/api/auth/login`. But `authApi.login` calls `apiClient.post("/auth/login")` which resolves to `/api/auth/login`. MSW handlers in Phase 4 use `/api/login` (different path).
**Why it happens:** Mismatch between MSW handler paths and actual request paths.
**How to avoid:** Auth page tests must use MSW handlers matching `/api/auth/login` and `/api/auth/register` (the actual URL after baseURL is prepended). Genomics API uses paths like `/api/genomics/interactions` via apiClient, but `generateGenomicBriefing` uses raw `fetch` to a different base URL.
**Warning signs:** Tests hang or fail with "unhandled request" warnings.

### Pitfall 2: GenomicBriefing Uses fetch(), Not apiClient
**What goes wrong:** `generateGenomicBriefing` uses native `fetch()` to `http://localhost:8100/api/decision-support/genomic-briefing`, not the Axios apiClient.
**Why it happens:** AI service has separate base URL from Laravel backend.
**How to avoid:** MSW intercepts both fetch and XMLHttpRequest. Handler must match the full URL: `http.post("http://localhost:8100/api/decision-support/genomic-briefing", ...)`. Same for `interpretVariant` which hits `/decision-support/variant-interpret`.
**Warning signs:** Briefing mutation never resolves, loading spinner stays forever.

### Pitfall 3: Components Import InlineActionMenu (Deep Dependency)
**What goes wrong:** `ActionableVariantCard` and `VariantExpandedRow` import `InlineActionMenu` from `@/features/patient-profile/components/InlineActionMenu`, which imports `useCreateFlag` and `useCreateTask` hooks that may make API calls.
**Why it happens:** Component has cross-feature dependencies.
**How to avoid:** Either mock `InlineActionMenu` at the module level with `vi.mock()`, or ensure MSW handlers cover the collaboration API endpoints. Mocking is cleaner for unit tests.
**Warning signs:** Unexpected API calls in test output, unhandled request warnings.

### Pitfall 4: Zustand Persist Middleware and localStorage
**What goes wrong:** authStore and profileStore use `persist()` middleware which reads/writes to localStorage. Tests may leak state between runs.
**Why it happens:** localStorage persists across tests in jsdom.
**How to avoid:** Phase 4 setup.ts already clears `localStorage` and `sessionStorage` in afterEach. Combined with `resetStores()`, this prevents leaks. Already handled.
**Warning signs:** Tests pass individually but fail when run together.

### Pitfall 5: Hooks with `enabled` Flag
**What goes wrong:** `useGenomicVariants` has `enabled: !!(params?.upload_id || params?.person_id || params?.gene)`. If test doesn't pass one of these params, the query never fires.
**Why it happens:** TanStack Query's `enabled` option prevents automatic fetching.
**How to avoid:** Always pass required params in hook tests. E.g., `useGenomicVariants({ person_id: 1 })`.
**Warning signs:** `result.current.isSuccess` never becomes true.

### Pitfall 6: Coverage Denominator is Large
**What goes wrong:** Reaching 80% requires testing most of `src/**/*.{ts,tsx}` (excluding test files, .d.ts, main.tsx). There may be many non-tested UI components, pages, and utilities.
**Why it happens:** Coverage is calculated across ALL source files, not just files with tests.
**How to avoid:** After writing the required tests, run `npx vitest run --coverage` to check. May need to add basic render tests for other commonly-imported files, or narrow the coverage `include` scope.
**Warning signs:** Coverage report shows 40-50% even after writing all required tests.

## Code Examples

### Mock Data Factory: GenomicVariant
```typescript
import type { GenomicVariant } from "@/features/genomics/types";

export function createMockVariant(overrides?: Partial<GenomicVariant>): GenomicVariant {
  return {
    id: 1,
    upload_id: 1,
    source_id: 1,
    person_id: 100,
    sample_id: "S001",
    chromosome: "17",
    position: 43045629,
    reference_allele: "T",
    alternate_allele: "C",
    genome_build: "GRCh38",
    gene_symbol: "BRCA1",
    hgvs_c: "c.5266dupC",
    hgvs_p: "p.Gln1756fs",
    variant_type: "frameshift",
    variant_class: null,
    consequence: "frameshift_variant",
    quality: 99,
    filter_status: "PASS",
    zygosity: "heterozygous",
    allele_frequency: 0.45,
    read_depth: 150,
    clinvar_id: "VCV000017661",
    clinvar_significance: "Pathogenic",
    cosmic_id: null,
    measurement_concept_id: 0,
    mapping_status: "mapped",
    created_at: "2026-03-25T10:00:00Z",
    ...overrides,
  };
}
```

### Mock Data Factory: GeneDrugInteraction
```typescript
import type { GeneDrugInteraction } from "@/features/genomics/types";

export function createMockInteraction(overrides?: Partial<GeneDrugInteraction>): GeneDrugInteraction {
  return {
    id: 1,
    gene: "BRCA1",
    variant_pattern: "*",
    drug: "Olaparib",
    drug_class: "PARP inhibitor",
    relationship: "sensitive",
    evidence_level: "1A",
    indication: "Ovarian cancer",
    mechanism: "Synthetic lethality",
    source: "oncokb",
    source_url: null,
    oncokb_last_synced_at: null,
    last_verified_at: "2026-03-20T10:00:00Z",
    ...overrides,
  };
}
```

### MSW Handlers for Auth Pages
```typescript
// Override in individual test files via server.use()
http.post("/api/auth/login", async ({ request }) => {
  const body = await request.json() as Record<string, string>;
  if (body.email === "admin@acumenus.net") {
    return HttpResponse.json({
      data: { access_token: "test-token", user: mockUser },
    });
  }
  return HttpResponse.json({ message: "Invalid credentials" }, { status: 401 });
});

http.post("/api/auth/register", () => {
  return HttpResponse.json({
    data: { message: "Check your email for a temporary password" },
  });
});
```

### MSW Handlers for Genomics
```typescript
// Variants endpoint (paginated)
http.get("/api/genomics/variants", ({ request }) => {
  const url = new URL(request.url);
  const personId = url.searchParams.get("person_id");
  if (personId) {
    return HttpResponse.json({
      data: [createMockVariant()],
      current_page: 1,
      last_page: 1,
      per_page: 25,
      total: 1,
    });
  }
  return HttpResponse.json({ data: [], current_page: 1, last_page: 1, per_page: 25, total: 0 });
});

// Radiogenomics panel
http.get("/api/radiogenomics/patients/:id", () => {
  return HttpResponse.json({
    success: true,
    data: { patient: { person_id: 100 }, variants: { all: 5, actionable: 2, vus: 1, other: 2, details: [] }, drug_exposures: [], correlations: [], recommendations: [] },
  });
});

// AI briefing (uses fetch, not apiClient -- match full URL)
http.post("http://localhost:8100/api/decision-support/genomic-briefing", () => {
  return HttpResponse.json({
    briefing: "This patient has a BRCA1 frameshift variant...",
    generated_at: "2026-03-25T12:00:00Z",
    variant_count: 5,
    actionable_count: 2,
  });
});
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Jest + Enzyme | Vitest + RTL | 2023+ | RTL focuses on behavior, not implementation |
| `fireEvent` | `userEvent.setup()` | RTL v14+ | More realistic user simulation |
| MSW 1.x `rest.get` | MSW 2.x `http.get` | MSW 2.0 (2023) | New API, already using v2 in Phase 4 |
| Manual mock functions | MSW network-level interception | 2022+ | No need to mock axios/fetch directly |

## Open Questions

1. **Coverage gap from untested files**
   - What we know: Coverage includes ALL src files (stores, features, components, lib, hooks). Required tests cover stores, genomics, and auth.
   - What's unclear: Whether testing only the required components will hit 80%. Other features (patient-profile, settings, administration, commons) have source files that dilute coverage.
   - Recommendation: After writing required tests, check coverage. If below 80%, add basic smoke tests for high-LOC files (DashboardLayout, Sidebar, TopNavigation, etc.) or narrow the coverage `include` to tested modules.

2. **InlineActionMenu mocking strategy**
   - What we know: ActionableVariantCard and VariantExpandedRow depend on InlineActionMenu from patient-profile feature, which uses collaboration hooks.
   - What's unclear: Whether the collaboration API endpoints are complex enough to warrant full MSW handlers.
   - Recommendation: Mock `@/features/patient-profile/components/InlineActionMenu` at module level with `vi.mock()` returning a stub component. Simpler and isolates genomics component tests.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Vitest 3.x with jsdom |
| Config file | `frontend/vite.config.ts` (test block) |
| Quick run command | `cd frontend && npx vitest run` |
| Full suite command | `cd frontend && npx vitest run --coverage` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| FTEST-01 | authStore login/logout/role/permission state transitions | unit | `cd frontend && npx vitest run src/stores/__tests__/authStore.test.ts` | Partial (3 tests exist) |
| FTEST-02 | profileStore add/clear recent profiles | unit | `cd frontend && npx vitest run src/stores/__tests__/profileStore.test.ts` | No - Wave 0 |
| FTEST-03 | useGenomics hooks data fetching via MSW | integration | `cd frontend && npx vitest run src/features/genomics/hooks/__tests__/useGenomics.test.ts` | No - Wave 0 |
| FTEST-04 | GenomicBriefing renders briefing/loading/error | integration | `cd frontend && npx vitest run src/features/genomics/components/__tests__/GenomicBriefing.test.tsx` | No - Wave 0 |
| FTEST-05 | ActionableVariantsPanel renders actionable + VUS | unit | `cd frontend && npx vitest run src/features/genomics/components/__tests__/ActionableVariantsPanel.test.tsx` | No - Wave 0 |
| FTEST-06 | GenomicVariantTable filtering/search/expansion | integration | `cd frontend && npx vitest run src/features/genomics/components/__tests__/GenomicVariantTable.test.tsx` | No - Wave 0 |
| FTEST-07 | TreatmentTimeline proportional bars + accordion | unit | `cd frontend && npx vitest run src/features/genomics/components/__tests__/TreatmentTimeline.test.tsx` | No - Wave 0 |
| FTEST-08 | EvidenceBadge renders correct level/color/stale | unit | `cd frontend && npx vitest run src/features/genomics/components/__tests__/EvidenceBadge.test.tsx` | No - Wave 0 |
| FTEST-09 | LoginPage + RegisterPage form submission | integration | `cd frontend && npx vitest run src/features/auth/pages/__tests__/LoginPage.test.tsx` | No - Wave 0 |
| FTEST-10 | 80% coverage threshold | coverage | `cd frontend && npx vitest run --coverage` | N/A |

### Sampling Rate
- **Per task commit:** `cd frontend && npx vitest run`
- **Per wave merge:** `cd frontend && npx vitest run --coverage`
- **Phase gate:** Full suite green + coverage >= 80% before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `frontend/src/stores/__tests__/profileStore.test.ts` -- covers FTEST-02
- [ ] `frontend/src/features/genomics/hooks/__tests__/useGenomics.test.ts` -- covers FTEST-03
- [ ] `frontend/src/features/genomics/components/__tests__/` (5 test files) -- covers FTEST-04 through FTEST-08
- [ ] `frontend/src/features/auth/pages/__tests__/LoginPage.test.tsx` -- covers FTEST-09
- [ ] `frontend/src/features/auth/pages/__tests__/RegisterPage.test.tsx` -- covers FTEST-09
- [ ] Mock data factories in `frontend/src/test/factories.ts` -- shared across all test files

## Sources

### Primary (HIGH confidence)
- Direct source code inspection of all files to be tested (authStore, profileStore, useGenomics, all 7 genomics components, LoginPage, RegisterPage)
- Phase 4 summary and actual test infrastructure files (setup.ts, utils.tsx, handlers.ts, vite.config.ts)
- Existing authStore.test.ts pattern (verified working with 3 passing tests)
- package.json confirming all test dependencies installed

### Secondary (MEDIUM confidence)
- MSW 2.x handler patterns from existing handlers.ts (verified working in Phase 4 smoke tests)

### Tertiary (LOW confidence)
- Coverage feasibility for 80% threshold -- depends on total LOC denominator, may need supplementary tests

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - all libraries already installed and verified in Phase 4
- Architecture: HIGH - test patterns established with working examples
- Pitfalls: HIGH - identified from actual code inspection (baseURL mismatch, fetch vs apiClient, InlineActionMenu dep)
- Coverage target: MEDIUM - 80% may be tight depending on total source file count

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable infrastructure, no breaking changes expected)
