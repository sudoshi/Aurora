---
gsd_state_version: 1.0
milestone: v2-post-stabilization
milestone_name: Aurora Post-Stabilization Product Hardening
status: active
last_updated: "2026-06-19T00:00:00-04:00"
last_activity: "2026-06-19 -- Added the first outbound interoperability emit path: FHIR Genomics report export as a FHIR R4 Bundle."
progress:
  completed_phases: 2
  active_phase: 3
  percent: 47
---

# Project State

## Project Reference

See: `.planning/ROADMAP.md`

**Core value:** Aurora is now a feature-rich v2 clinical MDT platform. The
current milestone is product hardening: make the deployed system reliable,
demonstrable, and operationally coherent before starting another broad feature
tranche.

## Current Position

Phase: 2 complete, Phase 3 in progress

Current focus: downstream standards-backed contract coverage and inbound FHIR
Genomics parsing, with imaging refactor/performance hardening as the next
cleanup lane.

## Completed In This Tranche

- Reconciled `.planning/ROADMAP.md` away from the stale March stabilization
  checklist and into the current post-stabilization product roadmap.
- Replaced this state file with current milestone state.
- Verified Orthanc proxy health through nginx:
  - `/orthanc/statistics` returned HTTP 200.
  - `/orthanc/dicom-web/studies` returned HTTP 200 with a 2.4 MB DICOMweb
    response.
- Ran `dicom/sync_orthanc_to_aurora.py --auto-create-patients` through a
  temporary `/tmp/aurora-sync-venv` containing `psycopg2-binary`.
- Inserted 2,208 Orthanc-backed imaging studies into
  `clinical.imaging_studies`.
- Created 1,761 TCIA patient records and identifier mappings.
- Verified Aurora now reports:
  - `orthanc/tcia`: 2,208 imaging studies.
  - total imaging studies through the authenticated API: 2,377.
  - indexed study rows include `dicom_endpoint=orthanc` and
    `wadors_uri=/orthanc/dicom-web`.
- Fixed E2E helpers for the current Authentik-enabled UI and dropdown-based
  top navigation.
- Updated the imaging E2E smoke to assert the current Medical Imaging page
  structure.
- Verified both the imaging smoke target and, later in this tranche, the full
  public Chromium E2E target against `https://aurora.acumenus.net`.
- Wrote `.planning/quick/1-refactor-imaging-pipeline-for-re-indexed/1-SUMMARY.md`.
- Added a detailed 2026-06-17 devlog entry.
- Moved the default production nginx path from Vite dev proxying to static
  `backend/public/build` serving.
- Added an explicit `docker-compose.dev.yml` and `docker/nginx/dev.conf` path
  for local Vite HMR.
- Updated `deploy.sh` so production uses `docker-compose.yml`, stops a leftover
  `aurora-node` dev container, and fails deployment if the served root or SPA
  fallback contains Vite dev markers or lacks `/build/assets` references.
- Added a `--skipped-report` CSV option to
  `dicom/sync_orthanc_to_aurora.py`.
- Ran the Orthanc sync in dry-run report mode and confirmed the 24 skipped
  Orthanc studies are all blank-DICOM-PatientID MR studies; none are missing
  StudyInstanceUID values.
- Implemented `POST /api/imaging/studies/{id}/index-series` against Orthanc:
  StudyInstanceUID lookup, Orthanc series metadata fetch, and
  `clinical.imaging_series` upserts.
- Added Orthanc service configuration keys to environment examples.
- Normalized imaging API/frontend contracts for `body_part`,
  `body_part_examined`, series UID/description aliases, and paginated metadata.
- Added backend feature coverage for indexed study list/detail payloads and
  Orthanc-backed series indexing.
- Added a Playwright imaging smoke that opens a real indexed production study
  detail page and verifies the OHIF iframe URL carries the expected
  `StudyInstanceUIDs` parameter.
- Implemented persistent imaging criteria list/create/delete and wired visible
  criteria creation into the imaging UI.
- Implemented persistent manual and computed imaging response assessments with
  frontend-compatible response payloads.
- Returned frontend-compatible population analytics arrays while preserving
  legacy distribution maps.
- Retired frontend green-success behavior for deferred imaging actions, so
  DICOMweb indexing, local DICOM import, auto-link, NLP extraction, AI
  measurement extraction, and template suggestion controls no longer report
  false completion.
- Normalized imaging measurement and patient timeline payloads so frontend
  measurement fields round-trip and the timeline endpoint returns person,
  studies, drug exposures, measurements, and summary data.
- Documented the imaging ingestion policy in `docs/imaging-ingestion-policy.md`:
  blank-DICOM-PatientID studies are quarantined/manual-review only; DICOMweb
  bulk indexing and local import should be queued, idempotent jobs; auto-linking
  requires deterministic identifiers.
- Revalidated frontend typecheck/tests/build and backend Pest on the current
  worktree.
- Hardened the public E2E auth setup so it reuses a validated stored Sanctum
  token before attempting another form login.
- Replaced the raw IP-wide API throttle with authenticated `throttle:api`
  middleware on protected routes; authenticated requests are now keyed by user
  ID and guests remain IP-limited.
- Cleared live Laravel route/config/application caches, restarted PHP/nginx,
  and verified authenticated public API calls returned HTTP 200 instead of
  stale HTTP 429 responses.
- Verified the full live Chromium E2E target:
  `npx playwright test --project=chromium` passed 31 tests with 2
  data-dependent genomics skips against `https://aurora.acumenus.net`.
- Added `clinical.imaging_ingestion_runs` with active-run idempotency,
  queueable DICOMweb/local import job dispatch, run status/counters, and
  pollable API payloads.
- Implemented queued DICOMweb indexing with deterministic DICOM PatientID
  matching against patient identifiers/MRN, blank-PatientID quarantine skips,
  and study/series upserts.
- Implemented guarded local DICOM import triggering with configured allowlisted
  roots and import command checks.
- Added `clinical.imaging_features` and persisted AI/NLP feature extraction
  outputs.
- Implemented AI volumetric measurement persistence and deterministic
  measurement-template suggestions.
- Changed auto-link from no-op success to an explicit policy error until a
  staged unlinked-study table exists.
- Enabled the imaging UI controls for queued DICOMweb indexing, local import,
  NLP extraction, and AI measurement extraction, and removed the stale auto-link
  prompt.
- Verified backend formatting, focused imaging tests, full backend Pest,
  frontend typecheck, frontend Vitest, and frontend production build.
- Added staged genomics upload variants with parse/match/import/ClinVar
  metadata on upload records.
- Implemented queued VCF/MAF/CSV/TSV parsing for genomic uploads with
  multi-ALT VCF handling, duplicate suppression, malformed-file failure states,
  and deterministic sample identifier/MRN matching.
- Replaced upload-level genomics no-op actions:
  `matchPersons`, `importToOmop`, and `annotateClinVar` now return explicit
  operation payloads and non-2xx errors for unavailable work.
- Implemented idempotent import of matched staged rows into
  `clinical.genomic_variants`.
- Implemented upload-scoped ClinVar annotation against the local ClinVar cache
  and explicit HTTP 503 behavior when the cache is empty.
- Updated genomics upload/detail UI actions so match/import/annotation surface
  real counts and backend errors.
- Verified backend formatting, focused genomics tests, and frontend typecheck.
- Implemented standards-aware `FhirAdapter` read paths over Aurora's local
  clinical schema for patient demographics, conditions, medications,
  procedures, measurements, observations, visits, notes, imaging studies, and
  genomic variants.
- Implemented standards-aware `OmopAdapter` read paths over Aurora's local
  clinical schema, projecting records to the nearest OMOP CDM v5.4 table/source
  fields available without a separate CDM schema.
- Added `CLINICAL_DATA_ADAPTER` / `config/clinical.php` selection so
  `PatientService` can intentionally run with `manual`, `fhir`, or `omop`.
- Added focused adapter implementation tests covering full profile/search,
  notes, imaging, genomics, and configured adapter selection.
- Verified adapter-adjacent unit tests, full backend Pest, frontend typecheck,
  frontend Vitest, frontend production build, and `git diff --check`.
- Selected FHIR Genomics as Aurora's first outbound interoperability emit path.
- Added `FhirGenomicsReportExporter`, which emits a FHIR R4 collection Bundle
  containing `Patient`, Genomic Report `DiagnosticReport`, and variant
  `Observation` resources with HL7 Genomics Reporting profile URLs.
- Added protected `GET /api/genomics/patients/{patient}/fhir-report` with
  response metadata identifying the export as local FHIR R4 Genomics Reporting.
- Verified focused FHIR Genomics exporter/genomics API tests, full backend Pest,
  frontend typecheck, frontend Vitest, frontend production build, and
  `git diff --check`.

## Active Backlog

1. Downstream standards-backed contract coverage for Abby, decision drafting,
   and cohort tools.
2. Inbound FHIR Genomics upload parsing for supported report bundles.
3. Imaging controller/service refactor and study-list performance hardening.
4. AI decision-intelligence slice 2: ambient MDT capture and instrumentation.
5. Rare-disease follow-ons: VRS/SeqRepo/UTA, ClinGen GDV, ClinVar TSV,
   Phen2Gene/Exomiser, and real MME peer configuration.
6. First non-rare population pack, recommended: Cardiac Heart Team / TAVR.

## Known Follow-Ups

- Twenty-four Orthanc studies were skipped because their DICOM PatientID values
  are blank. Policy now requires quarantine/manual review; synthetic patient
  creation and fuzzy auto-linking are disallowed.
- The FHIR/OMOP adapters are standards-aware local projections, not external
  FHIR-server or physical OMOP-CDM connections.
- FHIR Genomics upload files currently fail fast during parsing until inbound
  parsing is implemented for supported report Bundle, DiagnosticReport, and
  variant Observation payloads.

## Session Continuity

Last updated: 2026-06-19
Branch: `v2/phase-0-scaffold`
Worktree note: unrelated untracked files may remain and should not be swept into
commits unless explicitly requested.
