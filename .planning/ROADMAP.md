# Roadmap: Aurora Post-Stabilization Product Hardening

## Status As Of 2026-06-19

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
`.planning/PROJECT.md` as historical context, but it is no longer the active
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

**Status:** Complete on 2026-06-18.

**Goal:** Stop serving the public production frontend through the Docker Vite
development server.

**Completed tasks:**
- Updated the default compose/nginx path so production serves the built
  frontend from `backend/public/build`.
- Moved Vite HMR behind an explicit development compose/nginx path.
- Updated deploy behavior so production uses the default production compose
  file, stops any leftover dev Vite container, and fails if served HTML contains
  Vite dev markers.
- Verified static SPA fallback and built asset references locally before
  deployment.

### Phase 3: Imaging Productization

**Status:** Endpoint closeout complete on 2026-06-19; refactor/performance and
segmentation productization remain.

**Goal:** Turn the now-indexed Orthanc study corpus into a dependable clinical
imaging workflow.

**Completed so far:**
- Added skipped-study reporting to the Orthanc sync script and confirmed the
  24 skipped studies all have blank DICOM PatientID values; none are missing
  StudyInstanceUID values.
- Implemented Orthanc-backed `indexSeries` for single-study series metadata
  upserts into `clinical.imaging_series`.
- Normalized imaging API response aliases for current Orthanc fields and legacy
  frontend field names.
- Added authenticated backend coverage for indexed Orthanc metadata, OHIF
  WADO-RS fields, normalized series fields, and the Orthanc series indexer.
- Added a browser smoke that opens a live indexed study detail page and verifies
  the OHIF iframe URL includes the `StudyInstanceUIDs` parameter.
- Implemented persistent imaging criteria list/create/delete and wired the
  visible imaging criteria creation flow to the backend.
- Implemented durable manual and computed imaging response assessments,
  including stored history retrieval and frontend-compatible fallback payloads.
- Returned frontend-compatible population analytics arrays while retaining the
  existing distribution-map response keys.
- Replaced green success states for deferred imaging actions with neutral
  disabled/pending UI, so stubbed DICOMweb indexing, local import, auto-link,
  NLP extraction, AI extraction, and template suggestion actions no longer
  present as completed work.
- Normalized imaging measurement and patient timeline contracts so frontend
  measurement fields and longitudinal timeline data round-trip from the backend.
- Documented `docs/imaging-ingestion-policy.md`, which settles blank-PatientID
  handling and defines queued DICOMweb/local import and deterministic auto-link
  boundaries.
- Hardened protected API rate limiting so authenticated SPA/E2E traffic is
  keyed by user ID through `throttle:api`, while guests retain IP-based limits.
- Hardened Playwright auth setup to reuse validated stored Sanctum state before
  calling the public login endpoint.
- Verified the full public Chromium E2E target:
  `npx playwright test --project=chromium` passed 31 tests with 2
  data-dependent genomics skips against `https://aurora.acumenus.net`.
- Added auditable imaging ingestion runs and queue jobs for DICOMweb indexing
  and guarded local import.
- Implemented queued DICOMweb indexing with deterministic DICOM PatientID
  matching, blank-PatientID quarantine skips, and idempotent study/series
  upserts.
- Added persisted imaging feature storage and wired NLP extraction,
  `/imaging/features`, imaging stats, and population top-feature analytics to
  real data.
- Implemented AI volumetric measurement extraction into
  `clinical.imaging_measurements`.
- Implemented deterministic measurement-template suggestions.
- Changed auto-link from no-op success to an explicit policy error.
- Enabled frontend controls for queued DICOMweb indexing, local import, NLP
  extraction, and AI measurement extraction.

**Tasks:**
- Split `ImagingController` into narrower controllers/services now that the
  behavior is covered.
- Replace per-row study list count queries with eager-loaded counts and add
  query-count/performance coverage.
- Productize real segmentation execution and DICOM SEG/RTSTRUCT persistence.

### Phase 3B: Genomics Upload Productization

**Status:** Complete on 2026-06-19 for VCF/MAF/CSV/TSV upload processing;
FHIR Genomics remains part of Phase 4 interoperability.

**Goal:** Replace upload-level genomics false-success actions with a real,
auditable variant ingestion path.

**Completed tasks:**
- Added `clinical.genomic_upload_variants` for staged parsed variants that can
  exist before patient matching.
- Added upload metadata for parse, match, import, ClinVar annotation, errors,
  and last operation results.
- Added `ProcessGenomicUploadJob` and `GenomicUploadIngestionService`.
- Implemented streaming VCF and delimited MAF/CSV/TSV parsing, multi-ALT VCF
  splitting, duplicate suppression, malformed-file failure states, and
  deterministic sample identifier/MRN matching.
- Implemented real upload-level `matchPersons`, `importToOmop`, and
  `annotateClinVar` behavior with explicit operation payloads and non-2xx
  responses for unavailable work.
- Imported fully matched staged variants idempotently into
  `clinical.genomic_variants`.
- Updated frontend upload/detail actions so match/import/annotation display
  real counts and backend errors.
- Verified focused genomics API coverage and frontend typecheck.

### Phase 4: Interoperability Spine

**Status:** Local adapter read projections and first outbound FHIR Genomics
report export complete on 2026-06-19; inbound FHIR Genomics parsing and
downstream standards-backed consumers remain.

**Goal:** Replace placeholder clinical-data adapters with working, testable
FHIR/OMOP flows.

**Completed so far:**
- Implemented `FhirAdapter` read paths for patient, conditions, medications,
  procedures, measurements, observations, visits, notes, imaging studies, and
  genomic variants as FHIR R4-style local projections.
- Implemented `OmopAdapter` read paths over Aurora's actual local clinical
  schema, projecting to OMOP CDM v5.4 table/source fields without pretending a
  separate physical CDM schema exists.
- Added `CLINICAL_DATA_ADAPTER` / `config/clinical.php` adapter selection for
  `manual`, `fhir`, and `omop`.
- Added focused adapter contract coverage for patient profile/search, notes,
  imaging, genomics, and configured `PatientService` selection.
- Selected FHIR Genomics as the first outbound emit path and added
  `GET /api/genomics/patients/{patient}/fhir-report`, returning a FHIR R4
  Bundle with `Patient`, Genomic Report `DiagnosticReport`, and variant
  `Observation` resources.

**Tasks:**
- Extend standards-backed contract coverage to Abby, decision drafting, and
  cohort tools when their first concrete interoperability workflow is selected.
- Implement inbound FHIR Genomics parsing for supported
  `Bundle`/`DiagnosticReport`/variant `Observation` payloads.

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
