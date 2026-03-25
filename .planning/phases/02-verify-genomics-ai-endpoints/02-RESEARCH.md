# Phase 2: Verify Genomics & AI Endpoints - Research

**Researched:** 2026-03-25
**Domain:** Laravel genomics endpoints, FastAPI AI service, PostgreSQL clinical schema
**Confidence:** HIGH

## Summary

Phase 2 addresses three bug verification requirements (BUG-08, BUG-09, BUG-10) focused on ensuring genomics and AI service endpoints return meaningful data. The investigation reveals that all code paths are already implemented and functional -- the primary risk is that **seeded data may not exist** in the database (the `GeneDrugInteractionSeeder` is not called by `DatabaseSeeder` or `ClinicalDemoSeeder` and must be run explicitly), and that the **AI service may not be running** or Ollama may not be available.

The genomics controller (`GenomicsController.php`) has working `interactions()` and `stats()` methods that query real database models. The AI service's `genomic-briefing` endpoint is fully implemented with Ollama integration. The main verification work is: (1) ensure seed data exists, (2) confirm endpoints return expected responses, (3) confirm AI service connectivity through the Laravel proxy.

**Primary recommendation:** Run the GeneDrugInteractionSeeder and ClinicalDemoSeeder if not already done, then verify each endpoint with curl. For BUG-10, verify the AI FastAPI service is running and Ollama is accessible, accepting that the briefing endpoint gracefully handles Ollama unavailability.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BUG-08 | Verify `/api/genomics/interactions` returns seeded gene-drug data | GenomicsController::interactions() queries GeneDrugInteraction model on `clinical.gene_drug_interactions` table. Seeder has 43 records but is NOT in DatabaseSeeder -- must be run explicitly. |
| BUG-09 | Verify `/api/genomics/stats` returns variant statistics | GenomicsController::stats() queries GenomicVariant model counting total, pathogenic, and VUS. Requires genomic variants in DB (seeded via ClinicalDemoSeeder demo patients). |
| BUG-10 | Verify AI service `/decision-support/genomic-briefing` responds | FastAPI endpoint at `/api/ai/decision-support/genomic-briefing` is fully implemented. Frontend accesses via Laravel proxy at `/api/ai/decision-support/genomic-briefing`. Requires AI service running on port 8100 and Ollama with medgemma-q4 model. Endpoint gracefully degrades if Ollama is down. |
</phase_requirements>

## Standard Stack

### Core (Already in Place)
| Library | Version | Purpose | Notes |
|---------|---------|---------|-------|
| Laravel | 10+ | Backend API framework | GenomicsController handles all genomics routes |
| FastAPI | Current | AI service framework | Decision support router at /api/ai/decision-support/* |
| PostgreSQL 16 | 16 | Database with clinical schema | `clinical.gene_drug_interactions` and `clinical.genomic_variants` tables |
| Ollama | Latest | Local LLM inference | medgemma-q4:latest model for genomic briefings |
| httpx | Current | Async HTTP client in Python | Used by llm_utils.py to call Ollama |

### Supporting
| Library | Purpose | When Used |
|---------|---------|-----------|
| Sanctum | Auth tokens | All genomics routes require auth:sanctum middleware |
| ApiResponse helper | Consistent JSON responses | Used by GenomicsController and RadiogenomicsController |

## Architecture Patterns

### Endpoint Architecture

```
Frontend → /api/ai/decision-support/genomic-briefing (POST)
           ↓ (Laravel AiProxyController)
           → http://localhost:8100/api/ai/decision-support/genomic-briefing
           ↓ (FastAPI decision_support router)
           → genomic_briefing.py → call_ollama_json() → Ollama
```

```
Frontend → /api/genomics/interactions (GET)
           ↓ (Laravel GenomicsController::interactions)
           → GeneDrugInteraction::query() → clinical.gene_drug_interactions table
```

```
Frontend → /api/genomics/stats (GET)
           ↓ (Laravel GenomicsController::stats)
           → GenomicVariant::count() queries → clinical.genomic_variants table
```

### Key Data Models

**GeneDrugInteraction** (`clinical.gene_drug_interactions`):
- Connection: `pgsql` (default) with explicit table `clinical.gene_drug_interactions`
- Fields: gene, variant_pattern, drug, drug_class, relationship, evidence_level, indication, mechanism, source, source_url
- Seeder: `GeneDrugInteractionSeeder` with 43 records (requirement says 42 -- actual count is 43)
- **CRITICAL: Seeder is NOT called by DatabaseSeeder** -- must be run explicitly: `php artisan db:seed --class=GeneDrugInteractionSeeder`

**GenomicVariant** (`clinical.genomic_variants`):
- Connection: default `pgsql` with `$table = 'genomic_variants'` (resolves via search_path `app,clinical,public`)
- Fields: patient_id, gene, variant, variant_type, chromosome, position, clinical_significance, etc.
- Seeded per-patient by ClinicalDemoSeeder demo patients (12 patients with genomic data)

**GenomicBriefingRequest** (Pydantic model in AI service):
- Fields: patient_id, variants (list[VariantSummary]), drug_exposures (list[DrugExposureSummary]), interactions (list[InteractionSummary]), total_variant_count

### AI Service Configuration
- Base URL: `AI_SERVICE_URL` env var, defaults to `http://localhost:8100`
- Ollama URL: `ollama_base_url` defaults to `http://localhost:11434`
- Ollama model: `medgemma-q4:latest`
- Ollama timeout: 120 seconds
- AI proxy routes: `POST /api/ai/{path}` and `GET /api/ai/{path}` through `AiProxyController`

### Response Formats

**interactions endpoint** returns:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "gene": "BRAF",
      "variant_pattern": "*",
      "drug": "Vemurafenib",
      "drug_class": "BRAF inhibitor",
      "relationship": "sensitive",
      "evidence_level": "1A",
      "indication": "...",
      "mechanism": "...",
      "source": "oncokb"
    }
  ]
}
```

**stats endpoint** returns:
```json
{
  "success": true,
  "data": {
    "total_variants": 15,
    "uploads_count": 0,
    "pathogenic_count": 5,
    "vus_count": 3
  },
  "message": "Genomics stats retrieved"
}
```

**genomic-briefing endpoint** returns:
```json
{
  "briefing": "Clinical narrative text...",
  "generated_at": "2026-03-25T...",
  "variant_count": 15,
  "actionable_count": 5,
  "error": null
}
```

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Seeding check | Manual SQL queries | `php artisan db:seed --class=GeneDrugInteractionSeeder` | Seeder is idempotent (uses updateOrCreate) |
| AI service check | Complex health monitoring | Existing `/api/ai/health` endpoint + try/catch in briefing | Endpoint already returns graceful error on Ollama failure |
| Auth for testing | Token generation scripts | `php artisan tinker` with Sanctum token | Consistent with Phase 1 verification approach |

## Common Pitfalls

### Pitfall 1: GeneDrugInteractionSeeder Not in DatabaseSeeder
**What goes wrong:** Running `php artisan db:seed` does NOT seed gene-drug interactions -- only SuperuserSeeder runs
**Why it happens:** `GeneDrugInteractionSeeder` was created separately and never added to `DatabaseSeeder::$calls`
**How to avoid:** Run explicitly: `php artisan db:seed --class=GeneDrugInteractionSeeder`
**Warning signs:** `GET /api/genomics/interactions` returns `{"success": true, "data": []}` (empty array)

### Pitfall 2: GenomicVariant Table Resolution via search_path
**What goes wrong:** `GenomicVariant` model has `$table = 'genomic_variants'` without schema prefix
**Why it happens:** Model relies on pgsql connection's `search_path: 'app,clinical,public'` to find `clinical.genomic_variants`
**How to avoid:** This works correctly with current config. If database.php changes, this could break.
**Warning signs:** "relation genomic_variants does not exist" error

### Pitfall 3: Interaction Count Mismatch (42 vs 43)
**What goes wrong:** Requirement BUG-08 says "42 seeded gene-drug interaction records" but seeder contains 43 entries
**Why it happens:** Requirement may have been written before final seeder update
**How to avoid:** Verify actual count after seeding rather than hardcoding 42. Accept 43 as correct.
**Warning signs:** Test expecting exactly 42 records will fail

### Pitfall 4: AI Service Not Running
**What goes wrong:** `POST /api/ai/decision-support/genomic-briefing` returns 503 "AI service unavailable"
**Why it happens:** FastAPI service on port 8100 is not started, or Ollama is not running
**How to avoid:** Start AI service before testing. Accept graceful degradation (error field in response) if Ollama is down.
**Warning signs:** AiProxyController catches `ConnectionException` and returns 503

### Pitfall 5: ClinicalDemoSeeder Not Run (Empty stats)
**What goes wrong:** `GET /api/genomics/stats` returns all zeros
**Why it happens:** `ClinicalDemoSeeder` is also not in `DatabaseSeeder` -- must be run explicitly
**How to avoid:** Run `php artisan db:seed --class=ClinicalDemoSeeder` to seed 12 demo patients with genomic variants
**Warning signs:** total_variants: 0, pathogenic_count: 0, vus_count: 0

### Pitfall 6: Docker DNS for AI Proxy
**What goes wrong:** Laravel proxy to AI service fails with connection error
**Why it happens:** `AI_SERVICE_URL` defaults to `http://localhost:8100` which may not resolve correctly inside Docker
**How to avoid:** Set `AI_SERVICE_URL` in `.env` to correct host (e.g., `http://host.docker.internal:8100` or the container name)
**Warning signs:** 503 response from `/api/ai/*` routes

## Code Examples

### Verifying Gene-Drug Interactions via curl
```bash
# Get auth token first
TOKEN=$(curl -s -X POST http://localhost:8085/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@acumenus.net","password":"superuser"}' | jq -r '.data.access_token // .access_token')

# BUG-08: Check interactions
curl -s http://localhost:8085/api/genomics/interactions \
  -H "Authorization: Bearer $TOKEN" | jq '.data | length'
# Expected: 43 (or 42 per requirement)

# BUG-09: Check stats
curl -s http://localhost:8085/api/genomics/stats \
  -H "Authorization: Bearer $TOKEN" | jq '.data'
# Expected: total_variants > 0, pathogenic_count > 0, vus_count > 0

# BUG-10: Check genomic briefing (via AI proxy)
curl -s -X POST http://localhost:8085/api/ai/decision-support/genomic-briefing \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "patient_id": 1,
    "variants": [{"gene":"BRAF","variant":"V600E","classification":"pathogenic","evidence_level":"1A","therapies":["Vemurafenib"]}],
    "drug_exposures": [],
    "interactions": [{"gene":"BRAF","drug":"Vemurafenib","relationship":"sensitive","evidence_level":"1A"}],
    "total_variant_count": 5
  }' | jq '.'
# Expected: briefing field with narrative text, no error field
```

### Seeding Data (if needed)
```bash
# Run from backend directory inside Docker or locally
cd /home/smudoshi/Github/Aurora/backend

# Seed gene-drug interactions (43 records, idempotent)
php artisan db:seed --class=GeneDrugInteractionSeeder

# Seed demo patients with genomic variants (12 patients, idempotent)
php artisan db:seed --class=ClinicalDemoSeeder
```

### Direct AI Service Test (bypassing Laravel proxy)
```bash
# Test AI service health
curl -s http://localhost:8100/api/ai/health | jq '.'

# Test genomic briefing directly
curl -s -X POST http://localhost:8100/api/ai/decision-support/genomic-briefing \
  -H 'Content-Type: application/json' \
  -d '{
    "patient_id": 1,
    "variants": [{"gene":"BRAF","variant":"V600E","classification":"pathogenic"}],
    "drug_exposures": [],
    "interactions": [],
    "total_variant_count": 5
  }' | jq '.'
```

## State of the Art

| Component | Current State | Notes |
|-----------|---------------|-------|
| GenomicsController::interactions() | Fully implemented | Queries real DB, supports gene/evidence_level/relationship/source filters |
| GenomicsController::stats() | Fully implemented | Counts total, pathogenic, VUS variants. uploads_count hardcoded to 0 |
| AI genomic-briefing endpoint | Fully implemented | Ollama-powered narrative generation with graceful fallback |
| GeneDrugInteractionSeeder | 43 records ready | Must be run explicitly (not in DatabaseSeeder) |
| AiProxyController | Working proxy | POST and GET, 120s timeout, passes user context headers |

## Open Questions

1. **Exact interaction count: 42 or 43?**
   - What we know: Seeder contains 43 `'gene' =>` entries. Requirement says 42.
   - Recommendation: Verify actual DB count after seeding. Update requirement if 43 is correct. Use >= 42 check rather than exact match.

2. **Is ClinicalDemoSeeder already run in current environment?**
   - What we know: It must be run explicitly. Phase 1 did not run it.
   - Recommendation: Check `clinical.genomic_variants` count before and after. Run if empty.

3. **Is Ollama running with medgemma-q4 model?**
   - What we know: AI service config expects `medgemma-q4:latest` at `localhost:11434`
   - Recommendation: Check `ollama list` and `curl http://localhost:11434/api/tags`. If model not available, the briefing endpoint returns error gracefully -- document this behavior as acceptable for BUG-10.

4. **AI service port / connectivity**
   - What we know: Default is `http://localhost:8100`, configured via `AI_SERVICE_URL` env var
   - Recommendation: Verify service is running before testing BUG-10. If not running, start it.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest (PHP), pytest (Python) -- not yet configured for this phase |
| Config file | None for this phase (Phase 3+ sets up test infrastructure) |
| Quick run command | `curl` verification scripts (manual) |
| Full suite command | N/A -- this phase is verification, not automated testing |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| BUG-08 | interactions returns seeded data | manual-smoke | `curl /api/genomics/interactions` with auth | N/A -- verification script |
| BUG-09 | stats returns variant statistics | manual-smoke | `curl /api/genomics/stats` with auth | N/A -- verification script |
| BUG-10 | genomic-briefing returns narrative | manual-smoke | `curl POST /api/ai/decision-support/genomic-briefing` | N/A -- verification script |

### Sampling Rate
- **Per task commit:** Run verification curl commands
- **Per wave merge:** All 3 endpoints verified returning expected data
- **Phase gate:** All 3 BUG requirements pass

### Wave 0 Gaps
- [ ] Ensure `GeneDrugInteractionSeeder` has been run (43 records in `clinical.gene_drug_interactions`)
- [ ] Ensure `ClinicalDemoSeeder` has been run (genomic variants exist for demo patients)
- [ ] Ensure AI FastAPI service is running on port 8100
- [ ] Ensure Ollama is running with medgemma-q4 model (or accept graceful degradation)

## Sources

### Primary (HIGH confidence)
- `backend/app/Http/Controllers/GenomicsController.php` -- interactions() at line 395, stats() at line 25
- `backend/app/Models/Clinical/GeneDrugInteraction.php` -- model with `clinical.gene_drug_interactions` table
- `backend/app/Models/Clinical/GenomicVariant.php` -- model with `genomic_variants` table (search_path resolution)
- `backend/database/seeders/GeneDrugInteractionSeeder.php` -- 43 records, uses updateOrCreate
- `backend/database/seeders/DatabaseSeeder.php` -- confirms GeneDrugInteractionSeeder is NOT auto-called
- `ai/app/routers/decision_support.py` -- genomic_briefing_endpoint at line 146
- `ai/app/services/genomic_briefing.py` -- generate_briefing with Ollama integration
- `ai/app/models/decision_support.py` -- GenomicBriefingRequest/Response Pydantic models
- `ai/app/services/llm_utils.py` -- call_ollama_json helper
- `ai/app/config.py` -- Ollama config (medgemma-q4, port 11434, 120s timeout)
- `backend/app/Http/Controllers/AiProxyController.php` -- proxy to AI service
- `backend/config/database.php` -- search_path includes clinical schema
- `backend/routes/api.php` -- route definitions for genomics and AI proxy

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all code inspected directly, models and controllers read in full
- Architecture: HIGH -- proxy pattern, DB schema, search_path all verified in source
- Pitfalls: HIGH -- identified concrete issues (seeder not in DatabaseSeeder, count mismatch, service availability)

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable -- this is verification of existing code, not new library research)
