# Summary: Imaging Productization Tranche 1

Date: 2026-06-18
Branch: `v2/phase-0-scaffold`

## Objective

Turn the indexed Orthanc corpus into a more dependable Aurora imaging workflow
by closing the skipped-study evidence gap, replacing one stub with real Orthanc
series indexing, aligning frontend/backend contracts, and adding automated
coverage for indexed-study/OHIF behavior.

## Completed

1. Added Orthanc backend service configuration.
2. Implemented `POST /api/imaging/studies/{id}/index-series`:
   - Finds the Orthanc study by `StudyInstanceUID`.
   - Fetches Orthanc study and series metadata.
   - Upserts `clinical.imaging_series` rows by `series_uid`.
   - Updates parent study series/image counts and marks the endpoint as
     `orthanc`.
3. Normalized imaging payload aliases:
   - study `body_part` and `body_part_examined`
   - series `series_uid` and `series_instance_uid`
   - series `description` and `series_description`
   - series `num_instances` and `num_images`
4. Normalized imaging frontend pagination handling for Laravel `meta` payloads.
5. Updated imaging UI views to consume current Orthanc fields without breaking
   legacy aliases.
6. Added skipped-study CSV reporting to `dicom/sync_orthanc_to_aurora.py`.
7. Ran a dry-run skipped-study investigation:
   - Report: `/tmp/aurora_orthanc_skipped_2026-06-18.csv`
   - Orthanc studies fetched: 2,232
   - Would-upsert studies: 2,208
   - Skipped no-patient-match: 24
   - Missing Study UID: 0
   - Classification: all 24 skipped rows have blank DICOM PatientID values and
     all are MR studies, mostly cardiac/perfusion descriptions.
8. Added backend feature tests for indexed study metadata, OHIF fields,
   normalized series detail payloads, and Orthanc-backed series indexing.
9. Added a Playwright smoke that opens a real indexed study detail page and
   verifies the OHIF iframe URL carries `StudyInstanceUIDs`.
10. Restored runtime vendor/cache permissions after installing local backend dev
    dependencies for testing.

## Verification

```bash
python3 -m py_compile dicom/sync_orthanc_to_aurora.py
php -l backend/app/Http/Controllers/ImagingController.php
php -l backend/config/services.php
php -l backend/tests/Feature/Api/ImagingStudyApiTest.php
cd backend && DB_HOST=/var/run/postgresql DB_USERNAME=smudoshi DB_PASSWORD= DB_DATABASE=aurora_test DB_MIGRATIONS_TABLE=public.migrations php artisan test tests/Feature/Api/ImagingStudyApiTest.php
cd backend && ./vendor/bin/pint --test app/Http/Controllers/ImagingController.php config/services.php tests/Feature/Api/ImagingStudyApiTest.php
npm --prefix frontend run typecheck
npm --prefix frontend run build
cd e2e && npx playwright test tests/imaging.spec.ts --project=chromium
git diff --check
```

## Remaining

- Decide policy for the 24 blank-DICOM-PatientID MR studies: quarantine,
  manual link, or synthetic research records.
- Implement or retire the remaining imaging stubs:
  `indexFromDicomweb`, `extractNlp`, `importLocalTrigger`, `autoLinkStudies`,
  `aiExtractMeasurements`, and `suggestTemplate`.
- Decide whether broad DICOMweb indexing should be a UI action, scheduled job,
  or ops-only sync path.
