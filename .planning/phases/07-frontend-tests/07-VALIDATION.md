---
phase: 7
slug: frontend-tests
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 7 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Vitest 3.x with V8 coverage |
| **Config file** | `frontend/vite.config.ts` (test block) |
| **Quick run command** | `cd frontend && npx vitest run --reporter=verbose 2>&1 \| tail -20` |
| **Full suite command** | `cd frontend && npx vitest run --coverage` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick test for the file just written
- **After every plan wave:** Run full frontend test suite
- **Before `/gsd:verify-work`:** Coverage >= 80%
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 07-01-01 | 01 | 1 | FTEST-01 | unit | `npx vitest run src/stores/__tests__/authStore.test.ts` | ✅ exists | ⬜ pending |
| 07-01-02 | 01 | 1 | FTEST-02 | unit | `npx vitest run src/stores/__tests__/profileStore.test.ts` | ❌ W0 | ⬜ pending |
| 07-02-01 | 02 | 1 | FTEST-03 | unit | `npx vitest run src/features/genomics/hooks/__tests__/` | ❌ W0 | ⬜ pending |
| 07-03-01 | 03 | 2 | FTEST-04-08 | component | `npx vitest run src/features/genomics/components/__tests__/` | ❌ W0 | ⬜ pending |
| 07-04-01 | 04 | 2 | FTEST-09 | component | `npx vitest run src/features/auth/__tests__/` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Update MSW handlers for correct auth API paths (`/api/auth/login` not `/api/login`)
- [ ] Add MSW handlers for AI service endpoints (native fetch to `http://localhost:8100`)
- [ ] New test files for stores, hooks, and components

---

## Manual-Only Verifications

*All phase behaviors have automated verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
