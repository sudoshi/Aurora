---
phase: 1
slug: fix-critical-blocker-verify-core-endpoints
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | curl / httpie (manual endpoint verification) |
| **Config file** | none — this phase is bug fixing and manual verification |
| **Quick run command** | `curl -s -o /dev/null -w '%{http_code}' http://localhost:8085/api/login -X POST -H 'Content-Type: application/json' -d '{"email":"admin@acumenus.net","password":"superuser"}'` |
| **Full suite command** | `bash .planning/phases/01-fix-critical-blocker-verify-core-endpoints/verify-endpoints.sh` |
| **Estimated runtime** | ~10 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick login check
- **After every plan wave:** Run full endpoint verification script
- **Before `/gsd:verify-work`:** All endpoints must return expected status codes
- **Max feedback latency:** 10 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | BUG-01 | config | verify clinical connection exists in config | ❌ W0 | ⬜ pending |
| 01-01-02 | 01 | 1 | BUG-02 | endpoint | `curl POST /api/login` returns 200 | ❌ W0 | ⬜ pending |
| 01-01-03 | 01 | 1 | BUG-03 | endpoint | `curl POST /api/register` returns 200/201 | ❌ W0 | ⬜ pending |
| 01-01-04 | 01 | 1 | BUG-04 | endpoint | `curl POST /api/change-password` returns 200 | ❌ W0 | ⬜ pending |
| 01-01-05 | 01 | 1 | BUG-05 | endpoint | `curl GET /api/dashboard` returns 200 | ❌ W0 | ⬜ pending |
| 01-01-06 | 01 | 1 | BUG-06 | endpoint | `curl GET /api/patients` returns 200 | ❌ W0 | ⬜ pending |
| 01-01-07 | 01 | 1 | BUG-07 | endpoint | `curl POST /api/cases` returns 200/201 | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `verify-endpoints.sh` — shell script that tests all 7 endpoints and reports pass/fail
- [ ] Endpoint verification requires a valid Sanctum token (obtained from login)

*Wave 0 creates the verification script as part of the fix plan.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Register sends temp password email | BUG-03 | Requires Resend API key and email delivery | Check Resend dashboard or use test email |

---

## Validation Sign-Off

- [ ] All tasks have automated verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 10s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
