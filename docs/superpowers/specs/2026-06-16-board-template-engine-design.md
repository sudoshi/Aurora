# Board-Template Engine v1 — Design

**Date:** 2026-06-16
**Phase:** A (horizontal core / keystone) — first slice
**Status:** Approved (design); pending implementation plan
**Strategy ref:** `docs/plans/2026-06-14-aurora-complex-care-strategy.md` §2 (unifying abstraction), §3.A (keystone)

---

## 1. Goal

Generalize Aurora's oncology-shaped `ClinicalCase` into a **configurable board-template
engine** so a single platform can run a tumor board, a rare-disease MDT, a heart team,
and a complex-medical review — each carrying its own structured data schema, candidacy
rubric, decision schema, agenda, and (where the time model demands it) an explicit state
machine. This is the keystone the three new population packs depend on.

A board template is **data, not code**: one `app.board_templates` row per board type.
The horizontal services (decisions, sessions, discussions, risk, AI, interop) stay shared;
each pack specializes them through its template.

## 2. The unifying abstraction (from strategy §2)

Populations differ by the **time model** of the decision, not by the underlying spine:

| Population | `time_model` | State machine in v1 |
|---|---|---|
| Cancer | `episodic` | none (stateless, as today) |
| Complex surgical | `episode_of_care` | **wired** (referred → … → recovery) |
| Complex medical | `longitudinal` | none (until pack) |
| Rare / undiagnosed | `diagnostic_odyssey` | **wired** (referral → … → reanalysis) |

Confirmed forks:
- **Validation = soft.** `data_schema` validation produces warnings and still persists.
  Lowest migration risk on existing oncology cases; packs evolve schemas freely.
- **State machine = generic engine, concrete machines wired only for rare-disease and
  surgical** in v1. Oncology and complex-medical keep `state_machine: null` (stateless)
  until their packs land — avoids speculative FSMs.

## 3. Architecture

```
                 ┌─────────────────────────┐
 create case ──► │  BoardTemplateService   │  resolve(key) → template
                 │  validate(tpl, data)    │  → [warnings]  (soft, never rejects)
                 └───────────┬─────────────┘
                             │
                 ┌───────────▼─────────────┐
                 │  CaseStateMachine       │  initialState(tpl)
                 │  (no-op when            │  canTransition / transition
                 │   state_machine is null)│
                 └───────────┬─────────────┘
                             │
                 ┌───────────▼─────────────┐
                 │  ClinicalCase           │  + board_template_id
                 │                         │  + state, + structured_data
                 └─────────────────────────┘
   Decisions / sessions / discussions hang off the now template-typed case, unchanged.
```

Existing oncology cases backfill to a seeded `tumor_board` template whose `data_schema`
mirrors today's fields, so current behaviour is preserved exactly (additive only).

## 3a. Reconciliation with existing code (DRY)

Exploration of the codebase found a **primitive template system already exists** and must
be *extended*, not duplicated:

- **`app.case_templates`** (model `App\Models\CaseTemplate`, `CaseTemplateController`,
  routes `GET /case-templates` + `GET /case-templates/{slug}`, seeder
  `SpecialtyTemplateSeeder`) — already holds `name`, `slug`, `specialty`, `case_type`,
  `description`, `clinical_question_prompt`, `recommended_tabs`, `decision_types`,
  `guideline_sets`, `default_team_roles`. The **4 board types are already seeded** with
  slugs `oncology-tumor-board`, `rare-disease-diagnostic-odyssey`,
  `complex-surgical-planning`, `complex-medical-case-review`.
- **`app.cases`** (model `App\Models\ClinicalCase`) already has `case_type`, `specialty`,
  and `status` — but **no FK to a template** (templates are currently an unbound UI scaffold).

**Therefore the engine =**
1. **Extend `app.case_templates`** with the engine fields (`time_model`, `data_schema`,
   `candidacy_rubric`, `agenda`, `state_machine`, `is_active`). Reuse existing
   `decision_types` as the decision schema — do **not** add a `decision_schema` column.
2. **Bind `app.cases`** to a template (`template_id`) and add `state` + `structured_data`.
3. Reuse `CaseTemplate`, `CaseTemplateController`, and `SpecialtyTemplateSeeder` — extend
   the seeder's existing 4 rows with the engine fields (idempotent `updateOrInsert` by slug).

The conceptual design (soft validation, generic engine, rare+surgical FSMs, 4 templates,
backward-compat) is unchanged; only the physical tables/models change from "new" to "extend."

## 4. Data model

> Reconciled: `board_templates` → **extend `app.case_templates`**; `clinical_cases` →
> **extend `app.cases`**; `decision_schema` → reuse existing `decision_types`.

### 4.1 `app.case_templates` — columns ADDED (engine fields only)

Existing columns reused as-is: `id`, `name`, `slug` (unique), `specialty`, `case_type`,
`description`, `clinical_question_prompt`, `recommended_tabs`, **`decision_types`** (= the
decision schema), `guideline_sets`, `default_team_roles`, timestamps.

| Column ADDED | Type | Notes |
|---|---|---|
| `time_model` | varchar + CHECK | `episodic \| episode_of_care \| longitudinal \| diagnostic_odyssey` |
| `data_schema` | jsonb default `'[]'` | `[{key,label,type,required,options?}]` |
| `candidacy_rubric` | jsonb nullable | `[{key,label,required}]` clearance items |
| `agenda` | jsonb default `'[]'` | default agenda sections (array of strings) |
| `state_machine` | jsonb nullable | `{initial, states:[...], transitions:[{from,to,event}]}` |
| `is_active` | boolean default true | |

### 4.2 `app.cases` — columns ADDED (additive, all nullable/defaulted)

| Column ADDED | Type | Notes |
|---|---|---|
| `template_id` | bigint FK → `app.case_templates.id` nullable, `onDelete('set null')` | backfilled by matching `case_type`/`specialty` |
| `state` | varchar nullable | set from `state_machine.initial` when present; else null |
| `structured_data` | jsonb default `'{}'` | validated softly against template `data_schema` |

> Migration is additive and reversible. **Backfill** maps each existing case to a template
> by `case_type` → slug:
> `tumor_board`→`oncology-tumor-board`, `surgical_review`/`surgical_planning`→`complex-surgical-planning`,
> `rare_disease`/`diagnostic_odyssey`→`rare-disease-diagnostic-odyssey`,
> `medical_complex`/`medical_review`→`complex-medical-case-review`; anything else →
> `oncology-tumor-board`. `state` left null for stateless templates.

### 4.3 Seeds — extend the existing 4 rows (idempotent `updateOrInsert` by `slug`)

`SpecialtyTemplateSeeder` already seeds these 4 rows; the seeder is extended to set the
engine fields on each (re-runnable):

- **`oncology-tumor-board`** — `time_model: episodic`, `state_machine: null`. `data_schema`
  mirrors current oncology case fields (stage, histology, key biomarkers). Backward-compat anchor.
- **`rare-disease-diagnostic-odyssey`** — `time_model: diagnostic_odyssey`. FSM:
  `referral → deep_phenotyping → testing → mdt_review → matchmaking →
  {diagnosed | undiagnosed} → reanalysis`. Reuses Plan 4 reanalysis-loop semantics.
- **`complex-surgical-planning`** — `time_model: episode_of_care`. FSM:
  `referred → workup → optimization → decision → procedure → recovery → closed`.
  `candidacy_rubric` carries multi-clearance items (e.g. cardiology, anesthesia, frailty).
- **`complex-medical-case-review`** — `time_model: longitudinal`, `state_machine: null`.
  `data_schema` = persistent problem list + goals-of-care axis.

## 5. Components

| Unit | Responsibility | Depends on |
|---|---|---|
| `CaseTemplate` model (extend) | Add jsonb casts for `data_schema`, `candidacy_rubric`, `agenda`, `state_machine`; bool cast for `is_active` | migration |
| `SpecialtyTemplateSeeder` (extend) | Set engine fields on the existing 4 rows (idempotent `updateOrInsert` by `slug`) | model |
| `BoardTemplateService` (new) | `resolve(slug)`; `validate(tpl, data): array<warning>` (soft — type + required checks against `data_schema`) | model |
| `CaseStateMachine` (new) | `initialState(tpl)`, `canTransition(tpl, from, to)`, `transition(case, event)`; null-safe no-op when `state_machine` is null | model |
| `ClinicalCase` changes | `template_id`/`state`/`structured_data` fillable + casts; `template()` relation; sets `state` from template on create | model, state machine |
| API (extend `CaseController`/`StoreCaseRequest`) | `StoreCaseRequest` accepts `template_id` + `structured_data`; store runs soft validation + sets initial state, surfaces warnings in `ApiResponse` meta. Template list/show already exist (`GET /case-templates`, `/case-templates/{slug}`) — add `?active=1` filter | service |
| Frontend `useCaseTemplates` hook | TanStack Query hook over `/case-templates`; expose engine fields in the types | API |
| Frontend dynamic form (`CaseForm`) | Render fields from selected template's `data_schema`; keep current oncology fields for `oncology-tumor-board`; show soft warnings inline | hook |

## 6. Data flow

`create case` → pick board template → form renders from `data_schema` → submit →
`BoardTemplateService.validate` (soft; warnings → `ApiResponse` meta) →
`CaseStateMachine.initialState(tpl)` sets `state` (null for stateless templates) →
persist. `Decision` / session / discussion flows are untouched and now hang off the
template-typed case.

## 7. Error handling

- Soft validation **never** 500s or 422s on data shape — warnings only.
- Unknown `board_template_id` → 422 (referential integrity, not schema conformance).
- Illegal state transition (`canTransition` false) → 422 with the attempted edge; null
  `state_machine` makes `transition` a no-op (cannot error).

## 8. Testing

**Backend (Pest):**
- Seeder idempotency (run twice → 4 rows, no dupes).
- `BoardTemplateService.validate`: conforming → no warnings; missing required → warning;
  wrong type → warning; always persists.
- `CaseStateMachine`: legal transition allowed; illegal rejected; null-template no-op.
- Case create backfill: existing-style oncology case → `tumor_board`, behaviour preserved.
- API list/show; soft warnings appear in meta.
- Data-safety guard (`tests/TestCase`) already redirects all pgsql connections to
  `aurora_test` — no prod/dev DB exposure.

**Frontend (Vitest + MSW):**
- `useBoardTemplates` returns active templates.
- Dynamic form renders fields from a `data_schema` fixture.
- Soft-warning display.

**Gates:** Pint, `tsc --noEmit`, `vite build`, Pest, Vitest all green before each commit.

## 9. Scope boundary (explicit YAGNI)

**In v1:** the engine (`board_templates` + service + state machine), `ClinicalCase`
generalization, the 4 template seeds, and template-driven case create.

**Deferred to later Phase A plans (each independently shippable):**
- Closed-loop FHIR `Task` engine (strategy §3.B).
- OMOP-native risk auto-compute at case creation (§3.C).
- SMART / CDS Hooks / US Core interop scaffold (§3.F).

## 10. Backward-compatibility guarantees

- All `clinical_cases` columns added are nullable/defaulted; migration is reversible.
- Existing cases backfill to `tumor_board`; `state` stays null; oncology create/edit/
  decision flows behave exactly as before.
- The protected auth system and existing `Decision` internals are untouched.
