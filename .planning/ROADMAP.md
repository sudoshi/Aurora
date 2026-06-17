# Roadmap: Aurora Post-Stabilization Product Hardening

## Status As Of 2026-06-17

Aurora's original stabilization and verification milestone is complete. The
current branch, `v2/phase-0-scaffold`, has moved beyond the March 2026
genomics/test-hardening roadmap and now contains the major v2 platform slices:

- Stabilization and verification across backend, frontend, AI, and E2E tests.
- Authentik OIDC production login with Sanctum fallback.
- Rare-disease diagnostic odyssey foundation, worklist, HPO capture, and
  Phenopackets v2 import/export.
- ACMG/AMP points engine with evidence criteria, provisional classification,
  and human confirmation.
- Variant canonical identity and ClinVar/ClinGen reanalysis alert surfaces.
- Matchmaker Exchange inbound/outbound support and public Beacon v2 endpoints.
- Evidence-grounded Abby decision draft and AI-attribution capture.
- Board-template engine fields, case-template binding, dynamic case form data,
  and template-driven case state.
- Orthanc/OHIF imaging pipeline re-synced into Aurora.
- Laravel 12 security baseline and frontend dependency advisories cleared.

The old "Aurora Stabilization & Verification" roadmap is retained in
`.planning/STATE.md` as historical context, but it is no longer the active
development sequence.

## Current Product Position

Aurora is now feature-rich but unevenly productized. The next work should make
the platform reliable, demonstrable, and operationally coherent before adding
another broad capability tranche.

## Active Roadmap

### Phase 1: Roadmap, Devlog, And Imaging Closeout

**Status:** Complete on 2026-06-17.

**Goal:** Reconcile stale planning state and close the Orthanc/OHIF indexing
loop with live evidence.

**Completed tasks:**
- Replaced the stale roadmap with this post-stabilization roadmap.
- Updated `.planning/STATE.md` to reflect the current milestone and next
  backlog.
- Re-synced Orthanc into Aurora: 2,208 indexed Orthanc studies inserted, 1,761
  TCIA patient records created, and 24 Orthanc studies left unlinked because no
  patient mapping was available.
- Verified `/orthanc/statistics`, `/orthanc/dicom-web/studies`, authenticated
  `/api/imaging/studies`, and the imaging browser E2E smoke.
- Wrote `.planning/quick/1-refactor-imaging-pipeline-for-re-indexed/1-SUMMARY.md`.
- Added a detailed `docs/devlog.md` entry.

### Phase 2: Static Production Frontend Serving

**Goal:** Stop serving the public production frontend through the Docker Vite
development server.

**Tasks:**
- Update nginx/compose/deploy behavior so production serves `frontend/dist`
  static assets.
- Preserve Vite HMR only for local development.
- Verify `https://aurora.acumenus.net` serves built assets without `@vite/client`.
- Add deployment checks that catch a public Vite-dev leak.

### Phase 3: Imaging Productization

**Goal:** Turn the now-indexed Orthanc study corpus into a dependable clinical
imaging workflow.

**Tasks:**
- Investigate the 24 Orthanc studies skipped by the sync because no patient
  mapping was available.
- Implement or retire stubbed imaging endpoints:
  `indexFromDicomweb`, `indexSeries`, `extractNlp`, `importLocalTrigger`,
  `autoLinkStudies`, `aiExtractMeasurements`, and `suggestTemplate`.
- Add authenticated API tests for indexed study metadata and OHIF WADO-RS
  values.
- Add a browser smoke that opens a real indexed study detail page and verifies
  the OHIF iframe URL includes the StudyInstanceUID.
- Decide whether DICOMweb indexing should be a UI action, scheduled job, or
  one-way ops script.

### Phase 4: Interoperability Spine

**Goal:** Replace placeholder clinical-data adapters with working, testable
FHIR/OMOP flows.

**Tasks:**
- Implement `FhirAdapter` read paths for patient, conditions, medications,
  procedures, measurements, observations, notes, imaging studies, genomic
  variants, and cases.
- Implement `OmopAdapter` read paths or clearly scope it to the actual local
  OMOP schema available in Aurora.
- Add contract tests for adapter output shapes used by Abby, decision drafting,
  patient profile, and cohort tools.
- Define the first outbound emit path: Phenopackets, FHIR Genomics, mCODE, or
  FHIR Task/CarePlan.

### Phase 5: AI Decision Intelligence Slice 2

**Goal:** Move beyond structured case-summary drafting into live MDT decision
capture.

**Tasks:**
- Add ambient discussion transcript ingestion with local audio-first defaults.
- Convert transcript segments into draft decisions, dissent points, follow-ups,
  and task suggestions.
- Extend AI attribution with review time, edit distance, accepted/rejected
  evidence, and decision-quality instrumentation.
- Add UI affordances that force evidence review before finalizing an AI-drafted
  decision.

### Phase 6: Rare-Disease Follow-Ons

**Goal:** Deepen the rare-disease lead vertical after the core GA4GH and ACMG
surfaces are stable.

**Tasks:**
- Provision VRS/SeqRepo/UTA or formally defer computed VRS IDs behind CAID.
- Add ClinGen Gene-Disease Validity as a second KB-change alert source.
- Upgrade ClinVar ingestion to `variant_summary.txt.gz` if
  `DateLastEvaluated` becomes required.
- Add Phen2Gene or Exomiser prioritization behind a process-isolated boundary.
- Configure real MME peers and document privacy/consent controls.

### Phase 7: First Non-Rare Population Pack

**Goal:** Use the board-template engine for a focused complex-care expansion.

**Recommended beachhead:** Cardiac Heart Team / TAVR.

**Tasks:**
- Define the TAVR case template, candidacy rubric, agenda, and state machine.
- Add computable risk-score inputs and placeholders for RCRI, frailty, and
  pulmonary risk.
- Add a structured decision schema for candidacy, optimization, and procedural
  planning.
- Reuse imaging and task infrastructure for episode-of-care follow-through.

## Operating Rules

- Keep commits scoped and use explicit path staging; the worktree can contain
  unrelated local planning files.
- Verify public host behavior for production-facing changes.
- Prefer live API and DB evidence over stale documentation.
- Add devlog entries for every completed roadmap tranche.
