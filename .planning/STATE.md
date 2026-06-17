---
gsd_state_version: 1.0
milestone: v2-post-stabilization
milestone_name: Aurora Post-Stabilization Product Hardening
status: active
last_updated: "2026-06-17T23:45:00-04:00"
last_activity: "2026-06-17 -- Roadmap reconciled; Orthanc/OHIF imaging sync closed out with live verification; devlog updated."
progress:
  completed_phases: 1
  active_phase: 2
  percent: 14
---

# Project State

## Project Reference

See: `.planning/ROADMAP.md`

**Core value:** Aurora is now a feature-rich v2 clinical MDT platform. The
current milestone is product hardening: make the deployed system reliable,
demonstrable, and operationally coherent before starting another broad feature
tranche.

## Current Position

Phase: 1 complete, Phase 2 next

Current focus: static production frontend serving, then imaging productization
and interoperability adapter implementation.

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
- Verified `npx playwright test tests/imaging.spec.ts --project=chromium`
  passes: 4/4 tests.
- Wrote `.planning/quick/1-refactor-imaging-pipeline-for-re-indexed/1-SUMMARY.md`.
- Added a detailed 2026-06-17 devlog entry.

## Active Backlog

1. Static production frontend serving.
2. Imaging productization and remaining DICOM/OHIF endpoint work.
3. FHIR/OMOP adapter implementation.
4. AI decision-intelligence slice 2: ambient MDT capture and instrumentation.
5. Rare-disease follow-ons: VRS/SeqRepo/UTA, ClinGen GDV, ClinVar TSV,
   Phen2Gene/Exomiser, and real MME peer configuration.
6. First non-rare population pack, recommended: Cardiac Heart Team / TAVR.

## Known Follow-Ups

- Twenty-four Orthanc studies were skipped by the sync because no patient
  mapping was available. Investigate whether those studies have blank DICOM
  PatientID values, unsupported identifiers, or should be linked to synthetic
  records.
- Several imaging endpoints are still explicit stubs:
  `indexFromDicomweb`, `indexSeries`, `extractNlp`, `importLocalTrigger`,
  `autoLinkStudies`, `aiExtractMeasurements`, and `suggestTemplate`.
- The production frontend still needs to move from public Vite-dev serving to
  static built assets.
- `backend/app/Services/Adapters/FhirAdapter.php` and
  `backend/app/Services/Adapters/OmopAdapter.php` still throw
  `not yet implemented` exceptions.

## Session Continuity

Last updated: 2026-06-17
Branch: `v2/phase-0-scaffold`
Worktree note: unrelated untracked files may remain and should not be swept into
commits unless explicitly requested.
