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

## 4. Data model

### 4.1 `app.board_templates` (new, seeded)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `key` | varchar unique | `tumor_board`, `rare_disease_mdt`, `heart_team`, `complex_medical` |
| `name` | varchar | display name |
| `description` | text nullable | |
| `time_model` | varchar + CHECK | `episodic \| episode_of_care \| longitudinal \| diagnostic_odyssey` |
| `data_schema` | jsonb | `[{key,label,type,required,options?}]` |
| `candidacy_rubric` | jsonb nullable | `[{key,label,required}]` clearance items |
| `decision_schema` | jsonb | allowed decision types for this board |
| `agenda` | jsonb | default agenda sections (array of strings) |
| `state_machine` | jsonb nullable | `{initial, states:[...], transitions:[{from,to,event}]}` |
| `is_active` | boolean default true | |
| `created_at` / `updated_at` | timestamps | |

### 4.2 `clinical_cases` (additive migration — all nullable/defaulted)

| Column | Type | Notes |
|---|---|---|
| `board_template_id` | bigint FK → `board_templates.id` | backfilled to `tumor_board` |
| `state` | varchar nullable | set from `state_machine.initial` when present; else null |
| `structured_data` | jsonb default `{}` | validated softly against template `data_schema` |

> Migration is additive and reversible. Backfill: every existing case →
> `tumor_board` template id; `state` left null (oncology is stateless).

### 4.3 Seeds (4 templates, idempotent upsert by `key`)

- **`tumor_board`** — `episodic`, `state_machine: null`. `data_schema` mirrors current
  oncology case fields (stage, histology, biomarkers, …). Proves backward-compat.
- **`rare_disease_mdt`** — `diagnostic_odyssey`. FSM:
  `referral → deep_phenotyping → testing → mdt_review → matchmaking →
  {diagnosed | undiagnosed} → reanalysis`. Reuses Plan 4 reanalysis-loop semantics.
- **`heart_team`** — `episode_of_care`. FSM:
  `referred → workup → optimization → decision → procedure → recovery → closed`.
  `candidacy_rubric` carries multi-clearance items (e.g. cardiology, anesthesia, frailty).
- **`complex_medical`** — `longitudinal`, `state_machine: null`. `data_schema` =
  persistent problem list + goals-of-care axis.

## 5. Components

| Unit | Responsibility | Depends on |
|---|---|---|
| `BoardTemplate` model | Eloquent model + jsonb casts | migration |
| `BoardTemplateSeeder` | Idempotent upsert of the 4 seeds by `key` | model |
| `BoardTemplateService` | `resolve(key)`; `validate(tpl, data): array<warning>` (soft — type + required checks against `data_schema`) | model |
| `CaseStateMachine` | `initialState(tpl)`, `canTransition(tpl, from, to)`, `transition(case, event)`; null-safe no-op when `state_machine` is null | model |
| `ClinicalCase` changes | `board_template_id`/`state`/`structured_data` fillable + casts; `boardTemplate()` relation; sets `state` from template on create | model, state machine |
| API | `GET /api/board-templates` (active list), `GET /api/board-templates/{key}`; `StoreCaseRequest` accepts `board_template_id` + `structured_data`, surfaces soft warnings in `ApiResponse` meta | service |
| Frontend `useBoardTemplates` | TanStack Query hook listing active templates | API |
| Frontend dynamic form | Case-create form renders fields from `data_schema`; falls back to current oncology form for `tumor_board`; shows soft warnings inline | hook |

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
