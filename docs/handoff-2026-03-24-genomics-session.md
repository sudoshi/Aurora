You are continuing an Aurora development session on branch v2/phase-0-scaffold. Use GSD workflow (/gsd:progress to start, then appropriate GSD commands).

## CURRENT STATE

### Infrastructure
- All Docker services UP: nginx (:8085), php (healthy), node (:5177), redis
- PostgreSQL runs on HOST (not in Docker) — port 5432, connection via host.docker.internal
- Aurora DB has 72 tables, GeneDrugInteraction table has 42 seeded records
- App is accessible at http://localhost:8085 (returns 200)

### Branch: v2/phase-0-scaffold
- All commits pushed to origin (up to date)
- Latest commit: 558e791 "feat(genomics): unified Genomics tab with Abby briefing, therapy matching, treatment timeline"
- No uncommittable changes (build artifacts are gitignored, only DICOM logs modified)

### What Was Just Built (14 commits tonight)
The entire Patient Genomics Tab feature across all layers:

**Backend (Laravel):**
- GeneDrugInteraction model + migration + seeder (43 entries)
- EvidenceUpdate model + migration (audit trail)
- GenomicsController with interactions endpoint
- RadiogenomicsService refactored from hardcoded to DB-driven
- OncoKbService (v1 — connectivity check + timestamp, parsing is a TODO for later)
- RefreshEvidenceCommand + weekly scheduler in routes/console.php
- ClinVarAnnotationService, ClinVarSyncService (pre-existing, integrated)

**AI Service (Python FastAPI):**
- GenomicBriefingRequest/Response models
- genomic_briefing.py service (Ollama-powered narrative generation)
- POST /decision-support/genomic-briefing endpoint

**Frontend (React/TypeScript):**
- Types: genomics/types/index.ts (comprehensive)
- API: genomicsApi.ts (getInteractions, generateGenomicBriefing, interpretVariant, getRadiogenomicsPanel)
- Hooks: useGenomics.ts (4 hooks with TanStack Query)
- Components: GenomicBriefing, ActionableVariantCard, ActionableVariantsPanel, TreatmentTimeline, GenomicVariantTable, VariantExpandedRow, EvidenceBadge
- Container: PatientGenomicsTab.tsx orchestrating all sections

## CRITICAL BUG TO FIX FIRST

**ALL API endpoints return 500 "An unexpected error occurred"** — even /api/login.

The root cause is NOT the genomics code. Investigation revealed:
1. AuthService.login() works perfectly when called from tinker
2. User model works, password verifies, token creation works
3. GeneDrugInteraction::count() returns 42 from tinker
4. The error log shows: `Database connection [clinical] not configured`
5. The error originates from CaseController.php line 50: `'patient_id' => 'nullable|integer|exists:clinical.patients,id'`

**BUT** — this shouldn't affect the login endpoint. The real issue is likely:
- A middleware that runs on ALL requests and touches the clinical connection
- Or an exception handler / service provider that eagerly loads something using the clinical connection
- The `clinical` database connection is NOT defined in config/database.php — it needs to be added

**Fix approach:**
1. Add a `clinical` connection to config/database.php (it should be the same as `pgsql` but pointing to the clinical schema, or just an alias)
2. OR fix the validation rule in CaseController to use the correct connection
3. Verify login works, then verify genomics endpoints work
4. Check if other models reference a `clinical` connection

To investigate: `grep -rn "clinical" backend/config/database.php backend/app/` to find all references.

## REMAINING DEVELOPMENT WORK

### 1. Fix the clinical DB connection (BLOCKING — nothing works without this)
- Add `clinical` connection to config/database.php OR fix references to use the correct connection
- All 72 tables are in the default pgsql database; check if clinical schema separation was planned

### 2. OncoKB Response Parsing (deferred — low priority)
- backend/app/Services/Genomics/OncoKbService.php lines 49-52 have explicit TODO
- This was intentionally left as v1 stub — only do if time permits

### 3. GenomicsController stub endpoints (deferred)
- Upload endpoints (listUploads, storeUpload, showUpload) return stubs
- Criteria endpoints (listCriteria, storeCriterion, updateCriterion, destroyCriterion) return stubs
- These are Phase 1+ work, not blocking

### 4. End-to-End Verification
After fixing the DB connection:
- Login as admin@acumenus.net / superuser
- Navigate to a patient profile with genomic data
- Verify the Genomics tab renders all 4 sections
- Verify AI briefing generation works (requires Ollama running)
- Verify gene-drug interactions load
- Verify variant table with filtering works

## END-OF-SESSION CHECKLIST (MANDATORY)

Before closing:
1. Deploy frontend: cd frontend && npm run build && rm -rf ../backend/public/build && cp -r dist ../backend/public/build
2. Commit all changes with descriptive message
3. Push to origin
4. Verify http://localhost:8085 works (login, navigate)

## KEY FILES

- backend/config/database.php — NEEDS clinical connection
- backend/app/Http/Controllers/CaseController.php — has exists:clinical.patients validation
- backend/app/Http/Controllers/GenomicsController.php — genomics endpoints
- backend/app/Http/Controllers/AuthController.php — login/register
- backend/app/Services/AuthService.php — auth business logic
- backend/app/Services/Genomics/OncoKbService.php — has TODO
- backend/app/Services/RadiogenomicsService.php — refactored, DB-driven
- frontend/src/features/genomics/ — all frontend genomics code
- frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx — main tab
- ai/app/services/genomic_briefing.py — AI briefing service
- docs/superpowers/plans/2026-03-24-actionable-genomics-tab.md — full plan
- .claude/rules/auth-system.md — DO NOT MODIFY auth flow

## CREDENTIALS
- Aurora login: admin@acumenus.net / superuser
- DB: localhost:5432, user smudoshi, password acumenus, database aurora
