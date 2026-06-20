# Imaging Ingestion Policy

Last updated: 2026-06-19

This note records the current policy for unfinished imaging ingestion questions:
blank DICOM PatientID studies, DICOMweb bulk indexing, local DICOM import, and
auto-linking.

## Source Guidance

- DICOM PS3.3 defines the Patient Identification Module as the patient identity
  attribute set for DICOM objects, including Patient ID `(0010,0020)`.
- DICOMweb QIDO-RS is the standards-based search path for studies, series, and
  instances. Use QIDO-RS to discover identifiers before retrieval or metadata
  hydration.
- Orthanc exposes DICOMweb through its DICOMweb plugin, which should remain the
  integration boundary for DICOMweb indexing.
- Laravel queued jobs are the correct application boundary for long-running
  imports. Bulk imports should not run as synchronous HTTP request work.

References:

- https://dicom.nema.org/medical/Dicom/2021d/output/chtml/part03/sect_C.2.2.html
- https://www.dicomstandard.org/using/dicomweb/query-qido-rs
- https://dicom.nema.org/medical/dicom/2019a/output/chtml/part18/sect_6.7.html
- https://orthanc.uclouvain.be/book/plugins/dicomweb.html
- https://laravel.com/docs/13.x/queues

## Blank PatientID Studies

Policy: quarantine by default.

The 24 known blank-DICOM-PatientID MR studies must not be auto-linked or
auto-created as synthetic research patients during normal ingestion. A blank
PatientID is insufficient identity evidence for automatic linkage, even when
StudyInstanceUID exists.

Allowed handling:

- Keep the studies out of `clinical.imaging_studies` unless an approved manual
  mapping is supplied.
- Use the skipped-study CSV from `dicom/sync_orthanc_to_aurora.py` as the review
  worklist.
- Manually link only when a reviewer has independent identity evidence, such as
  source-system accession context or another governed mapping table.
- Record reviewer, mapping source, and timestamp when manual linkage support is
  implemented.

Disallowed handling:

- Do not create synthetic patient records merely to make the study visible.
- Do not fuzzy-match blank-PatientID studies by dates, descriptions, modality,
  or body part alone.
- Do not let auto-link jobs link blank-PatientID studies.

## DICOMweb Bulk Indexing

Policy: queued idempotent ingestion, not synchronous UI work.

`POST /api/imaging/studies/index-from-dicomweb` should become a job dispatch
endpoint that returns an accepted/import-run response. The job should:

- Query Orthanc DICOMweb with QIDO-RS for studies, optionally filtered by
  modality, date range, PatientID, or accession number.
- Hydrate required study and series identifiers before writing local rows.
- Upsert by StudyInstanceUID and SeriesInstanceUID.
- Track indexed, updated, skipped, and errored counts.
- Capture skipped rows with reason codes, including blank PatientID and missing
  StudyInstanceUID.
- Avoid duplicate concurrent jobs for the same source/filter set.

The current per-study Orthanc series indexer can remain a direct authenticated
action because it operates on one known study and has bounded runtime.

## Local DICOM Import

Policy: allowlisted queued import only.

`POST /api/imaging/import-local/trigger` should not shell out against arbitrary
request-provided paths. Before enabling it:

- Define allowlisted import roots in configuration.
- Dispatch a queue job with an import-run record and status/progress fields.
- Reject paths outside the configured roots.
- Capture file-level skip/error reasons without exposing server paths in normal
  user-facing payloads.
- Reuse the same idempotent upsert and quarantine policy as DICOMweb indexing.

## Auto-Linking

Policy: deterministic identifiers only.

Auto-linking may link a study only when the DICOM patient identifier, issuer, or
another governed source identifier matches `clinical.patient_identifiers`
deterministically. If that evidence is absent or blank, the study remains
unlinked or quarantined for manual review.
