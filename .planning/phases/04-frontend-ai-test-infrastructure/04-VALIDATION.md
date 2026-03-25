---
phase: 4
slug: frontend-ai-test-infrastructure
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Vitest 3 (frontend), pytest 8.3 (AI), Playwright (E2E) |
| **Config files** | `frontend/vite.config.ts`, `ai/pytest.ini`, `e2e/playwright.config.ts` |
| **Quick run command** | `cd frontend && npx vitest run --reporter=verbose 2>&1 | tail -10` |
| **Full suite command** | `cd frontend && npx vitest run --coverage && cd ../ai && pytest --cov && cd ../e2e && npx playwright test --reporter=list` |
| **Estimated runtime** | ~15 seconds (smoke tests only) |

---

## Sampling Rate

- **After every task commit:** Run quick smoke test for modified service
- **After every plan wave:** Run full suite across all 3 services
- **Before `/gsd:verify-work`:** All smoke tests must pass
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 04-01-01 | 01 | 1 | INFRA-03 | config | `cd frontend && npx vitest run` passes with coverage | ❌ W0 | ⬜ pending |
| 04-01-02 | 01 | 1 | INFRA-04 | unit | MSW handlers intercept and return data in smoke test | ❌ W0 | ⬜ pending |
| 04-01-03 | 01 | 1 | INFRA-05 | unit | Test utilities render component with providers | ❌ W0 | ⬜ pending |
| 04-02-01 | 02 | 1 | INFRA-06 | config | `cd ai && pytest --cov` passes with coverage | ❌ W0 | ⬜ pending |
| 04-02-02 | 02 | 1 | INFRA-07 | config | pytest fixtures with mocked Ollama work | ❌ W0 | ⬜ pending |
| 04-02-03 | 02 | 1 | INFRA-08 | config | `cd e2e && npx playwright test` skeleton passes | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `frontend/src/test/setup.ts` — Vitest setup with cleanup
- [ ] `frontend/src/test/test-utils.tsx` — Provider wrappers
- [ ] `frontend/src/test/mocks/handlers.ts` — MSW handlers
- [ ] `frontend/src/test/smoke.test.ts` — Vitest smoke test
- [ ] `ai/pytest.ini` — pytest config with asyncio_mode
- [ ] `ai/tests/conftest.py` — Fixtures with mocked Ollama
- [ ] `ai/tests/test_smoke.py` — pytest smoke test

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
