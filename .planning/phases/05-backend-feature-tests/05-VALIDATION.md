---
phase: 5
slug: backend-feature-tests
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 5 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 3.8 (PHP) with DatabaseTruncation |
| **Config file** | `backend/tests/Pest.php`, `backend/.env.testing` |
| **Quick run command** | `cd backend && php artisan test --env=testing --filter=AuthenticationTest` |
| **Full suite command** | `cd backend && php artisan test --env=testing` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick test for the controller just written
- **After every plan wave:** Run full backend test suite
- **Before `/gsd:verify-work`:** Full suite green, coverage >= 80%
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 05-01-01 | 01 | 1 | BTEST-01 | feature | `php artisan test --filter=AuthenticationTest` | ✅ exists | ⬜ pending |
| 05-01-02 | 01 | 1 | BTEST-06 | feature | `php artisan test --filter=DashboardTest` | ❌ W0 | ⬜ pending |
| 05-02-01 | 02 | 1 | BTEST-02 | feature | `php artisan test --filter=PatientTest` | ✅ exists | ⬜ pending |
| 05-02-02 | 02 | 1 | BTEST-03 | feature | `php artisan test --filter=CaseTest` | ❌ W0 | ⬜ pending |
| 05-03-01 | 03 | 1 | BTEST-04 | feature | `php artisan test --filter=SessionTest` | ❌ W0 | ⬜ pending |
| 05-03-02 | 03 | 1 | BTEST-05 | feature | `php artisan test --filter=GenomicsTest` | ❌ W0 | ⬜ pending |
| 05-03-03 | 03 | 1 | BTEST-07 | feature | `php artisan test --filter=RadiogenomicsTest` | ❌ W0 | ⬜ pending |
| 05-03-04 | 03 | 1 | BTEST-13 | coverage | `php artisan test --coverage --min=80` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Fix `.env.testing` DB_HOST for local execution
- [ ] Create SessionFactory if needed
- [ ] New test files for Case, Session, Genomics, Dashboard, Radiogenomics controllers

---

## Manual-Only Verifications

*All phase behaviors have automated verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
