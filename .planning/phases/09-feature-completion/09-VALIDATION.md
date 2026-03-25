---
phase: 9
slug: feature-completion
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 9 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 3.8 (PHP) |
| **Config file** | `backend/tests/Pest.php` |
| **Quick run command** | `APP_ENV=testing php vendor/bin/pest tests/Feature/Api/GenomicsControllerTest.php -v` |
| **Full suite command** | `APP_ENV=testing php vendor/bin/pest` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run genomics controller tests
- **After every plan wave:** Run full backend suite
- **Before `/gsd:verify-work`:** All tests green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 09-01-01 | 01 | 1 | FEAT-01 | unit | `php vendor/bin/pest tests/Unit/Services/OncoKbServiceTest.php` | ✅ exists | ⬜ pending |
| 09-02-01 | 02 | 1 | FEAT-02 | feature | `php vendor/bin/pest --filter=upload` | ❌ W0 | ⬜ pending |
| 09-02-02 | 02 | 1 | FEAT-03 | feature | `php vendor/bin/pest --filter=criteria` | ❌ W0 | ⬜ pending |

---

## Wave 0 Requirements

- [ ] Migration for `genomic_uploads` table
- [ ] Migration for `genomic_criteria` table
- [ ] `GenomicUpload` and `GenomicCriteria` models
- [ ] Factories for both models
- [ ] Update existing GenomicsControllerTest stubs to assert real persistence

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| OncoKB API integration | FEAT-01 | Requires real OncoKB API token | Verify via `php artisan oncokb:sync --gene=BRAF` if token available |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
