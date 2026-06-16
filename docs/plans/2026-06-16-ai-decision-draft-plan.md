# AI Decision Draft + Capture (Evidence-Grounded) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** The first slice of Aurora's AI/evidence differentiation — an **agentic, evidence-grounded MDT decision draft**: Abby drafts a structured clinical Decision for a case (recommendation, rationale, decision_type, confidence, and cited evidence retrieved live via **BioMCP**), the team reviews/edits it, and on confirm it's persisted to `app.decisions` with full AI-attribution + timing instrumentation (toward the prep-time/decision-quality study).

**Architecture:** Three layers, reusing Aurora's existing AI stack.
- **`ai/` FastAPI (Python):** a `BioMcpService` (live evidence retrieval via the `biomcp-python` package — articles/trials/variants) + a `draft-decision` endpoint that gathers the patient/case snapshot (reusing `copilot._fetch_patient_summary_data`), runs the **`CloudSafetyFilter` PHI guard**, calls the production **`ClaudeClient`** (cost-tracked), and returns a structured, evidence-cited draft.
- **Laravel:** an additive migration adding AI-attribution columns to `app.decisions`; a `POST /api/cases/{case}/decisions/draft` proxy to the `ai/` endpoint (returns the draft, does NOT persist); and an extension of the existing `DecisionController::store` to persist the AI-attribution fields on confirm. Lightweight timing instrumentation.
- **React:** a "Draft with Abby" flow on the case decisions UI — request draft → editable draft + an evidence-citations panel → "Confirm & record" → persists via the existing decision-create path with `ai_generated=true`.

**Tech Stack:** Python 3.12 FastAPI + `biomcp-python` + the existing `anthropic` ClaudeClient; Laravel 11 / PHP 8.4 / Pest; React 19 + TS + Vite + TanStack Query + Vitest/MSW.

---

## Scope note — first slice of the AI/evidence differentiation

This is slice 1 of the differentiation initiative (the user's chosen 6-month emphasis + the market moat). It delivers a complete, shippable, measurable decision-draft capability. Deliberately deferred to later slices: the live ambient-discussion→decision capture (this slice drafts from structured patient/case data, not a live transcript), the full Claude-Agent-SDK tool-use loop (this slice uses deterministic retrieve-then-draft RAG, which is more controllable for v1), streaming to the board, and the formal decision-quality study (this slice lays the instrumentation).

**Provider decision (locked):** Claude (cloud) via the existing `ai/app/routing/ClaudeClient`, gated by the `CloudSafetyFilter` PHI guard + `cost_tracker` budget. No new provider wiring.

**Reuse map (verified — do not rebuild):**
- `ai/app/routing/claude_client.py` — `class ClaudeClient` with `chat(...)` (read its exact signature at line ~90; it returns a `ClaudeResponse`). Cost-tracked.
- `ai/app/routing/cloud_safety.py` — `CloudSafetyFilter.is_cloud_safe(piece)` / `filter_for_cloud(pieces)` (PHI guard, operates on `ContextPiece`).
- `ai/app/routers/copilot.py` — `_fetch_patient_summary_data(patient_id) -> dict` (SQLAlchemy `session.execute` snapshot of clinical.{patients,conditions,medications,procedures,measurements,observations,notes}). Reuse verbatim for case context.
- `ai/app/config.py` — `claude_api_key`, `claude_model`, `claude_max_tokens`, `phi_detection_enabled`, cloud budget settings.
- Laravel: `App\Models\Decision` (`app.decisions`: `case_id, session_id, patient_id, proposed_by, decision_type, recommendation, rationale, guideline_reference, status, finalized_at, finalized_by, urgency, record_refs[]`; relations `clinicalCase/patient/session/proposer/votes/followUps`). `DecisionController::store(Request, int $case)` validates `decision_type ∈ {treatment_recommendation,diagnostic_workup,referral,monitoring_plan,palliative,other}`, `recommendation` required. Routes: `POST cases/{case}/decisions` (store), etc.
- `App\Http\Controllers\AbbyController` — the proxy pattern: `Http::...->post(config('services.ai.base_url').'/api/ai/abby/...')`. `config('services.ai.base_url')` (default `http://localhost:8100`). `ApiResponse::success/error`.
- `App\Models\ClinicalCase` (`patient_id`, `clinical_question`, `summary`, `specialty`, `case_type`) + `ClinicalPatient` relations (`genomicVariants`, `conditions`, …) for case context.
- Frontend `features/abby-ai/` (api/types/store) + the decisions UI (read `features/**/Decision*` + `DecisionDashboardPage` + the case-detail decisions section to find where to embed the button).

**Environment guardrails (every task):**
- Laravel tests auto-target `aurora_test` via `tests/TestCase::createApplication()` (now redirects ALL pgsql connections) — run `php artisan test …` normally; NEVER pass `DB_DATABASE`/migrate/seed the default connection (it's the live `aurora` DB). New migration: validate against test DB only with `-e DB_DATABASE=aurora_test … migrate --force`.
- **www-data perms:** after creating PHP/Python files, `chmod -R o+rX backend/app ai/app` so php-fpm/uvicorn can read them (Write-tool umask is 660 → 500s in the served app; tests pass anyway). For PHP serving changes also `composer dump-autoload -o` + `docker compose restart php`.
- `ai/` Python: add `biomcp-python` to `ai/requirements.txt`; install in the ai container/venv. Python type hints on everything; Pydantic v2 models. Run the ai service's test runner (pytest) for ai/ tasks.
- Run Pint after PHP edits; frontend in the `node` container, verify `tsc --noEmit` AND `vite build` AND `vitest run`; no `npm install`; named exports (pages default); no `any`; no `zod`.
- Branch `v2/phase-0-scaffold`; commit via explicit literal pathspec (`git commit -m … -- <paths>`, options before `--`); `git add` new files explicitly; NEVER `git add -A`/`.`/`-p`/`reset`/`checkout`; a concurrent session has unrelated uncommitted files — never touch them.
- Secrets: `CLAUDE_API_KEY` is already an env var the ai/ service reads — never print/commit it. BioMCP uses public APIs (no key for v1).

---

## Reference — BioMCP + the draft contract

**BioMCP** (`biomcp-python`, genomoncology): retrieval functions for Articles (PubMed/PubTator3), Trials (ClinicalTrials.gov), Variants. Confirm exact import paths from the installed package (`python -c "import biomcp; help(biomcp)"`); the CLI equivalents are `biomcp article search --gene BRAF --disease Melanoma`, `biomcp trial search --condition "..." --phase PHASE3`, `biomcp variant search --gene TP53 --significance pathogenic`. The package is async. Always wrap calls in try/except + a per-call timeout; degrade to `[]`.

**Structured draft contract** (the `ai/` endpoint returns this; Laravel + frontend mirror it):
```json
{
  "decision_type": "treatment_recommendation",
  "recommendation": "string",
  "rationale": "string (cites the evidence)",
  "confidence": 0.0,
  "guideline_references": ["string"],
  "sources": [
    {"type": "article|trial|variant", "id": "PMID:... / NCT... / rsID", "title": "string", "url": "string"}
  ],
  "model": "claude-...",
  "evidence_counts": {"articles": 0, "trials": 0, "variants": 0}
}
```

---

## File structure

**Phase A — `ai/` FastAPI:**
- Create `ai/app/services/biomcp_service.py` — `BioMcpService` (evidence retrieval)
- Create `ai/app/routers/decisions.py` — `POST /api/ai/abby/draft-decision`
- Modify `ai/app/main.py` (register the router), `ai/requirements.txt` (+`biomcp-python`), `ai/app/config.py` (+`biomcp_enabled`)
- Test: `ai/tests/test_biomcp_service.py`, `ai/tests/test_draft_decision.py`

**Phase B — Laravel:**
- Create `backend/database/migrations/2026_06_16_020001_add_ai_attribution_to_decisions_table.php`
- Create `backend/app/Http/Controllers/AiDecisionController.php` — `draft(int $case)`
- Modify `backend/app/Models/Decision.php` (fillable+casts), `backend/app/Http/Controllers/DecisionController.php` (`store` accepts AI fields), `backend/routes/api.php`, `backend/config/services.php`/`.env.example` if needed
- Test: `backend/tests/Feature/Api/AiDecisionDraftTest.php`

**Phase C — React:**
- Create `frontend/src/features/abby-ai/api/decisionDraftApi.ts`, `hooks/useDecisionDraft.ts`, `components/AbbyDecisionDraft.tsx`, `types/decisionDraft.ts`
- Modify the case decisions UI to mount the draft component + `frontend/src/test/mocks/handlers.ts`
- Test: `components/__tests__/AbbyDecisionDraft.test.tsx`

---

# PHASE A — ai/ FastAPI (evidence retrieval + Claude draft)

### Task A1: `BioMcpService` — live evidence retrieval

**Files:** Create `ai/app/services/biomcp_service.py`; modify `ai/requirements.txt` (add `biomcp-python`), `ai/app/config.py` (add `biomcp_enabled: bool = env("BIOMCP_ENABLED", True)`). Test: `ai/tests/test_biomcp_service.py`.

- [ ] **Step 1: Add the dependency** — append `biomcp-python` to `ai/requirements.txt`; install (`pip install biomcp-python` in the ai venv/container). Confirm import path: `python -c "import biomcp; print(dir(biomcp))"` and locate the article/trial/variant search functions (e.g. `from biomcp.articles.search import search_articles` — verify the actual names; they may be `article_searcher`-style).

- [ ] **Step 2: Write the failing test** `ai/tests/test_biomcp_service.py` — monkeypatch the biomcp search functions to return canned results, assert `BioMcpService.gather(genes=["BRAF"], conditions=["Melanoma"], drugs=[])` returns `{"articles": [...], "trials": [...], "variants": [...]}` with normalized `{type,id,title,url}` items, and that a raised exception in a searcher degrades that key to `[]` (never raises).

- [ ] **Step 3: Implement** `BioMcpService` with `async def gather(self, genes, conditions, drugs, max_per_source=5) -> dict`:
  - If `not config.biomcp_enabled`: return `{"articles": [], "trials": [], "variants": []}`.
  - articles: for the top condition+gene, call the biomcp article search (await, `asyncio.wait_for(..., timeout=15)`), map to `{"type":"article","id":"PMID:"+pmid,"title":...,"url":"https://pubmed.ncbi.nlm.nih.gov/"+pmid}`.
  - trials: biomcp trial search by condition (+ recruiting/active filter if available), map to `{"type":"trial","id":nct,"title":...,"url":"https://clinicaltrials.gov/study/"+nct}`.
  - variants: for each gene, biomcp variant search (pathogenic/likely-pathogenic), map to `{"type":"variant","id":...,"title":gene+" "+hgvs,"url":...}`.
  - Each block wrapped in `try/except Exception as e: logger.warning(...); results[key]=[]`. Cap each to `max_per_source`. Return the dict. NEVER raise.

- [ ] **Step 4:** Run the ai test suite for this file (pytest), → PASS. Commit (`ai/app/services/biomcp_service.py`, `ai/tests/test_biomcp_service.py`, `ai/requirements.txt`, `ai/app/config.py`).

---

### Task A2: `draft-decision` endpoint (Claude, PHI-safe, evidence-grounded)

**Files:** Create `ai/app/routers/decisions.py`; modify `ai/app/main.py` (include the router). Test: `ai/tests/test_draft_decision.py`.

- [ ] **Step 1: Write the failing test** — monkeypatch `_fetch_patient_summary_data` → a canned snapshot, `BioMcpService.gather` → canned evidence, and `ClaudeClient.chat` → a canned `ClaudeResponse` whose text is the JSON draft contract. POST `/api/ai/abby/draft-decision` with `{"case_id": 1, "patient_id": 5, "clinical_question": "..."}`; assert 200 + the response matches the draft contract (decision_type, recommendation, rationale, confidence, sources[], model, evidence_counts). Assert the PHI filter was applied (the prompt sent to Claude contains no raw MRN/name — verify `CloudSafetyFilter` is invoked).

- [ ] **Step 2: Implement** the router `POST /api/ai/abby/draft-decision`:
  - Pydantic request `DraftDecisionRequest{case_id:int, patient_id:int, clinical_question:str|None, decision_type:str|None}`.
  - Gather: `snapshot = _fetch_patient_summary_data(patient_id)`; derive `genes` from the snapshot's genomic variants, `conditions` from conditions, `drugs` from medications.
  - `evidence = await BioMcpService().gather(genes, conditions, drugs)`.
  - Build the context, run it through `CloudSafetyFilter.filter_for_cloud(...)` (PHI guard) before composing the Claude prompt — the prompt must instruct Claude to return ONLY JSON matching the draft contract, grounding `rationale` in the supplied evidence and citing `sources` by id.
  - `resp = ClaudeClient(...).chat(messages=[...], max_tokens=config.claude_max_tokens)` (use the real signature from claude_client.py). Parse the JSON from `resp.text` (robust: extract the first `{...}` block; on parse failure return a 502 with a clear message).
  - Attach `model = config.claude_model` and `evidence_counts`. Return the draft dict (FastAPI JSONResponse). Wrap Claude/parse in try/except → 502 `{"detail": "..."}` (never 500-stacktrace).
  - Register the router in `ai/app/main.py`.

- [ ] **Step 3:** Run pytest → PASS. `chmod -R o+rX ai/app`. Commit (`ai/app/routers/decisions.py`, `ai/app/main.py`, `ai/tests/test_draft_decision.py`).

---

# PHASE B — Laravel (AI-attribution + draft proxy + capture)

### Task B1: Migration + Decision model AI-attribution fields

**Files:** Create `backend/database/migrations/2026_06_16_020001_add_ai_attribution_to_decisions_table.php`; modify `backend/app/Models/Decision.php`. Test: extend `FactorySmokeTest` (a Decision factory create with AI fields) if a `DecisionFactory` exists; else assert via a unit test.

- [ ] **Step 1: Migration** (additive, `app.decisions`):
```php
Schema::table('app.decisions', function (Blueprint $table) {
    $table->boolean('ai_generated')->default(false);
    $table->string('ai_model')->nullable();
    $table->decimal('ai_confidence', 4, 3)->nullable();   // 0.000–1.000
    $table->text('ai_rationale')->nullable();
    $table->jsonb('ai_sources')->nullable();
    $table->timestamp('ai_drafted_at')->nullable();
});
```
Validate against test DB only: `docker compose exec -T -e DB_DATABASE=aurora_test php sh -c "cd /var/www/html && php artisan migrate --force --path=database/migrations/2026_06_16_020001_add_ai_attribution_to_decisions_table.php"`.

- [ ] **Step 2:** Add the 6 columns to `Decision::$fillable`; cast `ai_generated => 'boolean'`, `ai_confidence => 'float'`, `ai_sources => 'array'`, `ai_drafted_at => 'datetime'`.

- [ ] **Step 3:** Run a quick model test (create a Decision with the AI fields, assert persisted+cast). Pint. Commit.

---

### Task B2: Draft proxy endpoint

**Files:** Create `backend/app/Http/Controllers/AiDecisionController.php`; modify `backend/routes/api.php`. Test: `backend/tests/Feature/Api/AiDecisionDraftTest.php`.

- [ ] **Step 1: Write the failing feature test** — seed superuser; `Http::fake([config('services.ai.base_url').'/*' => Http::response(<draft contract JSON>, 200)])`; create a `ClinicalCase` (with patient); `actingAs($user,'sanctum')->postJson("/api/cases/{$case->id}/decisions/draft")` → 200 + `assertJsonPath('data.recommendation', ...)` + `data.sources` present. Also assert 401 unauthenticated (with `Accept: application/json`).

- [ ] **Step 2: Implement** `AiDecisionController::draft(int $case)`:
  - `$c = ClinicalCase::findOrFail($case);`
  - `Http::timeout(60)->post(config('services.ai.base_url').'/api/ai/abby/draft-decision', ['case_id'=>$c->id,'patient_id'=>$c->patient_id,'clinical_question'=>$c->clinical_question]);`
  - on success return `ApiResponse::success($response->json())`; on failure `ApiResponse::error('Decision draft unavailable', 502)`. Wrap in try/catch.
- [ ] **Step 3: Route** inside the `auth:sanctum` group: `Route::post('cases/{case}/decisions/draft', [\App\Http\Controllers\AiDecisionController::class, 'draft']);`
- [ ] **Step 4:** Run → PASS. Pint. Commit (controller, routes/api.php, test).

---

### Task B3: Capture — persist AI-attribution on confirm

**Files:** Modify `backend/app/Http/Controllers/DecisionController.php` (`store`). Test: add a case to `AiDecisionDraftTest` (or a DecisionController test).

- [ ] **Step 1: Write the failing test** — POST `/api/cases/{case}/decisions` with `{decision_type, recommendation, rationale, ai_generated:true, ai_model:"claude-x", ai_confidence:0.82, ai_rationale:"...", ai_sources:[{type:"article",id:"PMID:1",title:"t",url:"u"}]}` → 201; assert the persisted Decision has `ai_generated=true` + `ai_sources` count 1 + `ai_drafted_at` set.
- [ ] **Step 2: Implement** — extend `store`'s `$request->validate([...])` with: `ai_generated => 'sometimes|boolean'`, `ai_model => 'nullable|string'`, `ai_confidence => 'nullable|numeric|between:0,1'`, `ai_rationale => 'nullable|string'`, `ai_sources => 'nullable|array'`, `ai_sources.*.id => 'required_with:ai_sources|string'`. When creating the Decision, include these fields and set `ai_drafted_at => now()` when `ai_generated` is true. Keep all existing behavior (additions only — DecisionController is not in a protected-file list, but make minimal targeted edits).
- [ ] **Step 3:** Run → PASS (existing decision tests still green). Pint. Commit.

---

# PHASE C — React (Draft-with-Abby flow)

### Task C1: Data layer (types + api + hooks)

**Files:** Create `frontend/src/features/abby-ai/types/decisionDraft.ts`, `api/decisionDraftApi.ts`, `hooks/useDecisionDraft.ts`. Test: `hooks/__tests__/useDecisionDraft.test.ts`.

- [ ] **Types** `DecisionDraft { decision_type: string; recommendation: string; rationale: string; confidence: number; guideline_references: string[]; sources: DraftSource[]; model: string; evidence_counts: { articles: number; trials: number; variants: number } }` + `DraftSource { type: string; id: string; title: string; url: string }`.
- [ ] **api** `draftDecision(caseId): Promise<DecisionDraft>` (POST `/cases/${caseId}/decisions/draft`, `data.data ?? data`); reuse the existing decisions-create api if present, else `createDecision(caseId, payload)` (POST `/cases/${caseId}/decisions`).
- [ ] **hooks** `useDraftDecision(caseId)` (mutation) + `useCreateDecision(caseId)` (mutation → invalidate the case decisions query). Write a hook test (MSW) for `useDraftDecision`.

### Task C2: `AbbyDecisionDraft` component + embed

**Files:** Create `frontend/src/features/abby-ai/components/AbbyDecisionDraft.tsx`; modify the case decisions UI (read `features/**/Decision*`/`DecisionDashboardPage`/the case-detail decisions section to find the mount point) + `frontend/src/test/mocks/handlers.ts`. Test: `components/__tests__/AbbyDecisionDraft.test.tsx`.

- [ ] **Step 1: Write the failing test** — MSW `POST /api/cases/3/decisions/draft` → a draft (recommendation, rationale, confidence 0.82, 2 sources incl. a PubMed url), `POST /api/cases/3/decisions` → 201. Render `<AbbyDecisionDraft caseId={3} />`. Click "Draft with Abby" → assert the recommendation + rationale render, the confidence shows as `82%`, and the sources render as links (one to pubmed). Edit the recommendation textarea, click "Confirm & record" → assert the create POST fires with `ai_generated:true` + the sources.
- [ ] **Step 2: Implement** `AbbyDecisionDraft` (named export): a "Draft with Abby" button → `useDraftDecision`; on success show an editable form (decision_type select, recommendation textarea prefilled, rationale textarea, a read-only confidence chip `{Math.round(confidence*100)}%`, and an **Evidence** list rendering `sources` as external links grouped by type with the `evidence_counts`); a "Confirm & record" button → `useCreateDecision` with `{decision_type, recommendation, rationale, ai_generated:true, ai_model:model, ai_confidence:confidence, ai_rationale:rationale, ai_sources:sources}`; loading/empty/error states; mirror the token classes used by `ReanalysisAlertsPanel`/`MmeMatchesPanel`. Add a clear "AI-drafted — review before recording" disclaimer (non-device CDS: human decides).
- [ ] **Step 3: Embed** the component in the case decisions section (the case detail page's decisions area). Add default MSW handlers for the two endpoints to `handlers.ts`.
- [ ] **Step 4: Verify** in the node container: `npx vitest run src/features/abby-ai && npx tsc --noEmit && npx vite build` → green. Commit.

---

## Self-Review

**1. Spec coverage:**
- Evidence retrieval (BioMCP articles/trials/variants, degrade-safe) → A1. ✓
- Agentic draft (Claude, PHI-safe via CloudSafetyFilter, evidence-grounded, structured contract) → A2. ✓
- AI-attribution persistence (`ai_generated/ai_model/ai_confidence/ai_rationale/ai_sources/ai_drafted_at`) → B1, B3. ✓
- Draft proxy endpoint → B2. ✓
- Human-in-the-loop review + capture UI with evidence citations + non-device-CDS disclaimer → C2. ✓
- Instrumentation: `ai_drafted_at` + `ai_generated` flag enable later prep-time/quality measurement. ✓ (Live ambient capture + the formal study are explicitly later slices.)

**2. Placeholder scan:** each task has the failing test + implementation or precise signatures. BioMCP import names + ClaudeClient.chat signature are flagged "confirm from the installed package/file" because they're external/in-repo APIs the implementer must read exactly — not placeholders for logic.

**3. Type consistency:** the draft contract JSON (decision_type, recommendation, rationale, confidence, sources[{type,id,title,url}], model, evidence_counts) is identical across A2 (produces), B2 (proxies), B3 (persists ai_sources subset), and C (DecisionDraft type). `ai_confidence` is 0–1 everywhere.

**Risk points for focused review:** A2 (PHI guard actually applied before the cloud call + robust JSON parse), B3 (validation/persistence of AI fields without breaking existing decision create), C2 (the confirm payload shape).

## Execution Handoff

Two options:
1. **Subagent-Driven (recommended)** — fresh subagent per task; review at A2 (PHI/parse), B3 (capture), C2 (confirm payload); continuous execution. Phase A is Python (ai/ pytest), B is PHP (Pest), C is React (Vitest).
2. **Inline** — batch with checkpoints.

Phases are sequential (C depends on B depends on A's contract). The draft contract is the integration seam — keep it identical across layers.
