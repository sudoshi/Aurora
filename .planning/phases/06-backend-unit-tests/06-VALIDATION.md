---
phase: 6
slug: backend-unit-tests
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 6 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 3.8 (PHP) |
| **Config file** | `backend/tests/Pest.php` |
| **Quick run command** | `cd /home/smudoshi/Github/Aurora/backend && APP_ENV=testing php vendor/bin/pest tests/Unit/ --filter=AuthService` |
| **Full suite command** | `cd /home/smudoshi/Github/Aurora/backend && APP_ENV=testing php vendor/bin/pest tests/Unit/` |
| **Estimated runtime** | ~10 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick test for the service just written
- **After every plan wave:** Run full unit test suite
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 10 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 06-01-01 | 01 | 1 | BTEST-08 | unit | `php vendor/bin/pest tests/Unit/Services/AuthServiceTest.php` | ❌ W0 | ⬜ pending |
| 06-01-02 | 01 | 1 | BTEST-09 | unit | `php vendor/bin/pest tests/Unit/Services/PatientServiceTest.php` | ❌ W0 | ⬜ pending |
| 06-02-01 | 02 | 1 | BTEST-10 | unit | `php vendor/bin/pest tests/Unit/Services/CaseServiceTest.php` | ❌ W0 | ⬜ pending |
| 06-02-02 | 02 | 1 | BTEST-11 | unit | `php vendor/bin/pest tests/Unit/Services/RadiogenomicsServiceTest.php` | ❌ W0 | ⬜ pending |
| 06-02-03 | 02 | 1 | BTEST-12 | unit | `php vendor/bin/pest tests/Unit/Services/OncoKbServiceTest.php` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] 5 new test files in `backend/tests/Unit/Services/`
- [ ] All factories already available from Phase 3

---

## Manual-Only Verifications

*All phase behaviors have automated verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 10s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
