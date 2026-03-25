---
phase: 10
slug: e2e-tests
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 10 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Playwright 1.58.2 |
| **Config file** | `e2e/playwright.config.ts` |
| **Quick run command** | `cd e2e && npx playwright test tests/login.spec.ts --reporter=list` |
| **Full suite command** | `cd e2e && npx playwright test --reporter=list` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run the spec just written
- **After every plan wave:** Run full E2E suite
- **Before `/gsd:verify-work`:** All E2E specs pass
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 10-01-01 | 01 | 1 | E2E-01, E2E-02 | e2e | `npx playwright test tests/login.spec.ts tests/patient-profile.spec.ts` | ❌ W0 | ⬜ pending |
| 10-02-01 | 02 | 1 | E2E-03, E2E-04 | e2e | `npx playwright test tests/genomics.spec.ts tests/case-management.spec.ts` | ❌ W0 | ⬜ pending |

---

## Wave 0 Requirements

- [ ] 4 new E2E spec files
- [ ] `loginAsAdmin` helper already exists from Phase 4

---

## Manual-Only Verifications

*All phase behaviors have automated verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
