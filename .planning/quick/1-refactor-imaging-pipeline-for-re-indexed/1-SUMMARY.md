# Summary: Refactor Imaging Pipeline For Re-Indexed Orthanc Data

Date: 2026-06-17
Branch: `v2/phase-0-scaffold`

## Objective

Make Orthanc's re-indexed DICOM study corpus visible to Aurora as indexed
imaging data and verify that the Medical Imaging UI can load indexed study
rows backed by `/orthanc/dicom-web`.

## Completed Tasks

1. Verified nginx can authenticate to Orthanc.
   - `GET http://localhost:8085/orthanc/statistics` returned HTTP 200.
   - Orthanc reported 2,232 studies, 1,762 patients, 8,077 series, and
     546,462 instances.

2. Verified DICOMweb is reachable through the nginx proxy.
   - `GET http://localhost:8085/orthanc/dicom-web/studies` returned HTTP 200.
   - The response size was 2,427,848 bytes.

3. Verified Aurora was not yet linked to the re-indexed Orthanc corpus.
   - Before sync, `clinical.imaging_studies` had zero rows with
     `dicom_endpoint='orthanc'`.
   - Existing rows were only `NULL/synthetic` and `NULL/golden_cohort`.

4. Prepared the local Python sync environment.
   - Created a temporary venv at `/tmp/aurora-sync-venv`.
   - Installed `psycopg2-binary` in that venv because the host Python is
     externally managed and cannot accept direct `pip --user` installs.
   - Verified PostgreSQL socket access to database `aurora` as user
     `smudoshi`.

5. Ran the Orthanc-to-Aurora sync.
   - Command:
     `/tmp/aurora-sync-venv/bin/python dicom/sync_orthanc_to_aurora.py --auto-create-patients`
   - Fetched 2,232 studies from Orthanc.
   - Created 1,761 new TCIA patient records.
   - Inserted 2,208 imaging studies with `dicom_endpoint='orthanc'`.
   - Skipped 24 studies because no patient mapping was available.

6. Verified the synced Aurora database state.
   - `orthanc/tcia`: 2,208 rows.
   - `NULL/synthetic`: 104 rows.
   - `NULL/golden_cohort`: 65 rows.
   - Total imaging studies visible through the authenticated API: 2,377.

7. Verified authenticated imaging API output.
   - A temporary Sanctum token was created for the admin user, used for one
     probe, then deleted immediately.
   - `GET https://aurora.acumenus.net/api/imaging/studies?per_page=2` returned
     `success=true`.
   - Sample rows returned `status=indexed`, `dicom_endpoint=orthanc`, and
     `wadors_uri=/orthanc/dicom-web`.

8. Hardened E2E authentication/navigation helpers for the current UI.
   - `loginAsAdmin` now avoids repeated login submissions when Playwright
     storage state already contains a persisted Aurora auth token.
   - `loginAsAdmin` now clicks the exact local `Sign In` button instead of also
     matching `Login with Authentik`.
   - `navigateTo` now supports dropdown-based top navigation groups such as
     Clinical, Intelligence, and Admin.

9. Updated the imaging Playwright smoke test to match the current UI.
   - The spec now loads `/imaging` directly after auth.
   - It verifies the `Medical Imaging` heading, the `Studies` tab, the visible
     study table, `indexed` study rows, and the stat-card labels.

10. Verified the browser-level imaging smoke.
    - Command:
      `npx playwright test tests/imaging.spec.ts --project=chromium`
    - Result: 4 passed.

11. Updated planning and devlog documentation.
    - Replaced stale `.planning/ROADMAP.md` content with the active
      post-stabilization roadmap.
    - Replaced `.planning/STATE.md` with current milestone state.
    - Added a detailed `docs/devlog.md` entry.
    - Updated the Orthanc sync script environment comment so it no longer
      documents the old `orthanc_secret` default.

## Verification Commands

```bash
curl -s -o /tmp/orthanc_stats.json -w '%{http_code}' \
  http://localhost:8085/orthanc/statistics

curl -s -o /tmp/orthanc_dicomweb.json -w '%{http_code} %{size_download}\n' \
  http://localhost:8085/orthanc/dicom-web/studies

docker compose exec -T php php artisan tinker --execute='foreach (DB::select("SELECT coalesce(dicom_endpoint, '\''NULL'\'') as dicom_endpoint, coalesce(source_type, '\''NULL'\'') as source_type, count(*) as count FROM clinical.imaging_studies GROUP BY dicom_endpoint, source_type ORDER BY count(*) DESC") as $row) { echo $row->dicom_endpoint."\t".$row->source_type."\t".$row->count.PHP_EOL; }'

npx playwright test tests/imaging.spec.ts --project=chromium
```

## Remaining Follow-Ups

- Investigate the 24 skipped Orthanc studies and decide whether to link,
  ignore, or quarantine them.
- Implement or intentionally retire the remaining imaging stubs:
  `indexFromDicomweb`, `indexSeries`, `extractNlp`, `importLocalTrigger`,
  `autoLinkStudies`, `aiExtractMeasurements`, and `suggestTemplate`.
- Add an OHIF study-detail smoke that opens a specific indexed study and
  asserts the iframe URL carries the correct `StudyInstanceUIDs` parameter.
- Move the production frontend from public Vite-dev serving to static built
  assets.
