---
phase: 2
slug: verify-genomics-ai-endpoints
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-25
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | curl / httpie (endpoint verification) |
| **Config file** | none — verification via shell script |
| **Quick run command** | `curl -s http://localhost:8085/api/genomics/interactions -H "Authorization: Bearer $TOKEN" \| jq '.data \| length'` |
| **Full suite command** | `bash .planning/phases/02-verify-genomics-ai-endpoints/verify-genomics.sh` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run quick interactions count check
- **After every plan wave:** Run full genomics verification script
- **Before `/gsd:verify-work`:** All endpoints must return expected data
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 01 | 1 | BUG-08 | endpoint | `curl GET /api/genomics/interactions` returns >= 42 records | ❌ W0 | ⬜ pending |
| 02-01-02 | 01 | 1 | BUG-09 | endpoint | `curl GET /api/genomics/stats` returns variant statistics | ❌ W0 | ⬜ pending |
| 02-01-03 | 01 | 1 | BUG-10 | endpoint | `curl POST /decision-support/genomic-briefing` returns narrative | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `verify-genomics.sh` — script that tests all 3 genomics/AI endpoints
- [ ] Ensure GeneDrugInteractionSeeder has been run (>= 42 records)
- [ ] Ensure AI service is running (health check)

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| AI briefing narrative quality | BUG-10 | Requires Ollama with specific model | Verify response contains meaningful clinical narrative, not just error |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
