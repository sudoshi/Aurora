# Phase 10: E2E Tests - Research

**Researched:** 2026-03-25
**Domain:** Playwright E2E testing for Aurora clinical platform
**Confidence:** HIGH

## Summary

Phase 10 implements four critical E2E user flows using Playwright against the deployed Aurora app at `https://aurora.acumenus.net`. The Playwright infrastructure is already established from Phase 4 (INFRA-08): `e2e/playwright.config.ts` is configured, a smoke test passes, and there are existing v1-era test files that provide useful patterns but need rewriting to match the current v2 UI.

The app uses a top-navigation header (not a sidebar) with dropdown menus for navigation groups (Clinical > Cases, Patient Profiles; Intelligence > Genomics). Login uses labeled form fields (`Email`, `Password`) with a `Sign In` button. The dashboard shows metric cards (Total Patients, Active Cases). Patient profiles are at `/profiles` with a table of patients. Genomics is accessed via a "Genomics" view-mode button within a patient profile. Cases are at `/cases` with a "New Case" button that opens a modal form, and case detail pages have an Overview/Documents/Team tab bar.

**Primary recommendation:** Rewrite the 4 E2E spec files to target the actual v2 UI selectors discovered in this research. Use the existing `loginAsAdmin` helper. Tests run against the live deployed app with seeded data.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| E2E-01 | Login flow -- admin logs in, sees dashboard | LoginPage uses `label="Email"`, `label="Password"`, `button="Sign In"`. Dashboard shows `h1="Dashboard"` and MetricCard with "Total Patients". |
| E2E-02 | Patient profile -- navigate to patient, view tabs | Navigate via Clinical dropdown > Patient Profiles (`/profiles`). Table rows are clickable `<tr>` elements. Patient detail has view-mode buttons (Briefing, Timeline, List, Labs, etc.). |
| E2E-03 | Genomics tab -- view briefing, variants, interactions, timeline | Within patient profile, click "Genomics" view-mode button. PatientGenomicsTab renders GenomicBriefing, ActionableVariantsPanel, TreatmentTimeline, GenomicVariantTable sections. |
| E2E-04 | Case management -- create case, add team member, view case | `/cases` page has "New Case" button. CaseForm modal with `label="Title"`, specialty/type/urgency selects, "Create Case" submit. Case detail has Team tab with "Add Member" button. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| @playwright/test | 1.58.2 | E2E browser testing | Already installed in e2e/package.json |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| (none needed) | - | - | All dependencies already in place |

**Installation:**
```bash
cd e2e && npm install  # Only if node_modules missing
```

## Architecture Patterns

### Existing Test Structure
```
e2e/
  playwright.config.ts    # baseURL: https://aurora.acumenus.net, chromium only
  package.json            # scripts: test, test:ui, test:headed, test:debug
  tests/
    helpers.ts            # loginAsAdmin(), navigateTo() helpers
    smoke.spec.ts         # Phase 4 smoke tests (keep)
    auth.spec.ts          # v1 tests (REWRITE)
    patient-profile.spec.ts  # v1 tests (REWRITE)
    case-lifecycle.spec.ts   # v1 tests (REWRITE)
    admin.spec.ts         # v1 tests (out of scope for Phase 10)
    commons.spec.ts       # v1 tests (out of scope)
    copilot.spec.ts       # v1 tests (out of scope)
    imaging.spec.ts       # v1 tests (out of scope)
    session-lifecycle.spec.ts  # v1 tests (out of scope)
```

### Pattern 1: Login Helper
**What:** `loginAsAdmin()` navigates to /login, fills email/password, clicks Sign In, waits for navigation away from /login
**Already exists in:** `e2e/tests/helpers.ts`
**Key selectors (verified from LoginPage.tsx):**
```typescript
page.getByLabel(/email/i)      // <label htmlFor="email">Email</label>
page.getByLabel(/password/i)   // <label htmlFor="password">Password</label>
page.getByRole("button", { name: /sign in/i })  // <button>Sign In</button>
```

### Pattern 2: Top Navigation (NOT Sidebar)
**What:** The v2 app uses a top navigation header with dropdown menus, NOT a sidebar
**Critical difference from v1 tests:** v1 tests use `navigateTo(page, "Patient")` which clicks sidebar links. v2 uses dropdown navigation groups.
**Navigation structure (from `config/navigation.ts`):**
- Dashboard: direct link to `/`
- Clinical (dropdown): Cases `/cases`, Sessions `/sessions`, Patient Profiles `/profiles`, Decisions `/decisions`
- Intelligence (dropdown): Imaging, Genomics `/genomics`, AI Copilot
- Commons: direct link to `/commons`
- Admin (dropdown, admin-only): Admin Dashboard, System Health, Users, etc.

**Navigation approach for tests:** Use `page.goto('/cases')` directly rather than trying to click through dropdown menus, since dropdown hover-based navigation is fragile in E2E tests.

### Pattern 3: Resilient Selectors
**What:** Use Playwright's user-facing locators (getByRole, getByLabel, getByText) with fallbacks
**Example:**
```typescript
// Good: targets accessible role
page.getByRole("heading", { name: /dashboard/i })

// Good: targets visible text content
page.getByText(/total patients/i)

// Fallback: direct URL navigation instead of clicking nav links
await page.goto("/profiles");
```

### Anti-Patterns to Avoid
- **Using sidebar selectors:** The v2 app has NO sidebar. v1 test files reference `data-testid='sidebar'`, `nav a`, `aside a` -- all wrong for v2.
- **Relying on hover-based dropdown navigation:** The Header.tsx uses `onPointerEnter`/`onPointerLeave` for dropdowns, which is unreliable in E2E. Navigate via URL instead.
- **Using `waitForTimeout`:** Prefer `waitForSelector`, `expect().toBeVisible()`, or `waitForURL` over arbitrary timeouts.
- **Conditional test logic:** v1 tests have many `if (await X.isVisible())` guards that silently skip assertions. Tests should assert expectations, not conditionally skip them.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Login flow | Custom auth token injection | `loginAsAdmin()` helper | Already exists, tests real login UI |
| Navigation | Dropdown menu clicking | `page.goto('/path')` | Hover dropdowns are fragile in E2E |
| Wait for data | `waitForTimeout(ms)` | `expect(locator).toBeVisible()` | Deterministic, resilient waits |

## Common Pitfalls

### Pitfall 1: v1 Test Files Have Wrong Selectors
**What goes wrong:** Existing v1 spec files (auth.spec.ts, patient-profile.spec.ts, case-lifecycle.spec.ts) reference UI elements that don't exist in v2 (sidebar, data-testid attributes, etc.)
**Why it happens:** v1 and v2 have completely different layouts
**How to avoid:** Rewrite spec files from scratch using the actual v2 component source as reference. Key differences:
- No sidebar -- top nav with dropdowns
- Patient list is a `<table>` with clickable `<tr>` rows (not card links)
- Case creation uses a modal form (CaseForm component)
- Genomics is a view mode within patient profile, not a separate page link
**Warning signs:** Tests pass but don't actually verify anything (empty conditional blocks)

### Pitfall 2: Genomics Tab May Not Appear Without Data
**What goes wrong:** The Genomics view-mode button is conditionally hidden when `(profile.genomics ?? []).length === 0`
**How to avoid:** The test must either: (a) navigate to a patient known to have genomic data, or (b) handle the case where the button is absent gracefully with a clear skip message. Since we're testing against deployed app with seeded data, verify which patients have genomic data first.

### Pitfall 3: Dropdown Navigation Race Conditions
**What goes wrong:** Clicking the "Clinical" dropdown, then clicking "Patient Profiles" -- the dropdown may close before the link is clicked
**How to avoid:** Navigate via URL (`page.goto('/profiles')`) instead of trying to use the dropdown menus

### Pitfall 4: Modal Form Overlay
**What goes wrong:** The CaseForm and AddMemberForm use fixed-position overlays. Playwright might click the backdrop instead of the form
**How to avoid:** Use specific label-based locators within the modal form, not broad page-level locators

### Pitfall 5: Tests Depend on Seeded Data
**What goes wrong:** Tests assume patients, cases, or genomic data exist in the database
**How to avoid:** The E2E tests run against the deployed app (aurora.acumenus.net) with seeded data. For E2E-04 (case creation), the test creates its own case -- no data dependency. For E2E-02/E2E-03, tests should handle empty state gracefully or target known seeded patients.

## Code Examples

### E2E-01: Login Flow (verified selectors from LoginPage.tsx)
```typescript
test("admin can log in and see the dashboard", async ({ page }) => {
  await page.goto("/login");
  await page.getByLabel(/email/i).fill("admin@acumenus.net");
  await page.getByLabel(/password/i).fill("superuser");
  await page.getByRole("button", { name: /sign in/i }).click();

  // Dashboard loads
  await expect(page).not.toHaveURL(/\/login/);
  await expect(page.getByRole("heading", { name: /dashboard/i })).toBeVisible();
  // MetricCard shows patient count
  await expect(page.getByText(/total patients/i)).toBeVisible();
});
```

### E2E-02: Patient Profile Navigation (verified from PatientProfilePage.tsx)
```typescript
test("navigate to patient and view tabs", async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto("/profiles");

  // Patient list landing has title
  await expect(page.getByRole("heading", { name: /patient profiles/i })).toBeVisible();

  // Click first patient row in table
  const firstRow = page.locator("table tbody tr").first();
  await firstRow.click();

  // Patient profile loads with demographics
  await expect(page.getByRole("heading", { name: /patient profile/i })).toBeVisible();

  // View mode buttons are visible (from VIEW_BUTTONS array)
  await expect(page.getByRole("button", { name: /timeline/i })).toBeVisible();
  await expect(page.getByRole("button", { name: /labs/i })).toBeVisible();

  // Click Timeline view
  await page.getByRole("button", { name: /timeline/i }).click();
});
```

### E2E-03: Genomics Tab (verified from PatientGenomicsTab.tsx)
```typescript
// Genomics view mode button text is "Genomics" (from VIEW_BUTTONS)
// PatientGenomicsTab renders 4 sections: GenomicBriefing, ActionableVariantsPanel,
// TreatmentTimeline, GenomicVariantTable
// If no variant data, shows empty state: "No genomic data available"
```

### E2E-04: Case Management (verified from CaseListPage.tsx, CaseForm.tsx, CaseTeamPanel.tsx)
```typescript
test("create case, add team member", async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto("/cases");

  // Click "New Case" button
  await page.getByRole("button", { name: /new case/i }).click();

  // Fill case form (modal)
  await page.getByLabel(/title/i).fill("E2E Test Case");
  // Specialty defaults to "oncology", case_type to "tumor_board", urgency to "routine"

  // Submit
  await page.getByRole("button", { name: /create case/i }).click();

  // Case should appear -- navigate to it
  // ...

  // On case detail, click "Team" tab
  await page.getByRole("tab", { name: /team/i })
    .or(page.getByText(/team/i)).click();

  // Click "Add Member"
  await page.getByRole("button", { name: /add member/i }).click();

  // Fill user ID (admin is user 1)
  await page.getByLabel(/user id/i).fill("1");
  await page.getByRole("button", { name: /add member/i }).click();
});
```

### Key Selectors Reference Table

| Page | Element | Selector |
|------|---------|----------|
| Login | Email input | `getByLabel(/email/i)` |
| Login | Password input | `getByLabel(/password/i)` |
| Login | Submit button | `getByRole("button", { name: /sign in/i })` |
| Login | Create Account link | `getByRole("link", { name: /create account/i })` |
| Login | Error message | `getByText(/invalid|error/i)` via `.auth-form-error` div |
| Dashboard | Page heading | `getByRole("heading", { name: /dashboard/i })` |
| Dashboard | Patient count metric | `getByText(/total patients/i)` |
| Dashboard | Active cases metric | `getByText(/active cases/i)` |
| Patient List | Page title | heading with text "Patient Profiles" |
| Patient List | Search input | `getByPlaceholder(/search by name/i)` |
| Patient List | Patient row | `table tbody tr` (clickable) |
| Patient Detail | Page heading | `getByRole("heading", { name: /patient profile/i })` |
| Patient Detail | View mode buttons | `getByRole("button", { name: /briefing|timeline|list|labs|visits|notes|genomics/i })` |
| Genomics Tab | Briefing section | GenomicBriefing component renders briefing narrative |
| Genomics Tab | Empty state | `getByText(/no genomic data available/i)` |
| Cases List | Page heading | `getByRole("heading", { name: /cases/i })` |
| Cases List | New Case button | `getByRole("button", { name: /new case/i })` |
| Case Form | Title input | `getByLabel(/title/i)` (id="case-title") |
| Case Form | Create button | `getByRole("button", { name: /create case/i })` |
| Case Detail | Tab bar | buttons with role="tab": Overview, Documents, Team |
| Case Detail | Team tab content | "Team Members" heading + "Add Member" button |
| Team Panel | Add Member button | `getByRole("button", { name: /add member/i })` |
| Team Panel | User ID input | `getByLabel(/user id/i)` (id="member-user-id") |
| Header | Logout | UserDropdown > "Logout" button |
| Header | Nav groups | Top nav dropdowns (Clinical, Intelligence, Admin, etc.) |

## State of the Art

| Old Approach (v1 tests) | Current Approach (v2) | Impact |
|-------------------------|----------------------|--------|
| Sidebar navigation | Top nav with dropdown menus | All `navigateTo()` calls broken for sidebar; use `page.goto()` |
| `data-testid` selectors | User-facing locators (getByRole, getByLabel) | More resilient to refactoring |
| Conditional `if (visible)` blocks | Direct assertions | Tests actually verify behavior |
| Card-based patient list | Table-based patient list | Selectors need updating |

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | @playwright/test 1.58.2 |
| Config file | e2e/playwright.config.ts |
| Quick run command | `cd e2e && npx playwright test --grep "E2E-0[1234]"` |
| Full suite command | `cd e2e && npx playwright test` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| E2E-01 | Admin login + dashboard visible | e2e | `cd e2e && npx playwright test auth.spec.ts` | Exists (needs rewrite) |
| E2E-02 | Navigate to patient, view tabs | e2e | `cd e2e && npx playwright test patient-profile.spec.ts` | Exists (needs rewrite) |
| E2E-03 | Genomics tab: briefing, variants, interactions, timeline | e2e | `cd e2e && npx playwright test genomics.spec.ts` | New file needed |
| E2E-04 | Create case, add team member, view detail | e2e | `cd e2e && npx playwright test case-lifecycle.spec.ts` | Exists (needs rewrite) |

### Sampling Rate
- **Per task commit:** `cd e2e && npx playwright test <spec_file> --reporter=list`
- **Per wave merge:** `cd e2e && npx playwright test --reporter=list`
- **Phase gate:** All 4 spec files green before /gsd:verify-work

### Wave 0 Gaps
- [ ] `e2e/tests/auth.spec.ts` -- rewrite for v2 selectors (E2E-01)
- [ ] `e2e/tests/patient-profile.spec.ts` -- rewrite for v2 selectors (E2E-02)
- [ ] `e2e/tests/genomics.spec.ts` -- new file (E2E-03)
- [ ] `e2e/tests/case-lifecycle.spec.ts` -- rewrite for v2 selectors (E2E-04)
- [ ] `e2e/tests/helpers.ts` -- verify `loginAsAdmin` still works; `navigateTo` may be obsolete

## Open Questions

1. **Which patients have genomic data?**
   - What we know: PatientGenomicsTab conditionally hides the Genomics button when `(profile.genomics ?? []).length === 0`
   - What's unclear: Which seeded patients have genomic data in the deployed app
   - Recommendation: Test should navigate to `/profiles`, pick first patient, check if Genomics button exists. If not, the test should try another patient or report a clear skip reason. Alternatively, query API first to find a patient with genomic data.

2. **Case creation API response timing**
   - What we know: CaseForm uses `createCase.mutate()` with `onSuccess: () => setShowForm(false)`
   - What's unclear: Whether the case list refreshes immediately after modal closes
   - Recommendation: After creating case, wait for modal to close, then verify case title appears in the list or navigate to `/cases` and search.

## Sources

### Primary (HIGH confidence)
- `e2e/playwright.config.ts` -- Playwright configuration (baseURL, timeout, projects)
- `e2e/tests/helpers.ts` -- Existing login helper
- `e2e/tests/smoke.spec.ts` -- Working smoke tests from Phase 4
- `frontend/src/features/auth/pages/LoginPage.tsx` -- Login form selectors
- `frontend/src/features/dashboard/pages/DashboardPage.tsx` -- Dashboard metrics
- `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx` -- Patient list + profile
- `frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx` -- Genomics sections
- `frontend/src/features/cases/pages/CaseListPage.tsx` -- Case list + creation
- `frontend/src/features/cases/pages/CaseDetailPage.tsx` -- Case detail + tabs
- `frontend/src/features/cases/components/CaseForm.tsx` -- Case creation form fields
- `frontend/src/features/cases/components/CaseTeamPanel.tsx` -- Team + add member form
- `frontend/src/components/layouts/DashboardLayout.tsx` -- Layout (no sidebar)
- `frontend/src/components/layout/Header.tsx` -- Top navigation
- `frontend/src/config/navigation.ts` -- Nav groups and routes
- `frontend/src/App.tsx` -- All route definitions

### Secondary (MEDIUM confidence)
- Existing v1 test files (auth.spec.ts, patient-profile.spec.ts, case-lifecycle.spec.ts) -- pattern reference only, selectors are stale

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Playwright already installed and configured from Phase 4
- Architecture: HIGH - All selectors verified directly from source component files
- Pitfalls: HIGH - Compared v1 test selectors against v2 source; key differences documented

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable -- Playwright config and component structure unlikely to change)
