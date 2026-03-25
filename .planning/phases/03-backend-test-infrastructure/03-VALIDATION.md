---
phase: 3
slug: backend-test-infrastructure
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 3 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 3.8 (PHP) |
| **Config file** | `backend/tests/Pest.php`, `backend/phpunit.xml` |
| **Quick run command** | `docker compose exec php php artisan test --filter=SmokeTest` |
| **Full suite command** | `docker compose exec php php artisan test` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick smoke test
- **After every plan wave:** Run full test suite
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 03-01-01 | 01 | 1 | INFRA-01 | config | `docker compose exec php php artisan test --filter=SmokeTest` | ❌ W0 | ⬜ pending |
| 03-01-02 | 01 | 1 | INFRA-02 | unit | `docker compose exec php php artisan test --filter=FactoryTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `backend/tests/Feature/SmokeTest.php` — validates Pest runs with DatabaseTruncation
- [ ] `backend/tests/Feature/FactoryTest.php` — validates all factories produce valid instances

---

## Manual-Only Verifications

*All phase behaviors have automated verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
