# Aurora Comprehensive App Completion TODO

Last updated: 2026-06-21 (swarm-audited check-off pass)

This checklist tracks the full app-completion plan across backend, frontend,
AI service, imaging, genomics, interoperability, federation, operations, and
planning hygiene.

Checkbox semantics:

- `[x]` means completed in the current repository or verified during the latest
  audit.
- `[ ]` means open work.
- A parent item can remain unchecked even when some child items are complete;
  that means the workstream is only partially complete.

## 0. Validation And Local Tooling

- [ ] Restore full local validation capability.
  - [x] Frontend `node_modules` are present.
  - [x] E2E `node_modules` are present.
  - [x] Backend `vendor/` is present.
  - [x] AI `venv/` exists.
  - [x] Reinstall backend dev dependencies so `backend/vendor/bin/pest` and
    `backend/vendor/bin/phpunit` exist.
  - [ ] Rebuild `ai/venv` from `ai/requirements.txt`; host Python 3.14 cannot
    currently install the pinned dependency set because `pydantic-core==2.27.2`
    does not support Python 3.14 through PyO3.
  - [x] Update stale backend test command documentation. `php artisan test`
    and `./vendor/bin/pest` are both available after backend dependencies were
    restored.
  - [x] Decide whether tests should run through host tooling, Docker, or both.
    Current verified path: frontend and backend on host; AI tests through the
    supported Python 3.12 Docker image.

- [ ] Maintain green validation.
  - [x] Frontend typecheck passed: `npm --prefix frontend run typecheck`.
  - [x] Frontend unit tests passed: `npm --prefix frontend test`
    reported 27 files / 88 tests passing.
  - [x] Frontend production build passed: `npm --prefix frontend run build`.
  - [x] Remove or suppress the jsdom navigation warning emitted during frontend
    auth tests, or document it as expected test-environment noise.
  - [x] Backend test suite runnable locally with explicit testing DB settings:
    `APP_ENV=testing DB_CONNECTION=pgsql DB_HOST=localhost DB_DATABASE=aurora_test
    DB_USERNAME=smudoshi DB_MIGRATIONS_TABLE=public.migrations DB_PASSWORD=<from
    backend/.env> ./vendor/bin/pest --exclude-group=mockery-alias` passed 475
    tests / 1,655 assertions.
  - [x] AI pytest suite runnable locally through Docker:
    `docker run --rm aurora-ai:dev python -m pytest` passed 40 tests with
    required coverage met at 81.57%.
  - [x] E2E suite recently verified against the intended deployment target:
    `npx playwright test --project=chromium` against
    `https://aurora.acumenus.net` passed 31 tests with 2 data-dependent
    genomics skips.
  - [x] Harden Playwright auth setup to reuse a validated stored Sanctum state
    before attempting form login, avoiding false failures against the
    rate-limited public login endpoint.

## 1. Imaging Productization

- [x] Complete the Orthanc/OHIF imaging workflow.
  - [x] Orthanc proxy and DICOMweb access were verified in planning summary.
  - [x] Orthanc corpus was synced into Aurora.
  - [x] Indexed Orthanc studies return `status=indexed` and
    `wadors_uri=/orthanc/dicom-web`.
  - [x] `POST /api/imaging/studies/{id}/index-series` is implemented against
    Orthanc.
  - [x] Study and series response aliases were normalized for frontend
    compatibility.
  - [x] Skipped-study CSV reporting was added to
    `dicom/sync_orthanc_to_aurora.py`.
  - [x] Backend coverage exists for indexed study metadata and Orthanc-backed
    series indexing.
  - [x] Playwright imaging smoke opens an indexed study detail and checks the
    OHIF iframe `StudyInstanceUIDs` parameter.
  - [x] Decide policy for 24 blank-DICOM-PatientID MR studies: quarantine,
    manual link, or synthetic research records.
  - [x] Add durable documentation for the blank-PatientID decision in
    `docs/imaging-ingestion-policy.md`.

- [x] Replace or retire remaining user-facing imaging stubs.
  - [x] Implement or remove `POST /api/imaging/studies/index-from-dicomweb`.
  - [x] Define DICOMweb bulk indexing policy: queued idempotent QIDO-RS
    ingestion, not synchronous UI work.
  - [x] Implement or remove `POST /api/imaging/studies/{id}/extract-nlp`.
  - [x] Implement real `GET /api/imaging/features`.
  - [x] Return frontend-compatible population analytics arrays from
    `GET /api/imaging/analytics/population` while retaining legacy distribution
    maps.
  - [x] Implement persistent imaging criteria list/create/delete.
  - [x] Implement or remove `POST /api/imaging/import-local/trigger`.
  - [x] Define local DICOM import policy: allowlisted queued import only, with
    run status and file-level skip/error capture.
  - [x] Implement or remove `POST /api/imaging/studies/auto-link`.
  - [x] Define auto-link policy: deterministic identifier matches only; blank
    PatientID studies remain quarantined/manual-review.
  - [x] Persist `POST /api/imaging/patients/{personId}/response-assessments`
    instead of returning an `id: 0` response.
  - [x] Implement or remove `POST /api/imaging/studies/{id}/ai-extract`.
  - [x] Implement or remove `GET /api/imaging/studies/{id}/suggest-template`.
  - [x] Retire frontend stub-success behavior for deferred DICOMweb indexing,
    local DICOM import, auto-link, NLP extraction, AI measurement extraction,
    and template suggestion actions.
  - [x] Hide frontend controls for any endpoint intentionally deferred.
  - [x] Add regression tests that fail if a user-facing imaging action returns
    stub success.
  - [x] Normalize imaging measurement create/list/timeline contracts so
    frontend-entered names, body sites, laterality, target lesion numbers,
    series IDs, algorithm names, and confidence values round-trip.

- [x] Improve imaging backend structure and performance.
  - [x] Split the 1,651-line `ImagingController` into narrower controllers or
    services.
  - [x] Replace per-study measurement/segmentation count queries with
    eager-loaded counts.
  - [x] Add query-count or performance tests for study listing.

## 2. Imaging AI And Measurements

- [ ] Replace mock AI segmentation with production-capable execution.
  - [x] AI service exposes imaging endpoints for segmentation, volumetrics,
    response assessment, and feature extraction.
  - [x] RECIST/Lugano/Deauville/RANO-style rule logic exists in the AI response
    assessment service.
  - [ ] Replace mock segmentation structures in `segmentation_service.py` with
    an actual model path, for example TotalSegmentator or nnU-Net.
  - [ ] Define sync vs queued execution for large DICOM studies.
  - [ ] Persist segmentation, volumetric, and extracted-feature outputs in
    Laravel tables.
  - [ ] Add job status, retries, error states, and audit metadata.
  - [ ] Distinguish clinician-entered measurements from computed measurements
    in API payloads and UI.
  - [ ] Add model/service tests for real image-analysis execution boundaries.

## 3. FHIR / OMOP Interoperability

- [ ] Implement the interoperability spine.
  - [x] `ClinicalDataAdapter` contract exists.
  - [x] `ManualAdapter` implements patient profile, search, imaging, genomics,
    notes, visits, conditions, medications, procedures, observations, and labs.
  - [x] `PatientService` delegates profile/search to an adapter.
  - [x] Replace the `FhirAdapter` throwing stub with complete local read
    projections.
  - [x] Replace the `OmopAdapter` throwing stub with complete local read
    projections.
  - [x] Add adapter selection/configuration so the app can use FHIR or OMOP
    intentionally rather than manual-only defaults.
  - [x] Add contract tests for adapter output shapes consumed by patient profile,
    search, notes, imaging tabs, and genomics tabs.
  - [ ] Extend contract tests to Abby, decision drafting, and cohort tools once
    their first standards-backed workflows are selected.
  - [x] Define and implement the first outbound emit path: FHIR Genomics report
    export as a FHIR R4 Bundle with `DiagnosticReport` and variant
    `Observation` resources.

## 4. Genomics Upload And Annotation Pipeline

- [x] Complete upload-to-variant processing.
  - [x] `GenomicUpload` model/table/factory exists.
  - [x] Upload list/create/show/delete endpoints use real file storage and DB
    persistence.
  - [x] `GenomicCriteria` model/table/factory exists.
  - [x] Genomic criteria list/create/update/delete endpoints are persistent.
  - [x] Genomics stats count persisted uploads.
  - [x] Global ClinVar status/search/sync endpoints exist.
  - [x] OncoKB treatment parsing and gene-drug interaction upsert logic exists.
  - [x] Implement upload-level `matchPersons` instead of returning zero counts.
  - [x] Implement upload-level `importToOmop` instead of synthetic
    `stub.vcf` data.
  - [x] Implement upload-level `annotateClinVar` instead of zero-count no-op.
  - [x] Add parser/import tests for VCF/CSV happy paths, malformed files,
    duplicate variants, and unmatched samples.
  - [x] Update frontend upload detail states so no no-op pipeline action appears
    successful.

## 5. Realtime Collaboration

- [ ] Wire real-time collaboration infrastructure.
  - [x] Commons APIs, pages, hooks, and UI modules exist.
  - [x] Frontend hooks are written for channel subscription, presence,
    notification listening, and typing whispers.
  - [x] Replace `frontend/src/lib/echo.ts` stub with Laravel Echo/Reverb or
    Soketi initialization.
  - [x] Configure `/broadcasting/auth` and private/presence channel
    authorization.
  - [x] Add backend broadcast events for messages, replies, reactions,
    notifications, presence, and typing.
  - [ ] Add graceful polling fallback when real-time transport is unavailable.
  - [ ] Add multi-user E2E coverage for live messages, notifications, and
    presence.

## 6. Federation, Matchmaker, And Beacon

- [ ] Complete federated discovery.
  - [x] Federation relay service exists.
  - [x] Federation relay health, peer registration, fan-out, de-identification,
    and k-anonymity scaffolding exist.
  - [ ] Replace `/federation/respond` empty-result stub with local similarity
    query execution.
  - [ ] Add peer registration/configuration workflow and operational docs.
  - [ ] Add audit logs and institution policy controls for federated queries.
  - [ ] Validate end-to-end peer-to-peer federation with at least two Aurora
    instances.

- [ ] Productize rare-disease discovery standards.
  - [x] MME peer/match models and migrations exist.
  - [x] MME inbound `/api/mme/v1/match` route exists.
  - [x] MME outbound search and persisted match listing exist.
  - [x] Beacon v2 public discovery endpoints exist.
  - [x] Beacon genomic variant boolean/count query exists.
  - [ ] Configure real MME peers and document consent/privacy controls.
  - [ ] Expand Beacon filtering terms beyond an empty response.
  - [x] Decide Beacon record/count access tiers and k-anonymity policy.

## 7. AI Decision Intelligence

- [ ] Complete decision intelligence beyond slice 1.
  - [x] Laravel decision draft proxy exists.
  - [x] FastAPI evidence-grounded draft decision endpoint exists.
  - [x] BioMCP retrieval integration exists for articles, trials, and variants.
  - [x] Claude decision draft path enforces de-identified context and structured
    JSON response.
  - [x] Laravel `DecisionController` persists AI attribution fields such as
    `ai_generated`, `ai_model`, `ai_confidence`, `ai_sources`, and
    `ai_drafted_at`.
  - [ ] Add ambient MDT transcript ingestion with local audio-first defaults.
  - [ ] Add diarization and speaker attribution.
  - [ ] Convert transcript segments into draft decisions, dissent points,
    follow-ups, and task suggestions.
  - [ ] Add clinician review workflow for transcript-derived decisions.
  - [ ] Track edit distance, review time, accepted/rejected evidence, confidence
    deltas, and decision-quality instrumentation.
  - [ ] Add privacy and retention controls for audio/transcripts.

## 8. Rare Disease Follow-Ons

- [ ] Deepen rare-disease support after the foundation slice.
  - [x] Diagnostic odyssey backend and worklist exist.
  - [x] Phenotype feature capture exists.
  - [x] HPO search proxy and frontend autocomplete exist.
  - [x] Phenopacket import/export surfaces exist.
  - [x] ACMG/AMP points engine exists.
  - [x] Variant canonicalization/reanalysis alert surfaces exist.
  - [x] ClinGen Allele Registry service exists.
  - [x] ClinGen Gene-Disease Validity service/configuration exists.
  - [x] VRS ID service exists and degrades to null when AnyVar is unavailable.
  - [ ] Provision VRS/SeqRepo/UTA or formally defer computed VRS IDs behind
    CAID-only identity.
  - [ ] Add operational ClinGen GDV scheduled ingestion as a second KB-change
    alert source if not already enabled in production.
  - [ ] Evaluate ClinVar TSV ingestion upgrade if `DateLastEvaluated` becomes
    required.
  - [ ] Add Phen2Gene or Exomiser behind an isolated process boundary.
  - [ ] Document rare-disease data-sharing consent for MME/Beacon participation.

## 9. First Non-Rare Population Pack

- [ ] Build the Cardiac Heart Team / TAVR pack.
  - [x] Board-template engine fields exist.
  - [x] Cases are bound to board templates.
  - [x] Template-driven case form fields and state handling exist.
  - [x] Demo cardiac/heart-team clinical content exists in seed data.
  - [ ] Define TAVR case template, candidacy rubric, agenda, and state machine.
  - [ ] Add structured decision schema for candidacy, optimization, and
    procedural planning.
  - [ ] Add computable risk-score inputs for RCRI, frailty, pulmonary risk, and
    surgical optimization.
  - [ ] Reuse imaging, task, and decision infrastructure for episode-of-care
    follow-through.
  - [ ] Add tests and demo walkthrough for the TAVR pack.

## 10. Deployment, Operations, And Security Hardening

- [ ] Harden production deployment and runtime operations.
  - [x] Default production compose/nginx path serves built frontend static
    assets from `backend/public/build`.
  - [x] Vite HMR is behind explicit development compose/nginx path.
  - [x] Deploy script builds frontend, copies assets, restarts runtime, and
    checks served HTML for built asset references.
  - [x] Deploy script stops a leftover dev Vite container before production
    static serving.
  - [x] Laravel was upgraded to a current security baseline per git history.
  - [x] Replace the raw IP-wide API throttle with an auth-first named
    `throttle:api` limiter on protected routes; authenticated SPA/E2E traffic
    now keys by user ID at 300 requests/minute while guests remain at
    60 requests/minute.
  - [x] Move Orthanc nginx credentials out of hardcoded base64 headers into env
    substitution or Docker secrets.
  - [x] Decide whether `docker-compose.prod.yml` is legacy or supported; remove
    or update it.
  - [ ] Add health checks for Orthanc, AI, federation, queues, and sync
    freshness.
  - [ ] Add admin-visible stale/error states for OncoKB, ClinVar, ClinGen,
    DICOM sync, and AI services.
  - [x] Add CI checks for backend, frontend, AI, and E2E smoke paths.
  - [x] Add coverage threshold enforcement in GitHub Actions if still desired.

## 11. Planning And Documentation Hygiene

- [ ] Keep planning docs coherent.
  - [x] `.planning/ROADMAP.md` reflects current post-stabilization roadmap.
  - [x] `.planning/STATE.md` reflects current milestone and known follow-ups.
  - [x] `docs/devlog.md` records recent imaging/static-serving closeouts.
  - [x] This comprehensive completion TODO exists at
    `.planning/comprehensive-app-completion-todo.md`.
  - [x] Mark stale `.planning/PROJECT.md` as historical, because it still
    describes old March stabilization items as active.
  - [x] Mark stale `.planning/codebase/CONCERNS.md` as historical, because it
    includes completed OncoKB/genomics concerns as open.
  - [x] Fully rewrite stale `.planning/PROJECT.md`, which still describes old March
    stabilization items as active.
  - [x] Fully rewrite stale `.planning/codebase/CONCERNS.md`, which includes completed
    OncoKB/genomics concerns as open.
  - [x] Decide whether to keep or remove untracked
    `.planning/quick/1-refactor-imaging-pipeline-for-re-indexed/1-PLAN.md`.
  - [x] Decide whether to keep, relocate, or remove untracked
    `dicom/pathology_download_plan.md`.
  - [ ] Add a devlog entry whenever each remaining tranche closes.

## 12. Large-File Refactoring And Maintainability

- [ ] Reduce highest-risk large files once behavior is stable.
  - [x] Split `backend/app/Http/Controllers/ImagingController.php`
    (1,651 lines).
  - [ ] Split `frontend/src/features/patient-profile/components/PatientTimeline.tsx`
    (938 lines).
  - [ ] Split `frontend/src/features/commons/api.ts` (835 lines).
  - [ ] Split `frontend/src/features/cases/pages/CaseDetailPage.tsx`
    (791 lines).
  - [ ] Split `frontend/src/components/layout/AbbyPanel.tsx` (690 lines).
  - [ ] Split `frontend/src/features/genomics/pages/GenomicsPage.tsx`
    (648 lines).
  - [ ] Split `frontend/src/features/imaging/pages/ImagingPage.tsx`
    (713 lines).
  - [ ] Add focused unit tests for extracted pure functions and child
    components.

## Recommended Execution Order

- [ ] Phase A: repair local validation dependencies and commands.
- [x] Phase B: finish or hide remaining user-facing imaging stubs.
- [x] Phase C: implement FHIR/OMOP adapters and genomic upload processing.
- [ ] Phase D: wire realtime collaboration and federation local responder.
- [ ] Phase E: implement ambient decision-intelligence slice 2.
- [ ] Phase F: complete rare-disease follow-ons and first non-rare TAVR pack.
- [ ] Phase G: complete operational hardening and documentation cleanup.
