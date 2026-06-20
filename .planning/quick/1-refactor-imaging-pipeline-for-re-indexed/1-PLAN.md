---
phase: quick-1-refactor-imaging
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - docker/nginx/default.conf
  - backend/app/Http/Controllers/ImagingController.php
  - dicom/sync_orthanc_to_aurora.py
autonomous: true
must_haves:
  truths:
    - "Nginx proxy authenticates to Orthanc with the correct password"
    - "All 2036 Orthanc studies have dicom_endpoint='orthanc' in Aurora DB so they show as 'indexed' in the UI"
    - "Patient-to-study linking is correct for all synced studies"
    - "OHIF viewer loads and displays DICOM images for any indexed study"
    - "PatientImagingTab shows the correct studies for each patient"
  artifacts:
    - path: "docker/nginx/default.conf"
      provides: "Correct Orthanc proxy auth header"
      contains: "proxy_pass http://host.docker.internal:8042"
    - path: "backend/app/Http/Controllers/ImagingController.php"
      provides: "formatStudy correctly identifies all orthanc-synced studies as indexed"
    - path: "dicom/sync_orthanc_to_aurora.py"
      provides: "Updated sync script that sets dicom_endpoint='orthanc' consistently"
  key_links:
    - from: "docker/nginx/default.conf"
      to: "Orthanc at localhost:8042"
      via: "nginx proxy with Basic auth"
      pattern: "proxy_set_header Authorization"
    - from: "frontend OhifViewer"
      to: "/orthanc/dicom-web"
      via: "nginx proxy -> Orthanc DICOMweb"
      pattern: "wadors_uri.*orthanc/dicom-web"
    - from: "ImagingController formatStudy"
      to: "imaging_studies.dicom_endpoint"
      via: "status determination logic"
      pattern: "dicom_endpoint.*orthanc"
---

<objective>
Fix the imaging pipeline so that Orthanc's re-indexed DICOM data (2036 studies, 484K instances on NVMe RAID0) displays correctly in Aurora.

Three issues to fix:
1. **Nginx proxy auth is wrong** -- uses old password `orthanc_secret` but Orthanc now requires `GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih` (from Parthenon .env). This breaks OHIF viewer and all DICOMweb requests.
2. **Most studies show as "pending" not "indexed"** -- Only 70 of 2320 studies have `dicom_endpoint='orthanc'`. The other 2185 TCIA studies have file:// paths as their endpoint. The `formatStudy()` method only marks studies as "indexed" if `source_type='orthanc'` OR `dicom_endpoint='orthanc'`, missing all the `dicom_endpoint='file://...'` + `source_type='tcia'` studies that ARE in Orthanc now.
3. **Sync script needs re-run** -- The `sync_orthanc_to_aurora.py` script sets `dicom_endpoint='orthanc'` on upsert, but the old file:// records were never updated after re-indexing into Orthanc.

Purpose: Make the entire TCIA imaging collection viewable through OHIF in Aurora.
Output: Working imaging viewer with correct patient-study links for all 2036 Orthanc studies.
</objective>

<execution_context>
@/home/smudoshi/.claude/get-shit-done/workflows/execute-plan.md
@/home/smudoshi/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@docker/nginx/default.conf
@backend/app/Http/Controllers/ImagingController.php
@dicom/sync_orthanc_to_aurora.py
@frontend/src/features/imaging/components/OhifViewer.tsx
@frontend/src/features/imaging/pages/ImagingStudyPage.tsx

Current state (from investigation):
- Orthanc running on localhost:8042 with user `parthenon` / password from Parthenon .env
- Orthanc has 2036 studies, 1651 patients, 484372 instances (214GB on NVMe RAID0)
- Aurora DB has 2320 imaging_studies rows, but only 70 have dicom_endpoint='orthanc'
- 2185 rows have file:// paths as dicom_endpoint (from original TCIA import before Orthanc re-index)
- Nginx proxy at /orthanc/ uses wrong Basic auth (401 on all requests)
- The old Basic auth header `cGFydGhlbm9uOm9ydGhhbmNfc2VjcmV0` decodes to `parthenon:orthanc_secret` (wrong)
- Correct password is in Parthenon's .env: `GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih`

DB breakdown:
- `file:///media/smudoshi/DATA/TCIA-downloads/*` + `tcia`: 2185 rows (the bulk)
- `orthanc` + `tcia`: 19 rows
- `/dicom-web` + `orthanc`: 51 rows
- `orthanc` + `golden_cohort`: 1 row
- null + `golden_cohort`: 64 rows
</context>

<tasks>

<task type="auto">
  <name>Task 1: Fix nginx Orthanc proxy auth and update formatStudy logic</name>
  <files>docker/nginx/default.conf, backend/app/Http/Controllers/ImagingController.php, backend/.env</files>
  <action>
1. **Fix nginx proxy auth**: In `docker/nginx/default.conf`, the `/orthanc/` location block has a hardcoded Basic auth header. Replace the old base64 value with the correct one. The correct credentials are `parthenon:GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih`. Generate the base64: `echo -n 'parthenon:GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih' | base64`. Replace the `proxy_set_header Authorization` line with the new value.

   IMPORTANT: Do NOT hardcode the password in nginx config long-term. For now, update the base64 header to get things working. Add a comment noting this should eventually use env substitution.

2. **Fix formatStudy status logic**: In `ImagingController.php`, the `formatStudy()` method (line 20) determines `$isIndexed` as:
   ```php
   $isIndexed = $study->source_type === 'orthanc' || $study->dicom_endpoint === 'orthanc';
   ```
   This misses studies that were synced from Orthanc (source_type='tcia', dicom_endpoint='orthanc'). After the sync script runs (Task 2), all Orthanc studies will have `dicom_endpoint='orthanc'`. But to be robust, also check for the `/dicom-web` endpoint pattern. Update to:
   ```php
   $isIndexed = $study->dicom_endpoint === 'orthanc'
       || $study->source_type === 'orthanc'
       || str_contains((string) $study->dicom_endpoint, 'dicom-web');
   ```

   Also update the `wadors_uri` assignment on line 40. Currently it returns `/orthanc/dicom-web` only when indexed. This is correct -- OHIF uses this as the WADO-RS base URL via nginx proxy. No change needed there.

3. **Add ORTHANC env vars to backend/.env** (if not already present): Add `ORTHANC_URL=http://localhost:8042`, `ORTHANC_USER=parthenon`, `ORTHANC_PASSWORD=GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih` for future use by the indexSeries/indexFromDicomweb endpoints when they are implemented. These are NOT secrets that go in source -- they go in .env only.

4. **Restart nginx**: Run `docker compose restart nginx` to pick up the new config.
  </action>
  <verify>
    <automated>curl -s -o /dev/null -w "%{http_code}" -u parthenon:GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih http://localhost:8042/statistics && echo " orthanc-direct-ok"; curl -s -o /dev/null -w "%{http_code}" http://localhost:8085/orthanc/statistics && echo " nginx-proxy-ok"</automated>
  </verify>
  <done>Nginx proxy returns 200 for /orthanc/statistics (not 401). formatStudy correctly identifies orthanc and dicom-web endpoint studies as indexed.</done>
</task>

<task type="auto">
  <name>Task 2: Re-sync Orthanc studies to Aurora DB to fix dicom_endpoint values</name>
  <files>dicom/sync_orthanc_to_aurora.py</files>
  <action>
1. **Update sync script credentials**: In `sync_orthanc_to_aurora.py`, the default `ORTHANC_PASS` on line 35 is `orthanc_secret`. Update it to read from env with the correct default: `os.environ.get("ORTHANC_PASS", "GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih")`.

   NOTE: This is a local development script, not deployed code. The password default is acceptable here (same pattern as existing ORTHANC_USER default).

2. **Run the sync script**: Execute `cd /home/smudoshi/Github/Aurora && python3 dicom/sync_orthanc_to_aurora.py --auto-create-patients`. This will:
   - Fetch all 2036 studies from Orthanc
   - Match DICOM PatientIDs to Aurora patients via patient_identifiers table
   - Auto-create any missing patient records
   - Upsert all studies with `dicom_endpoint='orthanc'` (the sync script hardcodes this in the INSERT/UPDATE SQL)
   - This converts the 2185 file:// endpoint rows to 'orthanc'

3. **Verify the sync results**: After sync, run:
   ```sql
   SELECT dicom_endpoint, count(*) FROM clinical.imaging_studies GROUP BY dicom_endpoint;
   ```
   Expected: The vast majority should now show `dicom_endpoint='orthanc'`. The 64 golden_cohort rows with null endpoint may remain unchanged (they weren't in Orthanc).
  </action>
  <verify>
    <automated>PGPASSWORD=claude321\$% psql -h localhost -U claude_dev -d aurora -p 5432 -t -c "SELECT count(*) FROM clinical.imaging_studies WHERE dicom_endpoint = 'orthanc';" | tr -d ' '</automated>
  </verify>
  <done>At least 2000 studies have dicom_endpoint='orthanc' in the DB (up from 20). Patient-study links are correct via patient_identifiers mapping.</done>
</task>

<task type="checkpoint:human-verify" gate="blocking">
  <what-built>
    Fixed the entire imaging pipeline:
    1. Nginx proxy now authenticates correctly to Orthanc (new password)
    2. All Orthanc studies synced to Aurora DB with correct dicom_endpoint='orthanc'
    3. formatStudy() correctly identifies indexed studies for OHIF viewer
  </what-built>
  <how-to-verify>
    1. Open http://localhost:8085 and log in
    2. Navigate to the Imaging section -- studies should show with "indexed" status
    3. Click on any study to open the study detail page
    4. The "View Scan" tab should load OHIF viewer and display DICOM images
    5. Go to a patient profile (e.g., one with TCIA imaging) and check the Imaging tab -- studies should list with correct modality, date, and series/image counts
    6. Click a study from the patient imaging tab -- it should navigate to the study page and show images in OHIF
  </how-to-verify>
  <resume-signal>Type "approved" or describe any issues with image display</resume-signal>
</task>

</tasks>

<verification>
- `curl http://localhost:8085/orthanc/statistics` returns 200 with JSON stats (not 401)
- `curl http://localhost:8085/orthanc/dicom-web/studies` returns 200 with DICOM JSON
- DB query shows 2000+ studies with dicom_endpoint='orthanc'
- API call `GET /api/imaging/studies` returns studies with status='indexed'
- OHIF viewer loads in iframe and displays DICOM images
</verification>

<success_criteria>
- All Orthanc studies (2036) are synced to Aurora with correct patient links
- Nginx proxy passes auth correctly to Orthanc (200, not 401)
- Studies show as "indexed" in the UI, enabling OHIF viewer
- OHIF viewer successfully loads and renders DICOM images
- Patient imaging tab shows correct studies per patient
</success_criteria>

<output>
After completion, create `.planning/quick/1-refactor-imaging-pipeline-for-re-indexed/1-SUMMARY.md`
</output>
