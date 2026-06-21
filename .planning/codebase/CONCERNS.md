# Codebase Concerns & Risks

Last updated: 2026-06-20

> This is the **current** risk list, derived from `.planning/GA-READINESS-PLAN.md`
> (workstreams W0–W14) and `docs/security/threat-model.md` (decisions D1/D2). It
> supersedes the 2026-03-24 audit snapshot, whose blockers are now resolved (see
> *Resolved* at the bottom). For full task detail, status markers, and `Verify`
> commands, the GA plan is authoritative — this file summarizes the live risks.

## Open GA blockers — operator / coordination

These are GA-blocking but require sudo or a user decision; code work is done.

- **Reverb production activation (W1-T07).** Real-time is wired and tested, but
  prod needs `systemctl enable --now aurora-reverb`, `a2enmod proxy_wstunnel` +
  the Apache WS snippet, and prod `REVERB_*`/`VITE_REVERB_*` env. Runbook:
  `docs/deployment/realtime-reverb.md`. Until activated, the polling fallback
  (W1-T06) carries live updates.
- **Live multi-user realtime E2E (W1-T08).** `e2e/tests/realtime.spec.ts` must be
  run against a stack with Reverb live to prove push delivery end-to-end.
- **Orthanc credential rotation (W2-T02) + git-history scrub (W2-T03).** The old
  Orthanc proxy credential is in git history and must be treated as compromised.
  The credential is now env-injected (W2-T01 done), but rotation on shared infra
  and BFG/`git filter-repo` history scrub need a user decision + operator action.
  (PHI-access audit logging — the other D1 compensating control — is now **DONE**;
  see W2-T11 in the Resolved section.)

## Accepted-by-design risks (with compensating controls)

Per the decisions recorded in `docs/security/threat-model.md`:

- **Open clinical workspace (D1).** Patient, genomics, imaging, and odyssey
  records (threat-model findings A3–A6) are broadly visible to any authenticated
  clinical user — **accepted** for an MDT/tumor-board tool. Compensating control:
  PHI-access audit logging (W2-T11, **done**) + Research-Use-Only labeling.
  *Note:* A2 (case authorization) was a genuine inconsistency and is **FIXED**
  (CasePolicy team-scoping + tests). Sub-resource controllers (discussion /
  annotation / document / decision) should gate on case access as a fast-follow.
- **Internal-identified exports (D2).** FHIR Genomics export (P1) and the AI
  proxy user-context headers (P5) carry identifiers — **accepted** because they
  are internal-only surfaces. External federation is de-identified: MME label
  de-id (P2) and Beacon k-anonymity (P3, configurable threshold) are done;
  Phenopacket pseudonymous subject (P4) done.

## Remaining workstreams not yet done

Sourced from the GA plan; cite the workstream IDs when picking these up.

### Observability & ops (W3) — GA-blocking
- **W3-T03** Admin status board for stale/error states (OncoKB, ClinVar,
  ClinGen, DICOM sync, AI, federation, Reverb).
- **W3-T04** Structured logging with correlation/request IDs (backend + AI).
- **W3-T05 / W3-T06** Prometheus metrics export + alerting thresholds/routing.
- **W3-T07** Queue worker operability (restart policy, tries/timeout, failed-job
  runbook).
- Done: public readiness probe (W3-T01), nginx healthcheck (W3-T02),
  backup/restore + rollback runbooks (W3-T08/T09).

### Performance, scale & data lifecycle (W11) — GA-blocking
- **W11-T01** App-wide N+1 audit (patient timeline, commons messages, cases,
  genomics tables) + query-count tests on hot endpoints.
- **W11-T02** Verify/add DB indexes for hot query paths (FKs, patient/study/
  variant lookups, channel/message ordering) via scoped migrations.
- **W11-T02b** Move `QUEUE_CONNECTION` to Redis for prod throughput.
- **W11-T03/T04/T05/T06** Pagination/payload limits, frontend bundle/code-split
  audit (lazy-load OHIF), realtime+imaging load tests, data-retention policy.

### Imaging (W4) — partial
- **W4-T01/T02/T03/T04, W14-T01** ImagingController split, withCount N+1 fix +
  query-count test, blank-PatientID enforcement — **DONE 2026-06-20** (see the
  Resolved section). Remaining: **W4-T06** OHIF iframe hardening review (CSP
  `frame-src` — Orthanc auth is already env-injected).

### Imaging AI / segmentation (W5)
- **W5-T02** Distinguish clinician-entered vs computed measurements end-to-end
  (partially addressed: AI imaging responses now carry `data_source`/`verified`/
  `disclaimer`; the per-measurement clinician-vs-computed `source` field is the
  remaining piece).
- **W5-T03/T04/T05 `[OUT-OF-GA]`** Wire a real segmentation model
  (TotalSegmentator / nnU-Net) behind an isolated process/queue boundary;
  persistence + execution-boundary tests. *(W5-T01 mock labeling is DONE — the
  AI responses carry `data_source="mock_model"`/`verified=false`/`disclaimer`.)*

### AI guardrails (W7)
- **W7-T02** Persist Abby chat session state — investigated + **deferred**:
  conversations are already DB-backed (Laravel `AbbyController` + `copilot.py`
  DB sessions); no volatile state confirmed. Reopen only if a real in-memory
  session is found.
- **W7-T04/T05** Pin model IDs in config; decision-quality instrumentation.
  *(W7-T01 advisory labeling + W7-T03 `ai_status=degraded` are DONE.)*

### Interoperability (W6) — partial
- **W6-T01** Validate FHIR R4 conformance of emitted resources against the
  official validator; add a CI validation step.
- **W6-T02/T03** Extend adapter contract tests (Abby, decision-drafting, cohort
  tools); document operator-configurable adapter selection.

### Documentation & release (W13) — GA-blocking
- **W13-T02** Operator from-zero install/runbook matching `deploy.sh` + static
  prod path.
- **W13-T03** "Research Use Only" labeling/disclaimer in-app + docs (ties to
  W5-T01, W7-T01).
- **W13-T04** API documentation (OpenAPI/Scribe) generated from the ~260 routes.
- **W13-T05/T06/T07** LICENSE (Apache 2.0) + CONTRIBUTING/SECURITY/CHANGELOG;
  GA versioning/release process; PHI-handling documentation.

### Accessibility & UX (W12) — partial
- **W12-T01** WCAG 2.1 AA pass on core flows + axe checks in the E2E suite.
- **W12-T02/T04** Consistent loading/empty/error states; accessible auth flows.

### Federation depth (W8), rare-disease follow-ons (W9), TAVR pack (W10)
- Post-GA / partial: `/federation/respond` returns an empty stub (W8-T01);
  Beacon filtering returns empty (W8-T04); VRS degrades to null pending
  SeqRepo/UTA (W9-T01); TAVR population pack (W10) is P3.

### Maintainability / large files (W14, P3, post-GA)
- Oversized modules to split once behavior is stable and tested:
  `ImagingController.php` (~1,990), `PatientTimeline.tsx` (938),
  `commons/api.ts` (835), `CaseDetailPage.tsx` (791), `ImagingPage.tsx` (713),
  `AbbyPanel.tsx` (690), `GenomicsPage.tsx` (648). Not GA-blocking.

## CI coverage floors still open (W0)
- **W0-T05** Reproducible `aurora-ai` Docker test image (host Py 3.14 can't
  build `pydantic-core`); **W0-T06** expand AI coverage scope ≥70%;
  **W0-T07** backend Pest coverage floor (confirm the old "~22 masked failures"
  note is no longer true — backend-test must hard-fail); **W0-T08** frontend
  coverage floor; **W0-T03b** coordinated fastapi/biomcp CVE clearance;
  **W0-T10** "no stub-success" CI guard (land with W1).

---

## Resolved since the 2026-03-24 snapshot

Confirmed via `.planning/GA-READINESS-PLAN.md`, `.planning/ROADMAP.md`, and
`docs/devlog.md` before removal:

- **`clinical` DB connection blocker** — resolved; the app is functional (host
  PG, `app`/`clinical`/`public` schemas; full Pest suite green).
- **OncoKB response parsing** — **resolved** (devlog): `parseAndUpsertTreatments`
  with 8-level evidence mapping, resistance detection, combo-drug normalization,
  idempotent `updateOrCreate`, 12 unit tests.
- **Genomics upload / criteria endpoints** — **resolved** (Phase 3B): real
  VCF/MAF/CSV/TSV ingestion via `ProcessGenomicUploadJob` +
  `GenomicUploadIngestionService`, staged variants, match/import/annotate;
  persistent imaging criteria CRUD.
- **Real-time "out of scope"** — superseded; real-time is now a core GA
  workstream (W1) and is wired.
- **Frontend test availability** — resolved; Vitest suite present (~27 files /
  88 tests) plus the Playwright E2E suite that now gates `main`.
- **Genomics service tests** — OncoKB/ClinVar services now have unit coverage.

### Completed 2026-06-20 (this GA-hardening pass)
- **W2-T11 — PHI-access audit logging.** `LogPhiAccess` middleware records every
  patient/genomics/imaging/odyssey access (`phi.access` reads / `phi.write`
  mutations) into `user_audit_logs`; resilient (never breaks the request). The D1
  compensating control is now in place.
- **A2 — case authorization.** `CasePolicy` team-scopes case view/update/archive.
- **P2/P3/P4 — external de-identification.** MME label stripped, Beacon
  k-anonymity (configurable, default 5), Phenopacket pseudonymous subject.
- **W5-T01 / W7-T01 — AI labeling.** Imaging AI carries `data_source`/`verified`/
  `disclaimer`; LLM decision-support carries `evidence_grade=llm_advisory`.
- **W3-T01/T02/T03/T04 — observability.** Readiness probe, nginx healthcheck,
  admin Orthanc/federation/Reverb checkers, request-id correlation logging.
- **W13-T01/T03 — docs + RUO.** This rewrite; persistent in-app Research-Use-Only
  notice (app shell + login).

*The full pre-resolution detail is preserved in git history (this file's
2026-03-24 revision).*
