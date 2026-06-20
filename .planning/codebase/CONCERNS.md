# Codebase Concerns

**Analysis Date:** 2026-03-24

> Historical snapshot, superseded on 2026-06-18. This document is retained for
> old audit context and should not be treated as the active issue list. Current
> tracking lives in `.planning/comprehensive-app-completion-todo.md`. In
> particular, the database connection blocker, OncoKB response parsing coverage,
> genomics upload/criteria persistence, and frontend test availability have
> moved on since this snapshot.

## Critical Production Issues

### Database Connection Configuration Error
**Severity: CRITICAL — Blocks all API endpoints**

- **Issue**: Validation rules reference `exists:clinical.patients` but the `clinical` database connection is not defined in `config/database.php`
- **Files**:
  - `backend/config/database.php` (missing `clinical` connection definition)
  - `backend/app/Http/Controllers/CaseController.php` lines 50, 104 (reference `exists:clinical.patients,id`)
- **Symptoms**: All API endpoints return 500 "An unexpected error occurred" — even `/api/login` fails because validation middleware attempts to validate against a non-existent connection
- **Impact**: Application is completely non-functional. Users cannot authenticate, access cases, or perform any API operation
- **Fix approach**:
  1. Add `clinical` database connection to `config/database.php` — either as an alias pointing to the same PostgreSQL instance, or as a separate schema reference
  2. Verify the connection name matches Laravel's connection naming conventions
  3. Test that `/api/login` succeeds before proceeding with other features
  4. All other models and controllers referencing the `clinical` connection will then function

---

## Tech Debt

### OncoKB API Integration — Incomplete Implementation
**Severity: HIGH — Feature is non-functional stub**

- **Issue**: OncoKB response parsing is deliberately stubbed out pending future work
- **Files**: `backend/app/Services/Genomics/OncoKbService.php` lines 22, 49-52
- **Current state**:
  - Connectivity check works (verifies API token, makes HTTP request)
  - Timestamp updates work (updates `oncokb_last_synced_at` on sync)
  - Parsing is missing — OncoKB response data is fetched but not processed
- **Impact**: Gene-drug interactions are seeded manually; OncoKB updates are not automatically ingested. Clinical team cannot rely on fresh therapy recommendations from OncoKB database
- **Fix approach**:
  1. Define response parsing logic for OncoKB treatment annotations
  2. Map OncoKB evidence levels to internal `GeneDrugInteraction.evidence_level` enum
  3. Upsert new/updated interactions on sync (create/update `GeneDrugInteraction` records)
  4. Add unit tests for parsing
  5. Schedule sync in `routes/console.php` (already scheduled for weekly execution)

---

### Genomics Controller Stub Endpoints
**Severity: MEDIUM — Feature incomplete but not blocking**

- **Issue**: Upload and criteria management endpoints are placeholders returning empty stubs
- **Files**: `backend/app/Http/Controllers/GenomicsController.php` lines 41-100+ (listUploads, storeUpload, showUpload, listCriteria, storeCriterion, updateCriterion, destroyCriterion)
- **Current state**: Endpoints validate input but return synthetic empty responses
- **Impact**: Frontend cannot upload genomic files, create filtering criteria, or manage evidence queries. Gene-drug interaction table is manually seeded
- **Priority**: Deferred to Phase 1 — core genomics viewing works, but upload workflow is not functional
- **Fix approach**:
  1. Implement file upload handler with DICOM/VCF/CSV parsing
  2. Create `GenomicUpload` model to track batch metadata
  3. Implement `GenomicCriteria` model for complex variant filtering
  4. Add integration tests for upload pipeline

---

## Code Complexity & Maintainability

### Large Frontend Components Require Breaking Apart
**Severity: MEDIUM — Technical debt accumulation**

**Oversized components (>700 lines):**
- `frontend/src/features/patient-profile/components/PatientTimeline.tsx` (938 lines)
  - Multi-domain timeline rendering with lane packing, filtering, zoom, date range selection
  - Complex SVG rendering with manual layout calculation
  - Should extract: `TimelineEventRenderer`, `TimelineLanePacker`, `TimelineToolbar`, `TimelineTooltip`

- `frontend/src/features/cases/pages/CaseDetailPage.tsx` (781 lines)
  - Case details, document uploads, team management, 9 view modes, 7 domain tabs
  - Embeds multiple feature modules (patient profile, imaging, genomics, collaboration)
  - Should extract: `CaseOverviewView`, `CaseDocumentsView`, `CaseTeamView` (already exists), separate view renderers for each mode

- `frontend/src/features/imaging/pages/ImagingPage.tsx` (631 lines)
  - DICOM viewer integration, measurement tools, study/series navigation
  - Consider extracting: `DicomViewerToolbar`, `SeriesList`, `MeasurementPanel`

- `frontend/src/features/genomics/pages/GenomicsPage.tsx` (648 lines)
  - Variant table with filtering, gene-drug interactions, actionable variants, timeline
  - Consider extracting: `GenomicsFilters`, `InteractionsSidebar`, `ActionableVariantsPanel`

- `frontend/src/components/layout/AbbyPanel.tsx` (690 lines)
  - Abby conversation sidebar with threading, mentions, commands, reaction handling
  - Large JSX tree, consider extracting: `AbbyMessageThread`, `AbbyCommandPalette`, `AbbyMentionPopover`

- `frontend/src/features/commons/api.ts` (835 lines)
  - 50+ API functions mixed with 30+ TanStack Query hooks
  - Should split into: `channelsApi.ts` + `channelsQueries.ts`, `messagesApi.ts` + `messagesQueries.ts`, etc.

**Impact**: Difficult to test individual features, high merge conflict risk, slow IDE performance, harder to reason about props and state flow

**Fix approach**:
  1. Extract child components from each oversized file
  2. Create separate query hook files (`useChannels`, `useMessages`, etc.) per feature
  3. Add Storybook stories for new components to verify isolation
  4. Ensure no regression with existing tests

---

### Large Backend Controller Files
**Severity: LOW — Still within acceptable bounds but trending toward bloat**

- `backend/app/Http/Controllers/ImagingController.php` (1,186 lines)
  - Manages study listing, measurements, segmentations, time-series queries
  - All endpoints in one file — consider extracting to `MeasurementController`, `SegmentationController`

- `backend/app/Http/Controllers/GenomicsController.php` (419 lines)
  - Manageable size, but will grow as upload and criteria endpoints are implemented
  - Early extraction: split stats, variants, interactions into separate concerns if growth continues

**Impact**: Harder to navigate, potential for increased HTTP request handler complexity

**Fix approach**:
  1. Use sub-namespace controllers (`Http/Controllers/Imaging/MeasurementController.php`)
  2. Route to specific controllers per resource type
  3. Keep related queries in the same file (e.g., all measurement queries in `MeasurementController`)

---

## Test Coverage Gaps

### Limited Frontend Test Coverage
**Severity: MEDIUM — No ComponentError Boundaries**

- **Issue**: Very few frontend components have written tests or error boundaries
- **Files affected**: Most `frontend/src/features/*/components/` and `frontend/src/features/*/pages/`
- **Current state**:
  - 4 test files found in backend (`AuthenticationTest.php`, `PatientTest.php`, `EventTest.php`, `CaseDiscussionTest.php`)
  - 0 frontend unit/integration tests found
  - No E2E tests checking critical workflows (login → case creation → decision capture)
- **Impact**: Regressions in UI are not caught before deployment. Complex components (PatientTimeline, CaseDetailPage, GenomicsPage) can break unexpectedly
- **Required coverage**: Minimum 80% per project standards
  - PatientTimeline: test lane packing algorithm, date filtering, zoom
  - CaseDetailPage: test view switching, document upload, case editing
  - GenomicsPage: test variant filtering, gene-drug interaction lookup, briefing generation
  - Genomics hooks: test API calls, caching, error states

**Fix approach**:
  1. Add Vitest setup for frontend (config already exists at root but may not be fully integrated)
  2. Write tests for critical paths: login → navigate to patient → view genomics tab
  3. Test error handling: missing patient data, failed API calls, timeout scenarios
  4. Add E2E tests with Playwright for: user creation → case conference flow → decision capture

---

### Incomplete Genomics Backend Testing
**Severity: MEDIUM**

- **Issue**: Genomics services (OncoKbService, ClinVarSyncService, ClinVarAnnotationService) have no unit tests
- **Files**:
  - `backend/app/Services/Genomics/OncoKbService.php` (untested)
  - `backend/app/Services/Genomics/ClinVarSyncService.php` (untested)
  - `backend/app/Services/Genomics/ClinVarAnnotationService.php` (untested)
- **Impact**: Parsing bugs in sync services can corrupt variant data silently. Missing coverage for error cases (API timeout, malformed response)
- **Fix approach**:
  1. Mock HTTP responses from OncoKB and ClinVar APIs
  2. Test parsing of real API payloads
  3. Test database transaction rollback on error
  4. Test sync idempotency (running twice produces same result)

---

## Fragile Areas

### Patient Timeline SVG Rendering — Complex Coordinate Math
**Severity: MEDIUM — Risk of layout bugs**

- **Files**: `frontend/src/features/patient-profile/components/PatientTimeline.tsx` lines 96-250+ (lane packing algorithm)
- **Why fragile**:
  - Custom lane packing algorithm (no library — hand-rolled collision detection)
  - Manual SVG coordinate calculation for event positioning
  - Multiple coordinate systems (timeline milliseconds, pixel offsets, lane rows)
  - Zoom and pan state applied in multiple places
- **Risk**: Small changes to event sizing, padding, or zoom logic can break visual alignment
- **Safe modification**:
  1. Add test cases for lane packing with edge cases: overlapping events, events on same day, very long events
  2. Use visual regression testing (Percy, Chromatic) before merging timeline changes
  3. Extract lane packing to pure function with unit tests
  4. Add parameter documentation for coordinate transformations

---

### Clinical Data Adapter Layer — Schema Flexibility vs. Type Safety
**Severity: MEDIUM — Risk of data loss during mapping**

- **Files**:
  - `backend/app/Services/Adapters/OmopAdapter.php`
  - `backend/app/Services/Adapters/FhirAdapter.php`
  - `backend/app/Services/Adapters/ManualAdapter.php`
  - `backend/app/Models/Clinical/*.php` (clinical schema models)
- **Why fragile**:
  - Each adapter maps from different schema to normalized internal model
  - Mapping rules are not validated — fields may be silently dropped if source has extra attributes
  - No versioning of mapping rules — if FHIR R4 adds new coded fields, they're silently ignored
  - OMOP adapter reads directly from CDM without caching — network issues can cause partial data loads
- **Risk**:
  - Variant interpretation differs if genomic source loses chromosome or position during mapping
  - OMOP queries fail silently if CDM schema changes
  - FHIR adapter misses new fields added by EHR vendor
- **Safe modification**:
  1. Add mapping validation: log all fields in source that don't have target mapping
  2. Use schema versioning: track which FHIR version / OMOP version the mapping targets
  3. Add integration tests with real sample data from each source type
  4. Test with intentional schema mismatches to verify graceful degradation

---

### Error Handling — Swallowing Errors in Non-Critical Services
**Severity: MEDIUM — Silent failures**

- **Issue**: Several services catch all exceptions but only log — no user-facing error indication
- **Files**:
  - `backend/app/Services/Genomics/OncoKbService.php` lines 57-60 (catches all, logs, continues)
  - `backend/app/Services/Genomics/ClinVarSyncService.php` (scheduled command failures silently logged)
  - `backend/app/Console/Commands/RefreshEvidenceCommand.php` (job failures logged to console)
  - `backend/app/Http/Controllers/Admin/SystemHealthController.php` (returns empty array on connection errors)
- **Impact**:
  - OncoKB sync fails silently — team doesn't know therapy recommendations are stale
  - ClinVar sync hangs — variant annotations become outdated without warning
  - Admin dashboard shows no data when services are down (assumes "no data" vs. "error loading")
- **Fix approach**:
  1. Use structured logging: `Log::error('oncokb_sync_failed', ['gene' => $gene, 'code' => $response->status()])`
  2. Create `SyncStatusModel` to track last sync timestamp + error state
  3. Return error status in health endpoint: `{ "oncokb_sync": "healthy|stale|error" }`
  4. Add dashboard notification if sync is >7 days stale or in error state
  5. Admin users see "Sync failed 2 hours ago: API timeout" not just empty table

---

## Performance Bottlenecks

### Potential N+1 Query in Imaging Module
**Severity: MEDIUM — Scalability concern**

- **Issue**: ImagingController.php line 44 counts related measurements in a loop
- **Files**: `backend/app/Http/Controllers/ImagingController.php` lines 18-46 (formatStudy method)
- **Pattern**:
  ```php
  'measurement_count' => $study->imagingMeasurements()->count(),
  'segmentation_count' => $study->segmentations()->count(),
  ```
- **Impact**: If listing 20 studies, this issues 40 additional COUNT queries. With 1,000+ studies, becomes significant
- **Fix approach**:
  1. Eager load counts: `ImagingStudy::withCount(['imagingMeasurements', 'segmentations'])`
  2. Use `measurement_count` and `segmentation_count` attributes from relation count
  3. Add test: verify only 1 query for studies list, not N queries

---

### Clinical Event Filtering — Unbounded Query Results
**Severity: LOW — May become issue at scale**

- **Issue**: Several query patterns lack explicit pagination or limits
- **Files**:
  - `backend/app/Http/Controllers/PatientTaskController.php` line 28 (`get()` with no limit)
  - `backend/app/Http/Controllers/GenomicsController.php` line 412 (`get()` on interactions with no limit)
  - `backend/app/Http/Controllers/Commons/MessageController.php` (has `limit()` — good pattern)
- **Impact**: If patient has 10,000+ tasks or 5,000+ gene-drug interactions, API returns all at once
- **Fix approach**:
  1. Add pagination: use `paginate(50)` instead of `get()`
  2. Or add hard limit: `limit(1000)->get()`
  3. Document API contract: "returns max 1,000 records, use pagination for more"
  4. Test with large datasets to verify response times

---

## Security Considerations

### Patient Validation Rules Reference Non-Existent Connection
**Severity: MEDIUM — Could leak validation errors**

- **Issue**: Validation rule `exists:clinical.patients,id` fails at runtime, returns generic 500 error
- **Files**: `backend/app/Http/Controllers/CaseController.php` lines 50, 104
- **Risk**: Error message leaks that system uses "clinical" schema — information disclosure
- **Current mitigation**: Laravel hides detailed error in production (logs to file)
- **Recommendations**:
  1. Fix the connection issue (critical above)
  2. Add unit test for CaseController.store() validation
  3. Verify production error responses don't leak schema names
  4. Use form request classes (StoreDiscussionRequest pattern) to centralize validation and hide details

---

### Resend Email Configuration Not Validated at Startup
**Severity: LOW — May fail at runtime**

- **Issue**: `RESEND_API_KEY` is loaded from env but never validated until first email sent
- **Files**: `backend/app/Http/Controllers/AuthController.php` line 40 (catches all exceptions)
- **Risk**: Registration fails silently if API key is missing/invalid — new users think they're registered but aren't
- **Current mitigation**: Errors are logged; subsequent email sends will fail
- **Recommendations**:
  1. Add startup validation in `AppServiceProvider`: ping Resend API to verify credentials
  2. Return 503 Service Unavailable if email service is not configured instead of 500
  3. Add admin dashboard indicator: "Email service: connected / disconnected / error"

---

### API Response Doesn't Always Include Consistent Error Format
**Severity: LOW — Minor inconsistency**

- **Issue**: Some endpoints return `response()->json($result)` instead of `ApiResponse::error()`
- **Files**:
  - `backend/app/Http/Controllers/AuthController.php` line 66 (login returns raw response)
  - `backend/app/Http/Controllers/AuthController.php` line 90 (user endpoint returns raw response)
- **Risk**: Frontend error handling expects consistent `{ success, message, data }` format — raw responses may break error display
- **Fix approach**:
  1. Standardize all endpoints to use `ApiResponse` helper
  2. Audit all controllers for inconsistent response formats
  3. Add test: verify all endpoints return proper ApiResponse format

---

## Missing Critical Features / Blockers

### Audit Logging Not Implemented
**Severity: MEDIUM — Compliance risk**

- **Issue**: `backend/database/migrations/2026_03_21_600001_create_user_audit_logs_table.php` exists but is not used
- **Files**:
  - Migration exists: `backend/database/migrations/2026_03_21_600001_create_user_audit_logs_table.php`
  - Model exists: `backend/app/Models/UserAuditLog.php`
  - No middleware/service logs case/decision updates, user access, document downloads
- **Impact**: Cannot audit who accessed what patient data, modified decisions, or downloaded reports — HIPAA compliance gap
- **Fix approach**:
  1. Create `AuditService` to log user actions: `audit()->log('case_viewed', $caseId, $userId)`
  2. Attach logging middleware to all endpoints that access PII
  3. Test audit trail: verify every case view/edit/delete is logged
  4. Add admin dashboard: show audit log search/filter by user/patient/action

---

### Decision Capture — Structure Not Fully Defined
**Severity: MEDIUM — Feature incomplete**

- **Issue**: Decision capture models exist but workflow/UI is incomplete
- **Files**:
  - `backend/database/migrations/2026_03_21_700003_create_decision_tables.php`
  - `backend/app/Models/Decision.php`
  - Frontend: `frontend/src/features/collaboration/components/DecisionCapture.tsx` (429 lines)
- **Current state**: Models for Decision, RecommendationVote, GuidelineConcordance exist; endpoints partially implemented
- **Missing**:
  - Structured decision form (not just free text)
  - Vote aggregation (unanimous / split decision indicators)
  - Outcome tracking (did this patient follow recommendations? what was result?)
  - Compliance checking (are recommendations guideline-concordant?)
- **Impact**: Decisions are captured but not analyzed — cannot learn from past cases
- **Fix approach**:
  1. Define decision form schema (what fields must be captured)
  2. Implement vote tallying and consensus scoring
  3. Add outcome tracking (follow-up checkboxes, FHIR mapping)
  4. Add dashboard: show decision quality metrics (outcomes/recommendations ratio, guideline adherence %)

---

### "Patients Like This" Not Production-Ready
**Severity: MEDIUM — Core differentiator needs implementation**

- **Issue**: Similarity engine is designed but not yet implemented
- **Files**:
  - Design doc references `patient_embeddings` table and pgvector queries
  - `backend/app/Services/RadiogenomicsService.php` exists but is radiogenomics-specific (different use case)
  - No `SimilarityService` or `EmbeddingService` found
- **Current state**:
  - pgvector is configured in PostgreSQL (plan specifies this)
  - No embedding generation on patient load
  - No similarity query endpoints
- **Impact**: Cannot show "Patients Like This" — a core differentiator of Aurora
- **Fix approach**:
  1. Create `EmbeddingService`: generates clinical embedding from patient timeline
  2. Create `SimilarityService`: queries similar patients using pgvector cosine similarity
  3. Add `/api/patients/{id}/similar` endpoint
  4. Seed initial embeddings for all patients on startup
  5. Test with known similar cases to verify ranking accuracy

---

### Federation — Deferred, Unclear Contract
**Severity: LOW — Not yet required**

- **Issue**: Federation architecture is designed but not implemented
- **Files**:
  - `federation/` directory exists but minimal implementation
  - Design doc specifies de-identification and cross-instance queries
- **Current state**: Stub/empty service
- **Impact**: Cannot query federated patients across institutions
- **Defer to**: Phase 2 (after core single-instance features are stable)

---

## Dependencies at Risk

### No Lock File for Backend / Unclear Dependency Status
**Severity: LOW — Operational risk**

- **Issue**: `backend/` is Laravel/Composer-based but no `composer.lock` visible in codebase
- **Risk**:
  - `composer install` may pick different package versions on different machines
  - Critical security patches for dependencies may not be applied
- **Recommendation**:
  1. Verify `composer.lock` exists in `.gitignore` and is not committed (normal for Docker-based deployments)
  2. Or commit lock file for production reproducibility (standard practice)
  3. Run `composer audit` weekly to detect vulnerable dependencies
  4. Use Dependabot or similar to auto-open PRs for security patches

---

### Python FastAPI AI Service — No Requirements Lock
**Severity: LOW — Reproducibility concern**

- **Issue**: `ai/requirements.txt` exists but no `requirements.lock` or pinned versions
- **Risk**: Ollama model versions, FastAPI minor versions may change between deployments
- **Recommendation**:
  1. Use `pip-tools` to generate `requirements.lock` with exact versions
  2. Test model quantization compatibility (Ollama model version vs. encoding/decoding changes)

---

## Scaling Limits

### Single PostgreSQL Instance — No Replication
**Severity: LOW — Scaling concern for future**

- **Current**: PostgreSQL 16 runs on host machine (not containerized)
- **Limitation**: Single point of failure, cannot scale reads across replicas
- **When to address**: When patient data exceeds 1GB or concurrent connections exceed 50
- **Scaling path**:
  1. Move PostgreSQL to separate server
  2. Set up read replicas (streaming replication)
  3. Use pgBouncer for connection pooling
  4. Route read-heavy queries (timeline, imaging) to replicas

---

### Redis — No Persistence Configuration
**Severity: LOW — Session/cache risk**

- **Issue**: Redis configured but persistence may not be explicitly enabled
- **Current**: Used for sessions, queue, cache, real-time pub/sub
- **Risk**: Data loss on restart, session loss if pod dies
- **Fix approach**:
  1. Enable RDB snapshots: `save 900 1` (snapshot every 15 min if 1 key changed)
  2. Or enable AOF (Append-Only File) for stronger durability
  3. Add Redis monitoring to alert on memory pressure

---

### Meilisearch — Optional, Not Mandatory
**Severity: LOW — Good design**

- **Current**: Designed as optional; system falls back to PostgreSQL full-text search
- **Risk**: Full-text search performance degrades with 100,000+ clinical notes
- **Scaling path**: Deploy Meilisearch when search latency exceeds 500ms

---

## Summary of Critical Actions

**Immediate (blocking launch):**
1. Fix database connection configuration (`clinical` connection definition)
2. Verify all API endpoints work post-fix (test login, case creation, genomics queries)
3. Add error boundary or fallback for any remaining missing services

**High Priority (Phase 1):**
1. Implement OncoKB response parsing and upsert logic
2. Implement genomics upload and criteria endpoints
3. Add frontend test coverage (80%+) with focus on critical paths
4. Fix N+1 query in imaging module

**Medium Priority (Phase 1-2):**
1. Implement audit logging (log all data access/modification)
2. Complete decision capture workflow and outcome tracking
3. Implement "Patients Like This" similarity engine
4. Extract large components into smaller, testable units
5. Add comprehensive error handling and status indicators

---

*Concerns audit: 2026-03-24*
