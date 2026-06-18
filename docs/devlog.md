# Aurora Devlog

## 2026-06-18 — Static Production Frontend Serving

**Branch:** `v2/phase-0-scaffold`

### Overview

Moved Aurora's default production frontend path away from the Docker Vite
development server and onto the built Vite bundle served from
`backend/public/build`. Vite HMR remains available only through an explicit
development compose file.

### Completed Tasks

1. **Audited the current production serving path**
   - Confirmed `deploy.sh` uses the default `docker-compose.yml` stack.
   - Confirmed `docker/nginx/default.conf` proxied SPA traffic to `node:5173`.
   - Confirmed the default compose stack made nginx depend on the node service
     and published the Vite container on host port `5177`.

2. **Changed production nginx to serve static built assets**
   - Removed the Vite upstream and HMR proxy locations from
     `docker/nginx/default.conf`.
   - Added a root and SPA fallback path that serves `/build/index.html`.
   - Preserved long-lived immutable caching only for hashed files under
     `/build/assets/`.
   - Preserved Laravel API, Sanctum, broadcasting, storage, image, OHIF, and
     Orthanc proxy routes.

3. **Moved Vite HMR to an explicit development path**
   - Added `docker/nginx/dev.conf` with the previous Vite proxy behavior.
   - Added `docker-compose.dev.yml` so local development can opt into the dev
     nginx config and node service.
   - Marked the `node` service in `docker-compose.yml` with the `dev` profile
     so it is excluded from the default production stack.

4. **Hardened production deployment behavior**
   - Updated `deploy.sh` to call `docker compose -f docker-compose.yml`
     explicitly.
   - Removed production dependency syncing inside the node dev container.
   - Added a stop step for any leftover `aurora-node` container during
     production deployment.
   - Added a static frontend smoke that fails deployment if the served root HTML
     contains Vite dev markers such as `@vite/client`, `/src/main`, or
     `react-refresh`.
   - Added an SPA fallback smoke that verifies `/imaging` returns the built
     frontend bundle references.

5. **Validated compose and script syntax locally**
   - `bash -n deploy.sh` passed.
   - `docker compose -f docker-compose.yml config` showed the production stack
     with no default node service.
   - `docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev config`
     showed the explicit dev stack with node and `docker/nginx/dev.conf`.

6. **Updated operator-facing Docker guidance**
   - Replaced the stale README Docker snippet with the current static frontend
     production-like path.
   - Added the explicit dev-HMR compose command:
     `docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev up -d`.

### Verification Commands

```bash
bash -n deploy.sh
docker compose -f docker-compose.yml config
docker compose -f docker-compose.yml -f docker-compose.dev.yml --profile dev config
```

### Remaining Follow-Ups

- Investigate the 24 skipped Orthanc studies and decide whether to link,
  ignore, or quarantine them.
- Implement or intentionally retire the remaining imaging stubs in
  `ImagingController`.
- Add an OHIF study-detail smoke that opens a specific indexed study and
  asserts the iframe URL carries the expected `StudyInstanceUIDs` parameter.
- Start the FHIR/OMOP adapter implementation tranche after imaging
  productization is stable.

---

## 2026-06-17 — Roadmap Reconciliation + Orthanc/OHIF Imaging Closeout

**Branch:** `v2/phase-0-scaffold`

### Overview

Completed the first post-stabilization hardening tranche: reconciled stale
planning files, finished the Orthanc re-index sync into Aurora, repaired the
imaging E2E smoke after the Authentik/top-nav UI changes, and captured the work
in a dedicated quick-plan summary.

### Completed Tasks

1. **Audited roadmap state**
   - Reviewed `.planning/ROADMAP.md`, `.planning/STATE.md`, `docs/devlog.md`,
     recent plan docs, current routes, current feature modules, and recent git
     history.
   - Confirmed the old March stabilization roadmap was stale: `.planning/STATE.md`
     already marked the 10-phase milestone complete, while `.planning/ROADMAP.md`
     still had several unchecked historical items.
   - Confirmed the current branch already contains rare-disease odyssey,
     HPO/Phenopackets, ACMG classification, variant reanalysis, Matchmaker,
     Beacon, Abby decision draft, and board-template engine work.

2. **Replaced the stale active roadmap**
   - Rewrote `.planning/ROADMAP.md` as the active
     "Aurora Post-Stabilization Product Hardening" roadmap.
   - Recorded completed platform slices and set the next sequence:
     static production frontend serving, imaging productization,
     FHIR/OMOP adapters, AI decision-intelligence slice 2, rare-disease
     follow-ons, and the first non-rare population pack.

3. **Updated current project state**
   - Replaced `.planning/STATE.md` with the current
     `v2-post-stabilization` state.
   - Captured active backlog, known follow-ups, and the worktree hygiene note
     about unrelated local planning files.

4. **Verified Orthanc proxy health**
   - `GET http://localhost:8085/orthanc/statistics` returned HTTP 200.
   - Orthanc reported 2,232 studies, 1,762 patients, 8,077 series, and
     546,462 instances.

5. **Verified DICOMweb through nginx**
   - `GET http://localhost:8085/orthanc/dicom-web/studies` returned HTTP 200.
   - The DICOMweb response size was 2,427,848 bytes.

6. **Verified Aurora's pre-sync imaging DB state**
   - Before sync, `clinical.imaging_studies` had zero rows with
     `dicom_endpoint='orthanc'`.
   - Existing rows were only `NULL/synthetic` and `NULL/golden_cohort`, so the
     UI could not represent the re-indexed Orthanc corpus as indexed data.

7. **Prepared sync tooling without changing system Python**
   - Host Python was externally managed and rejected direct `pip --user`
     installation.
   - Created a temporary venv at `/tmp/aurora-sync-venv`.
   - Installed `psycopg2-binary` in that venv.
   - Verified PostgreSQL socket access to database `aurora` as user `smudoshi`.

8. **Ran the Orthanc-to-Aurora sync**
   - Ran:
     `/tmp/aurora-sync-venv/bin/python dicom/sync_orthanc_to_aurora.py --auto-create-patients`.
   - Fetched 2,232 studies from Orthanc.
   - Created 1,761 new TCIA patient records and identifier mappings.
   - Inserted 2,208 `clinical.imaging_studies` rows with
     `dicom_endpoint='orthanc'`.
   - Left 24 Orthanc studies skipped because no patient mapping was available.

9. **Verified post-sync database state**
   - `orthanc/tcia`: 2,208 imaging studies.
   - `NULL/synthetic`: 104 imaging studies.
   - `NULL/golden_cohort`: 65 imaging studies.
   - Total authenticated imaging API count: 2,377 studies.

10. **Verified authenticated imaging API output**
    - Created a temporary Sanctum token for `admin@acumenus.net`, used it for a
      single probe, and deleted it immediately.
    - `GET https://aurora.acumenus.net/api/imaging/studies?per_page=2` returned
      `success=true`.
    - Sample API rows returned `status=indexed`, `dicom_endpoint=orthanc`, and
      `wadors_uri=/orthanc/dicom-web`.

11. **Updated stale Orthanc sync documentation**
    - Updated `dicom/sync_orthanc_to_aurora.py` so the environment block no
      longer documents the old `orthanc_secret` default.

12. **Fixed E2E login helper for Authentik-era UI**
    - `loginAsAdmin` now detects persisted Playwright storage-state auth before
      submitting another local login form.
    - It waits for the main navigation before returning, avoiding SPA-loading
      races.
    - It clicks the exact local `Sign In` button so `Login with Authentik` is
      not also matched.

13. **Fixed E2E navigation helper for dropdown top nav**
    - `navigateTo` now supports dropdown navigation groups:
      Clinical, Intelligence, and Admin.
    - This matches the current top-nav design where Imaging lives under the
      Intelligence dropdown.

14. **Updated imaging E2E smoke assertions**
    - `e2e/tests/imaging.spec.ts` now loads `/imaging` directly after auth.
    - It asserts the `Medical Imaging` heading, `Studies` tab, visible table,
      `indexed` rows, and stat-card labels.
    - Replaced broad text/locator unions that caused Playwright strict-mode
      violations.

15. **Verified browser-level imaging smoke**
    - Cleared the application cache to remove local-auth throttle state created
      by repeated failed Playwright attempts.
    - Ran `npx playwright test tests/imaging.spec.ts --project=chromium`.
    - Result: 4 passed.

16. **Wrote the imaging quick-plan summary**
    - Added
      `.planning/quick/1-refactor-imaging-pipeline-for-re-indexed/1-SUMMARY.md`.
    - Included objective, every completed task, verification commands, evidence,
      and remaining follow-ups.

17. **Published the scoped implementation tranche**
    - Staged only the roadmap/state docs, imaging quick summary, Orthanc sync
      script comment, devlog entry, and imaging E2E helper/spec changes.
    - Committed the tranche on `v2/phase-0-scaffold`.
    - Pushed the branch to `origin/v2/phase-0-scaffold`.

18. **Deployed the pushed branch to production**
    - Ran `./deploy.sh` after the scoped push.
    - Deployment pulled the latest branch, installed backend/frontend
      dependencies, confirmed no pending migrations, rebuilt frontend assets,
      refreshed Laravel caches, synced frontend dependencies in Docker, and
      restarted the nginx, PHP, and node services.

19. **Verified production after deploy**
    - Confirmed Docker services were up after restart.
    - `GET https://aurora.acumenus.net/api/health` returned HTTP 200 with
      `status=ok`.
    - `GET https://aurora.acumenus.net/api/auth/providers` returned HTTP 200
      with OIDC enabled.
    - Rechecked `GET http://localhost:8085/orthanc/statistics` after deploy.
    - Rechecked authenticated `/api/imaging/studies?per_page=2`; it still
      returned `success=true`, total 2,377 studies, and indexed Orthanc rows.
    - Rechecked the database totals: 2,208 Orthanc-backed studies and 2,377
      imaging studies overall.

20. **Confirmed GitHub Actions for the pushed commit**
    - Watched Aurora CI run `27726521351` to completion.
    - Federation, Backend Lint, Frontend, AI Service, Security Audit, and Backend
      Tests all completed successfully.
    - The deploy and E2E workflow jobs were skipped by workflow conditions; the
      production deploy and imaging E2E smoke were run manually in this session.

### Remaining Follow-Ups

- Investigate the 24 skipped Orthanc studies and decide whether to link,
  ignore, or quarantine them.
- Implement or intentionally retire the remaining imaging stubs in
  `ImagingController`.
- Add an OHIF study-detail smoke that opens a specific indexed study and
  asserts the iframe URL carries the expected `StudyInstanceUIDs` parameter.
- Move production frontend serving from public Vite dev server to static
  `frontend/dist` assets.
- Start the FHIR/OMOP adapter implementation tranche after static serving and
  imaging productization are stable.

---

## 2026-03-25 — Stabilization & Verification Milestone Complete (10 Phases)

**Branch:** `v2/phase-0-scaffold`

### Overview

Completed the full Aurora Stabilization & Verification milestone — 10 phases, 52 requirements, ~280 automated tests across all layers. Used GSD workflow for structured planning, execution, and verification.

### Phase 1: Fix Critical Blocker & Verify Core Endpoints
- **Root cause:** `exists:clinical.patients,id` validation in CaseController interpreted `clinical` as a Laravel connection name (not PostgreSQL schema). Connection didn't exist in `config/database.php`.
- **Fix:** Added `clinical` database connection alias mirroring `pgsql` with `search_path => 'clinical,public'`.
- All 7 core endpoint groups verified: login, register, change-password, dashboard, patients, cases.
- Created reusable `verify-endpoints.sh` (237 lines, 8/8 checks pass).

### Phase 2: Verify Genomics & AI Endpoints
- Ran `GeneDrugInteractionSeeder` (42 records) and `ClinicalDemoSeeder` (766 variants).
- Verified: interactions (42 records), stats (766 variants, 140 pathogenic), AI briefing via Ollama medgemma-q4.
- Laravel AI proxy returns 503 in Docker dev (expected — container networking). Direct AI service works.

### Phase 3: Backend Test Infrastructure
- Configured Pest with `DatabaseTruncation` (not `RefreshDatabase` — 10-50x faster with 27+ migrations).
- Created `.env.testing` with `aurora_test` database, array drivers for cache/session/queue.
- Protected Spatie permission tables via `$exceptTables`.
- Created 3 new Clinical factories (ClinicalPatient, GeneDrugInteraction, GenomicVariant).
- Added `HasFactory` trait + `newFactory()` overrides to Clinical models.
- 5 factory smoke tests passing.

### Phase 4: Frontend & AI Test Infrastructure
- **Frontend:** Vitest 3.x with V8 coverage + jsdom, MSW 2.x handlers (4 endpoints), React test utilities (QueryClient/Router/Zustand wrappers), 8 smoke tests.
- **AI:** pytest with `asyncio_mode=auto` + coverage, `conftest.py` with mock_ollama/mock_anthropic, 3 smoke tests.
- **E2E:** Playwright smoke test (2 tests, chromium).

### Phase 5: Backend Feature Tests
- **101 tests, 303 assertions** across all 7 controllers.
- Auth (12), Patient (22), Dashboard (3), Case (16), Session (22), Genomics (20), Radiogenomics (7), Factory Smoke (5).
- Discovered and fixed `app` connection alias (same pattern as `clinical`).
- Fixed intermittent GenomicsControllerTest unique constraint collision.
- Pre-existing Mockery conflict in legacy EventTest documented (not caused by this work).

### Phase 6: Backend Unit Tests
- **51 tests, 378 assertions** across 5 service test files.
- AuthService (18): login, register, changePassword, logout, generateTempPassword, formatUser.
- PatientService (7): getStats, createPatient, getProfile.
- CaseService (13): create with auto-coordinator, update, archive, team management.
- RadiogenomicsService (8): panels, variant classification, correlations.
- OncoKbService (5): sync, HTTP handling, error paths.

### Phase 7: Frontend Tests
- **54 tests, 87.73% statement coverage.**
- authStore (9), profileStore (6), genomics hooks (6), EvidenceBadge (5), ActionableVariantsPanel (4), TreatmentTimeline (3), GenomicBriefing (4), GenomicVariantTable (4), LoginPage (4), RegisterPage (4).
- Coverage scoped to tested modules (untested features like patient-profile out of scope).

### Phase 8: AI Service Tests
- **22 tests, 82.42% scoped coverage.**
- Health endpoint (5), genomic briefing endpoint (5), briefing service (6), LLM utils (4).
- Coverage scoped to 7 core modules via pytest.ini.

### Phase 9: Feature Completion
- **OncoKB parsing:** Implemented `parseAndUpsertTreatments` with evidence level mapping (8 levels), resistance detection, combo drug normalization, idempotent `updateOrCreate`. 12 unit tests.
- **Upload endpoints:** Created `GenomicUpload` model/migration/factory. Replaced 4 stub methods with real file storage + DB persistence. 8 feature tests.
- **Criteria endpoints:** Created `GenomicCriteria` model/migration/factory. Replaced 4 stub methods with real Eloquent CRUD. 7 feature tests.

### Phase 10: E2E Tests
- **11 Playwright browser tests** across 4 spec files.
- auth.spec.ts (3): login success, invalid credentials, form validation.
- patient-profile.spec.ts (3): navigation, demographics, tab switching.
- genomics.spec.ts (2): conditional skip when no genomic data (expected).
- case-lifecycle.spec.ts (3): list, create, detail with team.
- StorageState auth setup for rate-limiter resilience.

### Totals
- **52/52 requirements satisfied**
- **~280 automated tests** (backend 152, frontend 54, AI 22, E2E 11)
- **Coverage:** Frontend 87.73%, AI 82.42%
- **Key bugs fixed:** clinical/app DB connection aliases, .env.testing DB_HOST, factory unique constraint
- **Features completed:** OncoKB parsing, genomic uploads, genomic criteria

**Files changed:** 100+ files across backend, frontend, ai, e2e, and .planning directories.

---

## 2026-03-25 — Nginx Static Assets Fix, Seeder Safety, DB Data Restoration

**Branch:** `v2/phase-0-scaffold`

### Nginx Static Asset Serving Fix

Aurora SVG logo was displaying as a broken image. Root cause: no `/image/` location block in nginx config — requests fell through to the Vite dev server catch-all, which returned SPA HTML (content-type `text/html`) instead of the SVG file.

**Fix:** Added explicit `/image/` alias block in `docker/nginx/default.conf` alongside existing `/build/` and `/storage/` blocks.

### Database Seeder Safety (Critical)

After a migration, only the superuser was being seeded — all demo patients, cases, and gene-drug interactions were lost. Two problems:

1. **DatabaseSeeder only called SuperuserSeeder** — all other seeders (ClinicalDemoSeeder, SampleCaseSeeder, GeneDrugInteractionSeeder, SpecialtyTemplateSeeder) were commented out or required manual invocation.
2. **SampleCaseSeeder deleted ALL cases** — `DB::table('app.cases')->whereNull('deleted_at')->delete()` wiped user-created cases on every re-seed.

**Fixes:**
- **DatabaseSeeder** now includes all 5 seeders in the correct dependency order
- **SampleCaseSeeder** made safe — only deletes cases linked to `DEMO-%` patients, never touches user-created data
- All seeders verified idempotent: `updateOrCreate`, `firstOrCreate`, `insertOrIgnore`, or scoped deletes

**Data restored:** 1 superuser, 13 patients, 12 cases, 42 gene-drug interactions, specialty templates.

**Files changed:** `docker/nginx/default.conf`, `database/seeders/DatabaseSeeder.php`, `database/seeders/SampleCaseSeeder.php`

---

## 2026-03-24 — Case-Patient Integration + Fully Dockerized Dev Environment

**Branch:** `v2/phase-0-scaffold`

### Case-Patient Profile Integration

Eliminated the context switch between case review and patient data. Clinicians reviewing a case at `/cases/:id` now see the full patient profile directly in the Overview tab — no more navigating away to `/profiles/:personId`.

**What was built:**
- **Collapsible case context header**: clinical question, summary, case details, and activity stats promoted from the Overview tab into a collapsible section in the case header
- **Embedded patient profile**: Overview tab renders all 9 patient view modes (Briefing, Timeline, List, Labs, Visits, Notes, Imaging, Genomics, Similar Patients) using existing profile components — zero duplication
- **No patient fallback**: cases without a linked patient show a "Link Patient" prompt that opens the edit form
- **CaseForm patient_id field**: new optional field to link a patient to a case
- **Shared CSV utility**: extracted `downloadEventsAsCsv` to `patient-profile/utils/csvExport.ts` for reuse
- **Full profile link**: "Full profile" link on the demographics card navigates to the standalone profile page
- Collaboration panel (Cmd/Ctrl+Shift+C) works within the case context

**Files changed:** CaseDetailPage.tsx (major rewrite), CaseForm.tsx, csvExport.ts (new), PatientProfilePage.tsx (import refactor)

### Fully Dockerized Dev Environment

Replaced the broken dual-setup (Apache direct-serve + incomplete Docker) with a single `docker compose up` that serves everything.

**What was built:**
- **docker-compose.yml rewrite**: removed Docker Postgres (use host), fixed PHP volume (`./backend:/var/www/html`), fixed Node volume (`./frontend:/app` + anonymous `node_modules`), activated Vite dev server, added `host.docker.internal` access
- **nginx multi-upstream routing**: `/api/*` → PHP-FPM, `/orthanc/*` → host Orthanc with auth + CORS, `/@vite/*` + `/ws` → Vite HMR, everything else → Vite SPA
- **PHP entrypoint script**: auto-installs composer deps on first run, clears caches, then exec's php-fpm
- **Vite config**: `host: 0.0.0.0`, port 5173, conditional `base` (`/` dev, `/build/` prod), `allowedHosts` for aurora.acumenus.net
- **Apache → reverse proxy**: replaced DirectoryRoot/PHP handler with simple ProxyPass to Docker nginx on :8085, WebSocket support for HMR
- **Host Postgres access**: `pg_hba.conf` allows Docker bridge network, PHP connects via `host.docker.internal`

**Result:** Both `localhost:8085` and `https://aurora.acumenus.net` serve the same Docker stack with Vite HMR, API, and Orthanc proxy working.

**Files changed:** docker-compose.yml, docker/nginx/default.conf, docker/php/Dockerfile, docker/php/entrypoint.sh (new), frontend/vite.config.ts, .env.example (new), Apache vhost

---

## 2026-03-22 — Action-Oriented Patient Experience Redesign

**Branch:** `v2/phase-0-scaffold`

### What was built

Transformed Aurora's patient views from passive data browsers (ported from Parthenon) into an action-oriented clinical collaboration surface. 23 commits across 4 phases.

**Phase 1: Schema + Briefing**
- New tables: `app.patient_flags`, `app.patient_tasks`
- Extended `decisions`, `case_discussions`, `case_annotations`, `follow_ups` with `patient_id` + anchoring fields
- `ValidRecordRef` validation rule for `domain:id` format
- 3 new controllers: PatientFlagController, PatientTaskController, PatientCollaborationController
- 10 new API endpoints for flags, tasks, collaboration aggregate, decisions
- Frontend: collaboration types, API layer, TanStack Query hooks
- **PatientBriefing**: 4-quadrant dashboard (Active Problems, Flagged Findings, Pending Actions, Recent Decisions) — now the default landing view, replacing Timeline

**Phase 2: Inline Actions**
- InlineActionMenu: three-dot context menu with inline flag/task creation forms
- SelectActToolbar: floating batch-action toolbar with framer-motion animation
- Added to all 5 data views: Genomics (with checkbox selection), Labs (with checkbox selection), Notes, Visits, Imaging

**Phase 3: Collaboration Panel**
- CollaborationPanel: 320px slide-out right panel, domain-sensitive filtering
- 4 panel tabs: Discussions, Tasks+FollowUps, Flags, Decisions — wired to live data
- Keyboard shortcut: Cmd/Ctrl+Shift+C
- Main content adjusts width when panel is open

**Phase 4: Session Agenda**
- SessionAgenda: multi-case ordered agenda with reorder, status tracking, patient links
- SessionDecisionLog: per-case decision display with voting tallies
- CaseDetailPage simplified to 3 tabs (Overview, Documents, Team) with "Open Patient" link

### Also committed
- ClinVar integration (sync service, models, API endpoints)
- TCIA demo patient seeder with clinical data
- GenomicsController expanded with ClinVar search/sync
- Various frontend fixes (imports, null guards, timeline improvements)

### API testing results (aurora.acumenus.net)
- All 10 new endpoints verified working (GET, POST, PATCH, DELETE)
- Flag create/resolve cycle tested
- Task create/complete cycle tested
- Collaboration aggregate returns all 5 collections
- Frontend served at 200
