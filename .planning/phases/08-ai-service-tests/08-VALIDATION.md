---
phase: 8
slug: ai-service-tests
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 8 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | pytest 8.3 with pytest-cov |
| **Config file** | `ai/pytest.ini` |
| **Quick run command** | `cd ai && python -m pytest tests/ -x -v 2>&1 \| tail -20` |
| **Full suite command** | `cd ai && python -m pytest tests/ --cov=app -v` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick test for the file just written
- **After every plan wave:** Run full AI test suite with coverage
- **Before `/gsd:verify-work`:** Coverage >= 80% on scoped modules
- **Max feedback latency:** 5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 08-01-01 | 01 | 1 | ATEST-01, ATEST-02 | endpoint | `pytest tests/test_endpoints.py -v` | ❌ W0 | ⬜ pending |
| 08-01-02 | 01 | 1 | ATEST-03, ATEST-04 | service | `pytest tests/test_genomic_briefing.py -v --cov` | ❌ W0 | ⬜ pending |

---

## Wave 0 Requirements

- [ ] `ai/tests/test_endpoints.py` — health + briefing endpoint tests
- [ ] `ai/tests/test_genomic_briefing.py` — service-level tests

---

## Manual-Only Verifications

*All phase behaviors have automated verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
